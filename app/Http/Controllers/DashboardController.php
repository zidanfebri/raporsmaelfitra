<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{User, Kelas, Mapel, Jadwal, Nilai, Bab, Rapot};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB; // Kuncian utama pencegah error DB not found
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $sekarang = Carbon::now('Asia/Jakarta');
        $tanggalHariIni = $sekarang->toDateString();
        $jamSekarang = $sekarang->format('H:i:s');

        // --- ROLE: ADMIN ---
        if ($user->role == 'admin') {
            return view('dashboard.admin', [
                'siswa' => User::where('role', 'siswa')->get(),
                'guru' => User::whereIn('role', ['guru', 'walikelas'])->get(),
                'kelas' => Kelas::all(),
                'jadwal' => Jadwal::with(['guru', 'mapel', 'kelas'])
                            ->orderBy('tanggal', 'desc')
                            ->orderBy('jam_mulai', 'asc')
                            ->get()
            ]);
        }

        // --- ROLE: GURU ATAU WALI KELAS ---
        if ($user->role == 'guru' || $user->role == 'walikelas') {
            $semester = $request->semester;
            $tp = $request->tahun_pelajaran;
            $filterTanggal = $request->tanggal;
            $filterKelas = $request->kelas_filter; 
            $filterMapel = $request->mapel_filter;

            $mapelDiampu = Mapel::whereHas('gurus', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->get();

            $jadwalSemua = Jadwal::where('guru_id', $user->id) 
                ->with([
                    'mapel.gurus' => function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    }, 
                    'guru', 
                    'kelas.siswa' => function($query) {
                        $query->with(['nilai']); 
                    }
                ])
                ->when($semester, fn($q) => $q->where('semester', $semester))
                ->when($tp, fn($q) => $q->where('tahun_pelajaran', 'LIKE', "%{$tp}%"))
                ->when($filterTanggal, fn($q) => $q->where('tanggal', $filterTanggal))
                ->when($filterKelas, fn($q) => $q->where('kelas_id', $filterKelas)) 
                ->when($filterMapel, fn($q) => $q->where('mapel_id', $filterMapel))
                ->orderBy('tanggal', 'desc')
                ->orderBy('jam_mulai', 'asc')
                ->paginate(10);

            $jadwalSemua->appends($request->all());

            $jadwalSemua->getCollection()->transform(function($j) use ($tanggalHariIni, $jamSekarang) {
                $isToday = $j->tanggal == $tanggalHariIni;
                $j->is_aktif = ($isToday && $jamSekarang >= $j->jam_mulai) || $j->tanggal < $tanggalHariIni;
                return $j;
            });

            $dataBab = Bab::where('guru_id', $user->id)->get();

            $kelasDiampu = Kelas::whereHas('jadwals', function($q) use ($user) {
                $q->where('guru_id', $user->id);
            })->get();

            $totalJadwalGuru = Jadwal::where('guru_id', $user->id)->count();
            $jadwalSudahDiinput = Jadwal::where('guru_id', $user->id)->whereHas('nilais')->count();
            $belumDiinputCount = $totalJadwalGuru - $jadwalSudahDiinput;

            return view('dashboard.guru_wali', compact('jadwalSemua', 'user', 'dataBab', 'mapelDiampu', 'kelasDiampu', 'belumDiinputCount', 'totalJadwalGuru'));
        }

        // --- ROLE SISWA ---
        if ($user->role == 'siswa') {
            $semester = $request->semester ?? '1';
            $tp = $request->tahun_pelajaran ?? '2025/2026';

            $nilaiMingguan = Nilai::where('siswa_id', $user->id)
                ->where('jenis', 'harian')
                ->whereHas('jadwal', function($q) use ($semester, $tp) {
                    $q->where('semester', $semester)->where('tahun_pelajaran', 'LIKE', "%{$tp}%");
                })
                ->with('jadwal.mapel')
                ->get()
                ->groupBy(function($item) {
                    return $item->jadwal->mapel->nama_mapel;
                });

            $nilaiUjian = Nilai::where('siswa_id', $user->id)
                ->whereIn('jenis', ['uts', 'uas'])
                ->whereHas('jadwal', function($q) use ($semester, $tp) {
                    $q->where('semester', $semester)->where('tahun_pelajaran', 'LIKE', "%{$tp}%");
                })
                ->with('jadwal.mapel')
                ->get()
                ->groupBy(function($item) {
                    return $item->jadwal->mapel->nama_mapel;
                });

            return view('dashboard.siswa', compact('user', 'nilaiMingguan', 'nilaiUjian', 'semester', 'tp'));
        }
        
        return redirect('/login')->with('error', 'Role tidak ditemukan.');
    }

    public function storeNilaiHarian(Request $request)
    {
        $request->validate([
            'jadwal_id' => 'required',
            'model_nilai' => 'required',
            'nomor_bab' => 'required', 
            'nama_bab' => 'required',  
            'nilai' => 'required|array',
            'presensi' => 'required|array'
        ]);

        $jadwal = Jadwal::findOrFail($request->jadwal_id);
        $isAnyValueFilled = false;

        // Pengecekan awal deteksi isi nilai numerik siswa massal
        foreach ($request->nilai as $s_id => $skor) {
            if (trim($skor) !== '' && is_numeric($skor)) {
                $isAnyValueFilled = true;
            }
        }

        foreach ($request->nilai as $siswa_id => $skor) {
            $statusPresensi = $request->presensi[$siswa_id] ?? 'Hadir';
            $cleanSkor = (trim($skor) !== '' && is_numeric($skor)) ? (int)$skor : null;
            
            // JIKA KOSONG TOTAL: Simpan data identitas model_nilai & materi bab namun kosongkan 'mingguan'
            Nilai::updateOrCreate(
                [
                    'siswa_id'    => $siswa_id,
                    'jadwal_id'   => $request->jadwal_id,
                    'jenis'       => 'harian'
                ],
                [
                    'model_nilai' => $request->model_nilai,
                    'id_bab'      => $request->nomor_bab,
                    'nama_bab'    => $request->nama_bab, 
                    'mingguan'    => $cleanSkor, 
                    'presensi'    => $statusPresensi,
                    'tanggal'     => $jadwal->tanggal
                ]
            );
        }

        if (!$isAnyValueFilled) {
            return back()->with('success', "Identitas kompetensi materi dasar berhasil disimpan! (Total progress hit belum bertambah karena nilai siswa kosong)");
        }

        return back()->with('success', "Data nilai harian berhasil diperbarui dan disinkronkan ke server!");
    }

    public function storeNilaiUtsUas(Request $request)
    {
        $request->validate([
            'jadwal_id' => 'required',
            'nilai' => 'required|array',
            'presensi' => 'required|array'
        ]);

        $jadwal = Jadwal::findOrFail($request->jadwal_id);
        $tipeUjian = $jadwal->tipe;

        foreach ($request->nilai as $siswa_id => $data) {
            $skorInput = $data[$tipeUjian] ?? null;
            $statusPresensi = $request->presensi[$siswa_id][$tipeUjian] ?? 'Hadir';

            Nilai::updateOrCreate(
                [
                    'siswa_id'  => $siswa_id,
                    'jadwal_id' => $jadwal->id,
                    'jenis'     => $tipeUjian,
                ],
                [
                    $tipeUjian => $skorInput,
                    'presensi' => $statusPresensi,
                    'tanggal'  => $jadwal->tanggal,
                ]
            );
        }
        return back()->with('success', 'Nilai & Presensi ' . strtoupper($tipeUjian) . ' berhasil disimpan!');
    }

    public function listJadwal() {
        return view('admin.jadwal', [
            'jadwal' => Jadwal::with(['guru', 'mapel', 'kelas'])->paginate(10),
            'mapel' => Mapel::all(),
            'guru' => User::whereIn('role', ['guru', 'walikelas'])->get(),
            'kelas' => Kelas::all()
        ]);
    }
    
    public function daftarKelas(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return redirect()->back()->with('error', 'Akses dibatasi! Halaman ini hanya dapat dibuka oleh Admin.');
        }

        $search = $request->search;

        $kelas = Kelas::withCount('siswa')
            ->when($search, function($query) use ($search) {
                return $query->where('nama_kelas', 'LIKE', "%{$search}%");
            })
            ->paginate(10);

        $kelas->appends($request->all());

        return view('admin.kelas', compact('kelas'));
    }

    public function daftarJadwalUjian(Request $request) 
    {
        $semester = $request->semester;
        $tp = $request->tahun_pelajaran;
        $mapel_id = $request->mapel_filter;

        $jadwalUjian = Jadwal::with(['guru', 'mapel', 'kelas'])
            ->whereIn('tipe', ['uts', 'uas']) 
            ->when($semester, function($query) use ($semester) {
                return $query->where('semester', $semester);
            })
            ->when($tp, function($query) use ($tp) {
                return $query->where('tahun_pelajaran', 'LIKE', "%{$tp}%");
            })
            ->when($mapel_id, function($query) use ($mapel_id) {
                return $query->where('mapel_id', $mapel_id);
            })
            ->orderBy('tanggal', 'desc')
            ->paginate(10);

        $jadwalUjian->appends($request->all());

        $mapel = Mapel::all();
        $guru = User::whereIn('role', ['guru', 'walikelas'])->get();
        $kelas = Kelas::all();

        return view('admin.jadwal_ujian', compact('jadwalUjian', 'mapel', 'guru', 'kelas'));
    }
    
    public function storeKelas(Request $request) {
        $request->validate(['nama_kelas' => 'required']);
        Kelas::create(['nama_kelas' => $request->nama_kelas]);
        return back()->with('success', 'Kelas berhasil ditambahkan!');
    }

    public function updateKelas(Request $request, $id) {
        $request->validate(['nama_kelas' => 'required']);
        Kelas::findOrFail($id)->update(['nama_kelas' => $request->nama_kelas]);
        return back()->with('success', 'Nama kelas berhasil diperbarui!');
    }

    public function daftarJadwal(Request $request) {
        $semester = $request->semester;
        $tp = $request->tahun_pelajaran;
        $mapel_id = $request->mapel_filter;
        $tanggal = $request->tanggal;
        $kelas_id = $request->kelas_filter; 
        $hari = $request->hari_filter;       

        $jadwal = Jadwal::with(['guru', 'mapel', 'kelas'])
            ->where('tipe', 'mingguan') 
            ->when($semester, function($query) use ($semester) {
                return $query->where('semester', $semester);
            })
            ->when($tp, function($query) use ($tp) {
                return $query->where('tahun_pelajaran', 'LIKE', "%{$tp}%");
            })
            ->when($mapel_id, function($query) use ($mapel_id) {
                return $query->where('mapel_id', $mapel_id);
            })
            ->when($kelas_id, function($query) use ($kelas_id) { 
                return $query->where('kelas_id', $kelas_id);
            })
            ->when($hari, function($query) use ($hari) { 
                return $query->where('hari', $hari);
            })
            ->when($tanggal, fn($q) => $q->where('tanggal', $tanggal))
            ->orderBy('tanggal', 'desc')
            ->paginate(10);

        $jadwal->appends($request->all());

        $mapel = Mapel::all();
        $guru = User::whereIn('role', ['guru', 'walikelas'])->get();
        $kelas = Kelas::all();
        return view('admin.jadwal', compact('jadwal', 'mapel', 'guru', 'kelas'));
    }

    public function daftarNilaiWali()
    {
        $user = Auth::user();
        $query = Jadwal::with(['mapel', 'kelas', 'guru']);

        if ($user->role == 'walikelas') {
            $query->where(function($q) use ($user) {
                $q->where('kelas_id', $user->kelas_id)
                  ->orWhere('guru_id', $user->id);
            });
        } else {
            $query->where('guru_id', $user->id);
        }

        $jadwalMapel = $query->get()->groupBy('mapel_id');

        return view('wali.daftar_nilai_mapel', compact('jadwalMapel'));
    }

    public function getRiwayatNil($mapel_id, $kelas_id, $jenis, Request $request)
    {
        $semester = $request->query('semester');
        $tp = $request->query('tahun_pelajaran');

        $jadwalIds = Jadwal::where('mapel_id', $mapel_id)
            ->where('kelas_id', $kelas_id)
            ->when($semester, fn($q) => $q->where('semester', $semester))
            ->when($tp, fn($q) => $q->where('tahun_pelajaran', $tp))
            ->pluck('id');

        $queryNilai = Nilai::whereIn('jadwal_id', $jadwalIds)->where('jenis', $jenis);

        if (strtolower($jenis) === 'harian') {
            $pertemuan = $queryNilai->orderBy('tanggal', 'asc')
                ->get()
                ->map(function($n) {
                    return [
                        'tanggal' => Carbon::parse($n->tanggal)->format('d/m/Y'),
                        'nomor_bab' => $n->id_bab ?? '?', 
                        'nama_bab' => $n->nama_bab ?? 'Bab Tidak Diisi',
                        'model' => ucwords($n->model_nilai ?? 'Nilai'),
                        'id_bab' => $n->id_bab,
                        'model_key' => $n->model_nilai,
                        'jadwal_id' => $n->jadwal_id
                    ];
                })->unique(function ($item) {
                    return $item['jadwal_id'].$item['model_key'].$item['id_bab'];
                })->values();
        } else {
            $pertemuan = $queryNilai->orderBy('tanggal', 'asc')
                ->get()
                ->map(function($n) {
                    return [
                        'tanggal' => Carbon::parse($n->tanggal)->format('d/m/Y'),
                        'nomor_bab' => $n->id_bab ?? '?', 
                        'nama_bab' => $n->nama_bab ?? 'Bab Tidak Diisi',
                        'model' => ucwords($n->model_nilai ?? 'Nilai'),
                        'id_bab' => $n->id_bab,
                        'model_key' => $n->model_nilai,
                        'jadwal_id' => $n->jadwal_id
                    ];
                })->unique(function ($item) {
                    return $item['tanggal'].$item['id_bab'].$item['model_key'];
                })->values();
        }

        $siswa = User::where('role', 'siswa')
            ->where('kelas_id', $kelas_id)
            ->get();

        $dataSiswa = [];
        foreach ($siswa as $s) {
            $nilaiSiswa = [];
            foreach ($pertemuan as $p) {
                if (strtolower($jenis) === 'harian') {
                    $skor = Nilai::where('siswa_id', $s->id)
                        ->where('jadwal_id', $p['jadwal_id'])
                        ->where('model_nilai', $p['model_key'])
                        ->where('id_bab', $p['id_bab'])
                        ->first();
                        
                    $nilaiSiswa[] = $skor->mingguan ?? '-';
                } else {
                    $skor = Nilai::where('siswa_id', $s->id)
                        ->whereIn('jadwal_id', $jadwalIds)
                        ->where('id_bab', $p['id_bab'])
                        ->where('model_nilai', $p['model_key'])
                        ->first();
                        
                    $nilaiSiswa[] = $skor->{$jenis} ?? '-';
                }
            }

            $dataSiswa[] = [
                'id' => $s->id,
                'nama' => $s->nama,
                'nilai_array' => $nilaiSiswa
            ];
        }

        if (strtoupper($jenis) === 'UTS' || strtoupper($jenis) === 'UAS') {
            usort($dataSiswa, function($a, $b) {
                $nilaiA = isset($a['nilai_array'][0]) && $a['nilai_array'][0] !== '-' ? (int)$a['nilai_array'][0] : 0;
                $nilaiB = isset($b['nilai_array'][0]) && $b['nilai_array'][0] !== '-' ? (int)$b['nilai_array'][0] : 0;
                return $nilaiB <=> $nilaiA;
            });
        }

        return response()->json([
            'mapel' => Mapel::find($mapel_id)->nama_mapel ?? 'Mata Pelajaran',
            'kelas' => Kelas::find($kelas_id)->nama_kelas ?? 'Kelas',
            'jenis' => strtoupper($jenis),
            'header_data' => $pertemuan,
            'siswa' => $dataSiswa
        ]);
    }

    public function daftarSeluruhNilai()
    {
        $user = Auth::user();
        $siswa = User::where('kelas_id', $user->kelas_id)->where('role', 'siswa')->get();
        
        foreach($siswa as $s) {
            $s->rekap = Nilai::where('siswa_id', $s->id)
                ->with('jadwal.mapel')
                ->get()
                ->groupBy('jadwal_id');
        }
        return view('admin.rekap_nilai', compact('siswa'));
    }

    public function halamanCetakRapot()
    {
        $user = Auth::user();
        $siswa = User::where('kelas_id', $user->kelas_id)->where('role', 'siswa')->get();

        foreach($siswa as $s) {
            $s->data_rapot = Nilai::where('siswa_id', $s->id)
                ->where('jenis', 'uas', 'uts')
                ->with('jadwal.mapel')
                ->get();
        }
        return view('admin.cetak_rapot', compact('siswa'));
    }

    // ================= SINKRONISASI TOTAL: SEPARATED RAPOTS TABLE ENGINE =================
    public function laporan(Request $request) {
        $user = Auth::user();
        $semester = $request->semester ?? '1';
        $tp = $request->tahun_pelajaran ?? '2025/2026';
        
        if ($user->role == 'walikelas') {
            $kelas_id = $user->kelas_id; 
        } else {
            $kelas_id = $request->kelas_id; 
        }

        $siswa = User::where('role', 'siswa')
            ->when($kelas_id, function($q) use ($kelas_id) {
                $q->where('kelas_id', $kelas_id);
            })
            ->with(['kelas', 'nilai' => function($query) use ($semester, $tp) {
                $query->whereHas('jadwal', function($q) use ($semester, $tp) {
                    $q->where('semester', $semester)->where('tahun_pelajaran', 'LIKE', "%{$tp}%");
                })->with('jadwal.mapel');
            }])
            ->get();

        $kelas = Kelas::all();

        $plotMengajar = Jadwal::where('kelas_id', $kelas_id)
            ->where('semester', $semester)
            ->where('tahun_pelajaran', 'LIKE', "%{$tp}%")
            ->select('guru_id', 'mapel_id')
            ->groupBy('guru_id', 'mapel_id')
            ->with(['guru', 'mapel'])
            ->get();

        $jadwalBolong = [];
        $raportBisaDicetak = true;

        foreach ($plotMengajar as $plot) {
            $listJadwalIds = Jadwal::where('kelas_id', $kelas_id)
                ->where('guru_id', $plot->guru_id)
                ->where('mapel_id', $plot->mapel_id)
                ->where('semester', $semester)
                ->where('tahun_pelajaran', 'LIKE', "%{$tp}%")
                ->pluck('id');

            $jumlahInputNilaiUnik = Nilai::whereIn('jadwal_id', $listJadwalIds)
                ->where('jenis', 'harian')
                ->whereNotNull('mingguan')
                ->select('jadwal_id')
                ->groupBy('jadwal_id')
                ->get()
                ->count();

            $apakahSudahInputUas = Nilai::whereIn('jadwal_id', $listJadwalIds)
                ->where('jenis', 'uas')
                ->exists();

            if ($jumlahInputNilaiUnik < 1 || !$apakahSudahInputUas) {
                $raportBisaDicetak = false;
                
                $dummyJadwal = new Jadwal();
                $dummyJadwal->mapel = $plot->mapel;
                $dummyJadwal->guru = $plot->guru;
                $dummyJadwal->total_input_saat_ini = $jumlahInputNilaiUnik; 
                $dummyJadwal->kekurangan = 1 - $jumlahInputNilaiUnik;
                $dummyJadwal->status_uas_terisi = $apakahSudahInputUas;
                
                $jadwalBolong[] = $dummyJadwal;
            }
        }

        // FIX LOGIKA SINKRONISASI: Baca data asli database, jika kosong baru berikan default
        foreach ($siswa as $s) {
            $existingRapot = Rapot::where('siswa_id', $s->id)
                ->where('semester', $semester)
                ->where('tahun_pelajaran', $tp)
                ->first();

            $s->rapot_kokurikuler  = $existingRapot ? $existingRapot->kokurikuler : '';
            $s->rapot_eskul_nama1  = $existingRapot ? $existingRapot->eskul_nama1 : '';
            $s->rapot_eskul_pred1  = $existingRapot ? $existingRapot->eskul_pred1 : 'B';
            $s->rapot_eskul_desc1  = $existingRapot ? $existingRapot->eskul_desc1 : '';
            $s->rapot_eskul_nama2  = $existingRapot ? $existingRapot->eskul_nama2 : '';
            $s->rapot_eskul_pred2  = $existingRapot ? $existingRapot->eskul_pred2 : 'B';
            $s->rapot_eskul_desc2  = $existingRapot ? $existingRapot->eskul_desc2 : '';
            $s->rapot_abs_sakit    = $existingRapot ? $existingRapot->abs_sakit : null;
            $s->rapot_abs_izin     = $existingRapot ? $existingRapot->abs_izin : null;
            $s->rapot_abs_alfa     = $existingRapot ? $existingRapot->abs_alfa : null;
            $s->rapot_catatan_wali = $existingRapot ? $existingRapot->catatan_wali : '';
            
            // Satukan rekap nilai untuk memudahkan rendering tampilan cetak raport
            $s->rekap_nilai = $s->nilai->groupBy(function($item) {
                return trim($item->jadwal->mapel->nama_mapel ?? '');
            });
        }

        return view('admin.laporan', compact('siswa', 'kelas', 'kelas_id', 'raportBisaDicetak', 'jadwalBolong'));
    }

    public function unduhTemplateRapotKelas(Request $request, $kelas_id)
    {
        $kelasInfo = Kelas::findOrFail($kelas_id);
        $semester = $request->query('semester', '1');
        $tp = $request->query('tahun_pelajaran', '2025/2026');
        
        $namaFile = 'TEMPLATE_RAPOT_MANUAL_KELAS_' . str_replace(' ', '_', strtoupper($kelasInfo->nama_kelas)) . '.xlsx';
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'ID_SISWA (JANGAN DIUBAH)');
        $sheet->setCellValue('B1', 'NAMA LENGKAP SISWA');
        $sheet->setCellValue('C1', 'NISN');
        $sheet->setCellValue('D1', 'CATATAN KOKURIKULER');
        $sheet->setCellValue('E1', 'NAMA EKSTRAKURIKULER 1');
        $sheet->setCellValue('F1', 'PREDIKAT ESKUL 1 (A/B/C/D)');
        $sheet->setCellValue('G1', 'DESKRIPSI KEMAJUAN ESKUL 1');
        $sheet->setCellValue('H1', 'NAMA EKSTRAKURIKULER 2 (OPSIONAL)');
        $sheet->setCellValue('I1', 'PREDIKAT ESKUL 2 (A/B/C/D)');
        $sheet->setCellValue('J1', 'DESKRIPSI KEMAJUAN ESKUL 2');
        $sheet->setCellValue('K1', 'ABSEN SAKIT (ANGKA)');
        $sheet->setCellValue('L1', 'ABSEN IZIN (ANGKA)');
        $sheet->setCellValue('M1', 'ABSEN ALFA (ANGKA)');
        $sheet->setCellValue('N1', 'CATATAN EVALUASI WALI KELAS');

        $siswaDaftar = User::where('role', 'siswa')->where('kelas_id', $kelas_id)->orderBy('nama', 'asc')->get();
        $rowIdx = 2;

        foreach ($siswaDaftar as $s) {
            $existingRapot = Rapot::where('siswa_id', $s->id)->where('semester', $semester)->where('tahun_pelajaran', $tp)->first();

            $sheet->setCellValue('A' . $rowIdx, $s->id);
            $sheet->setCellValue('B' . $rowIdx, strtoupper($s->nama));
            $sheet->setCellValue('C' . $rowIdx, ' ' . $s->nisn);
            $sheet->setCellValue('D' . $rowIdx, $existingRapot ? $existingRapot->kokurikuler : '');
            $sheet->setCellValue('E' . $rowIdx, $existingRapot ? $existingRapot->eskul_nama1 : '');
            $sheet->setCellValue('F' . $rowIdx, $existingRapot ? $existingRapot->eskul_pred1 : 'B');
            $sheet->setCellValue('G' . $rowIdx, $existingRapot ? $existingRapot->eskul_desc1 : '');
            $sheet->setCellValue('H' . $rowIdx, $existingRapot ? $existingRapot->eskul_nama2 : '');
            $sheet->setCellValue('I' . $rowIdx, $existingRapot ? $existingRapot->eskul_pred2 : 'B');
            $sheet->setCellValue('J' . $rowIdx, $existingRapot ? $existingRapot->eskul_desc2 : '');
            $sheet->setCellValue('K' . $rowIdx, ($existingRapot && $existingRapot->abs_sakit !== null) ? $existingRapot->abs_sakit : '');
            $sheet->setCellValue('L' . $rowIdx, ($existingRapot && $existingRapot->abs_izin !== null) ? $existingRapot->abs_izin : '');
            $sheet->setCellValue('M' . $rowIdx, ($existingRapot && $existingRapot->abs_alfa !== null) ? $existingRapot->abs_alfa : '');
            $sheet->setCellValue('N' . $rowIdx, $existingRapot ? $existingRapot->catatan_wali : '');
            $rowIdx++;
        }

        $validation = $sheet->getCell('F2')->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(false);
        $validation->setShowDropDown(true);
        $validation->setFormula1('"A,B,C,D"');

        for ($i = 2; $i < $rowIdx; $i++) {
            $sheet->getCell("F{$i}")->setDataValidation(clone $validation);
            $sheet->getCell("I{$i}")->setDataValidation(clone $validation);
        }

        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        if (ob_get_contents()) { ob_end_clean(); }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $namaFile . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    // ================= SINKRONISASI TOTAL: IMPOR EXCEL MASSAL KELAS LANGSUNG KE TABEL RAPOTS =================
    public function importRapotKelasAsync(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|file',
            'kelas_id'   => 'required',
            'semester'   => 'required',
            'tahun_pelajaran' => 'required'
        ]);

        try {
            $file = $request->file('file_excel');
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();
            
            $compiledData = [];

            for ($row = 2; $row <= $highestRow; $row++) {
                $siswa_id = trim($sheet->getCell('A' . $row)->getValue());
                if (!$siswa_id || !is_numeric($siswa_id)) { continue; }

                $kokurikuler   = trim($sheet->getCell('D' . $row)->getValue() ?? '-');
                $eskul_nama1   = trim($sheet->getCell('E' . $row)->getValue() ?? '');
                $eskul_pred1   = strtoupper(trim($sheet->getCell('F' . $row)->getValue() ?? 'B'));
                $eskul_desc1   = trim($sheet->getCell('G' . $row)->getValue() ?? '');
                
                $eskul_nama2   = trim($sheet->getCell('H' . $row)->getValue() ?? '');
                $eskul_pred2   = strtoupper(trim($sheet->getCell('I' . $row)->getValue() ?? 'B'));
                $eskul_desc2   = trim($sheet->getCell('J' . $row)->getValue() ?? '');

                $sakit_val     = trim($sheet->getCell('K' . $row)->getValue());
                $sakit         = (is_numeric($sakit_val) && $sakit_val !== '') ? (int)$sakit_val : null;

                $izin_val      = trim($sheet->getCell('L' . $row)->getValue());
                $izin          = (is_numeric($izin_val) && $izin_val !== '') ? (int)$izin_val : null;

                $alfa_val      = trim($sheet->getCell('M' . $row)->getValue());
                $alfa          = (is_numeric($alfa_val) && $alfa_val !== '') ? (int)$alfa_val : null;
                
                $catatan_wali  = trim($sheet->getCell('N' . $row)->getValue() ?? '-');

                // Murni menyimpan data evaluasi wali kelas langsung ke tabel rapots
                Rapot::updateOrCreate(
                    [
                        'siswa_id'        => $siswa_id,
                        'semester'        => $request->semester,
                        'tahun_pelajaran' => $request->tahun_pelajaran
                    ],
                    [
                        'kokurikuler'  => $kokurikuler,
                        'eskul_nama1'  => $eskul_nama1,
                        'eskul_pred1'  => $eskul_pred1,
                        'eskul_desc1'  => $eskul_desc1,
                        'eskul_nama2'  => $eskul_nama2,
                        'eskul_pred2'  => $eskul_pred2,
                        'eskul_desc2'  => $eskul_desc2,
                        'abs_sakit'    => $sakit,
                        'abs_izin'     => $izin,
                        'abs_alfa'     => $alfa,
                        'catatan_wali' => $catatan_wali
                    ]
                );

                $compiledData[] = [
                    'siswa_id'      => (int)$siswa_id,
                    'kokurikuler'   => $kokurikuler,
                    'eskul_nama1'   => $eskul_nama1,
                    'eskul_pred1'   => $eskul_pred1,
                    'eskul_desc1'   => $eskul_desc1,
                    'eskul_nama2'   => $eskul_nama2,
                    'eskul_pred2'   => $eskul_pred2,
                    'eskul_desc2'   => $eskul_desc2,
                    'sakit'         => $sakit,
                    'izin'          => $izin,
                    'alfa'          => $alfa,
                    'catatan_wali'  => $catatan_wali,
                ];
            }

            return response()->json([
                'success' => true,
                'data'    => $compiledData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses berkas spreadsheet rapot: ' . $e->getMessage()
            ], 500);
        }
    }

    // ================= SINKRONISASI TOTAL: SIMPAN FORM ISIAN MANUAL TOMBOL POP-UP MODAL =================
    public function simpanManualRapotSiswa(Request $request)
    {
        $request->validate([
            'siswa_id'        => 'required',
            'kelas_id'        => 'required',
            'semester'        => 'required',
            'tahun_pelajaran' => 'required'
        ]);

        $sakit = (trim($request->sakit) !== '' && is_numeric($request->sakit)) ? (int)$request->sakit : null;
        $izin  = (trim($request->izin) !== '' && is_numeric($request->izin)) ? (int)$request->izin : null;
        $alfa  = (trim($request->alfa) !== '' && is_numeric($request->alfa)) ? (int)$request->alfa : null;

        Rapot::updateOrCreate(
            [
                'siswa_id'        => $request->siswa_id,
                'semester'        => $request->semester,
                'tahun_pelajaran' => $request->tahun_pelajaran
            ],
            [
                'kokurikuler'  => $request->kokurikuler ?? '-',
                'eskul_nama1'  => $request->eskul_nama1,
                'eskul_pred1'  => $request->eskul_pred1 ?? 'B',
                'eskul_desc1'  => $request->eskul_desc1,
                'eskul_nama2'  => $request->eskul_nama2,
                'eskul_pred2'  => $request->eskul_pred2 ?? 'B',
                'eskul_desc2'  => $request->eskul_desc2,
                'abs_sakit'    => $sakit,
                'abs_izin'     => $izin,
                'abs_alfa'     => $alfa,
                'catatan_wali' => $request->catatan_wali ?? '-'
            ]
        );

        return response()->json([
            'success' => true
        ]);
    }
    
    public function lihatLegerWali(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'walikelas' && $user->role !== 'admin') {
            return redirect()->back()->with('error', 'Akses dibatasi hanya untuk Wali Kelas.');
        }

        $semester = $request->semester ?? '1';
        $tp = $request->tahun_pelajaran ?? '2025/2026';
        $kelas_id = ($user->role == 'walikelas') ? $user->kelas_id : $request->kelas_id;

        if (!$kelas_id) {
            $kelas = Kelas::all();
            return view('wali.leger', ['siswaLeger' => [], 'kelas' => $kelas, 'allMapels' => [], 'kelas_id' => null]);
        }

        $infoKelas = Kelas::findOrFail($kelas_id);
        $kelas = Kelas::all();
        
        $allMapels = Mapel::whereHas('jadwals', function($q) use ($kelas_id, $semester, $tp) {
            $q->where('kelas_id', $kelas_id)->where('semester', $semester)->where('tahun_pelajaran', 'LIKE', "%{$tp}%");
        })->get();

        $daftarSiswa = User::where('role', 'siswa')->where('kelas_id', $kelas_id)->get();
        $siswaLeger = [];

        foreach ($daftarSiswa as $s) {
            $nilaiMapelArray = [];
            $totalSkorSiswa = 0;
            $counterMapelSkor = 0;

            foreach ($allMapels as $m) {
                $nilaiGroup = Nilai::where('siswa_id', $s->id)
                    ->whereHas('jadwal', function($q) use ($kelas_id, $m, $semester, $tp) {
                        $q->where('kelas_id', $kelas_id)->where('mapel_id', $m->id)
                          ->where('semester', $semester)->where('tahun_pelajaran', 'LIKE', "%{$tp}%");
                    })->get();

                $avgHarian = $nilaiGroup->where('jenis', 'harian')->avg('mingguan') ?? 0;
                $skorUas = $nilaiGroup->where('jenis', 'uas')->first()->uas ?? 0;

                $nilaiAkhirMapel = 0;
                if ($avgHarian > 0 || $skorUas > 0) {
                    $nilaiAkhirMapel = round(($avgHarian * 0.70) + ($skorUas * 0.30));
                }

                $nilaiMapelArray[$m->id] = $nilaiAkhirMapel > 0 ? $nilaiAkhirMapel : 0;
                
                if ($nilaiAkhirMapel > 0) {
                    $totalSkorSiswa += $nilaiAkhirMapel;
                }
                $counterMapelSkor++;
            }

            $rataRataSiswa = $counterMapelSkor > 0 ? round($totalSkorSiswa / $counterMapelSkor, 2) : 0;

            $siswaLeger[] = [
                'nama' => $s->nama,
                'nisn' => $s->nisn,
                'scores' => $nilaiMapelArray,
                'total' => $totalSkorSiswa,
                'rata_rata' => $rataRataSiswa,
                'rangking' => 1
            ];
        }

        usort($siswaLeger, function($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        foreach ($siswaLeger as $index => $data) {
            $siswaLeger[$index]['rangking'] = $index + 1;
        }

        $namaKelasStr = strtoupper($infoKelas->nama_kelas ?? '');
        $isKelas10 = str_contains($namaKelasStr, 'X ') || $namaKelasStr === 'X' || str_contains($namaKelasStr, 'KELAS 10') || str_contains($namaKelasStr, 'KELAS X') || str_contains($namaKelasStr, '10');

        if ($isKelas10) {
            $strukturUrutanRapot = ['Pendidikan Agama dan Budi Pekerti', 'Bahasa Indonesia', 'Bahasa Inggris', 'Matematika', 'Fisika', 'Kimia', 'Biologi', 'Sejarah Umum', 'Geografi', 'Ekonomi', 'Sosiologi', 'Pendidikan Jasmani Olahraga dan Kesehatan', 'Informatika', 'Basa Sunda', 'Bahasa Arab', 'Science Project'];
        } else {
            if (str_contains($namaKelasStr, 'IPA')) {
                $strukturUrutanRapot = ['Pendidikan Agama dan Budi Pekerti', 'Bahasa Indonesia', 'Matematika', 'Bahasa Inggris', 'Sejarah Umum', 'Pendidikan Jasmani Olahraga dan Kesehatan', 'Seni Budaya', 'Matematika Tingkat Lanjut', 'Fisika', 'Kimia', 'Biologi', 'Informatika', 'Basa Sunda', 'Bahasa Arab', 'Science Project'];
            } else {
                $strukturUrutanRapot = ['Pendidikan Agama dan Budi Pekerti', 'Bahasa Indonesia', 'Matematika', 'Bahasa Inggris', 'Sejarah Umum', 'Pendidikan Jasmani Olahraga dan Kesehatan', 'Seni Budaya', 'Sejarah Indonesia', 'Geografi', 'Ekonomi', 'Sosiologi', 'Informatika', 'Basa Sunda', 'Bahasa Arab', 'Desain Grafis', 'Science Project'];
            }
        }

        $allMapelsSorted = collect();
        foreach ($strukturUrutanRapot as $namaMapelBaku) {
            $mapelMatch = $allMapels->first(fn($m) => trim(strtolower($m->nama_mapel)) === trim(strtolower($namaMapelBaku)));
            if ($mapelMatch) $allMapelsSorted->push($mapelMatch);
        }
        foreach ($allMapels as $mOriginal) {
            if (!$allMapelsSorted->contains('id', $mOriginal->id)) $allMapelsSorted->push($mOriginal);
        }

        // --- KALKULASI STATISTIK PER MATA PELAJARAN ---
        $statMapel = [];
        foreach ($allMapelsSorted as $m) {
            $koleksiNilai = [];
            foreach ($siswaLeger as $row) {
                if (isset($row['scores'][$m->id]) && $row['scores'][$m->id] > 0) {
                    $koleksiNilai[] = $row['scores'][$m->id];
                }
            }
            
            $statMapel[$m->id] = [
                'shadow_key' => $m->id,
                'jumlah'    => count($koleksiNilai) > 0 ? array_sum($koleksiNilai) : 0,
                'rata_rata' => count($koleksiNilai) > 0 ? round(array_sum($koleksiNilai) / count($koleksiNilai), 1) : 0,
                'terbesar'  => count($koleksiNilai) > 0 ? max($koleksiNilai) : 0,
                'terkecil'  => count($koleksiNilai) > 0 ? min($koleksiNilai) : 0,
            ];
        }

        if ($request->query('export') === 'excel') {
            $namaFile = 'LEGER_KELAS_' . str_replace(' ', '_', strtoupper($infoKelas->nama_kelas)) . '_SEMESTER_' . $semester . '.xls';
            if (ob_get_contents()) { ob_end_clean(); }
            header("Content-Type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=\"$namaFile\"");
            header("Pragma: no-cache");
            header("Expires: 0");
            return view('wali.leger_excel', compact('siswaLeger', 'allMapelsSorted', 'infoKelas', 'semester', 'tp', 'statMapel'));
        }

        return view('wali.leger', compact('siswaLeger', 'kelas', 'allMapels', 'kelas_id', 'infoKelas', 'allMapelsSorted', 'statMapel'));
    }

    public function lihatLegerUjianMurni(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return redirect()->back()->with('error', 'Akses dibatasi! Halaman ini hanya dapat dibuka oleh Admin.');
        }

        $semester = $request->semester ?? '1';
        $tp = $request->tahun_pelajaran ?? '2025/2026';
        $kelas_id = $request->kelas_id; 
        $jenis_ujian = $request->jenis_ujian ?? 'uts'; 

        if (!$kelas_id) {
            $kelas = Kelas::all();
            return view('wali.leger_ujian', ['siswaLeger' => [], 'kelas' => $kelas, 'allMapelsSorted' => [], 'kelas_id' => null, 'jenis_ujian' => $jenis_ujian]);
        }

        $infoKelas = Kelas::findOrFail($kelas_id);
        $kelas = Kelas::all();
        
        $allMapels = Mapel::whereHas('jadwals', function($q) use ($kelas_id, $semester, $tp) {
            $q->where('kelas_id', $kelas_id)->where('semester', $semester)->where('tahun_pelajaran', 'LIKE', "%{$tp}%");
        })->get();

        $namaKelasStr = strtoupper($infoKelas->nama_kelas ?? '');
        $isKelas10 = str_contains($namaKelasStr, 'X ') || $namaKelasStr === 'X' || str_contains($namaKelasStr, '10');
        if ($isKelas10) {
            $strukturUrutanRapot = ['Pendidikan Agama dan Budi Pekerti', 'Bahasa Indonesia', 'Bahasa Inggris', 'Matematika', 'Fisika', 'Kimia', 'Biologi', 'Sejarah Umum', 'Geografi', 'Ekonomi', 'Sosiologi', 'Pendidikan Jasmani Olahraga dan Kesehatan', 'Informatika', 'Basa Sunda', 'Bahasa Arab', 'Science Project'];
        } else {
            $strukturUrutanRapot = str_contains($namaKelasStr, 'IPA') 
                ? ['Pendidikan Agama dan Budi Pekerti', 'Bahasa Indonesia', 'Matematika', 'Bahasa Inggris', 'Sejarah Umum', 'Pendidikan Jasmani Olahraga dan Kesehatan', 'Seni Budaya', 'Matematika Tingkat Lanjut', 'Fisika', 'Kimia', 'Biologi', 'Informatika', 'Basa Sunda', 'Bahasa Arab', 'Science Project']
                : ['Pendidikan Agama dan Budi Pekerti', 'Bahasa Indonesia', 'Matematika', 'Bahasa Inggris', 'Sejarah Umum', 'Pendidikan Jasmani Olahraga dan Kesehatan', 'Seni Budaya', 'Sejarah Indonesia', 'Geografi', 'Ekonomi', 'Sosiologi', 'Informatika', 'Basa Sunda', 'Bahasa Arab', 'Desain Grafis', 'Science Project'];
        }

        $allMapelsSorted = collect();
        foreach ($strukturUrutanRapot as $namaMapelBaku) {
            $mapelMatch = $allMapels->first(fn($m) => trim(strtolower($m->nama_mapel)) === trim(strtolower($namaMapelBaku)));
            if ($mapelMatch) $allMapelsSorted->push($mapelMatch);
        }
        foreach ($allMapels as $mOriginal) {
            if (!$allMapelsSorted->contains('id', $mOriginal->id)) $allMapelsSorted->push($mOriginal);
        }

        $daftarSiswa = User::where('role', 'siswa')->where('kelas_id', $kelas_id)->get();
        $siswaLeger = [];

        foreach ($daftarSiswa as $s) {
            $nilaiMapelArray = [];
            $totalSkorSiswa = 0;
            $counterMapelSkor = 0;

            foreach ($allMapelsSorted as $m) {
                $skorUjian = Nilai::where('siswa_id', $s->id)
                    ->where('jenis', $jenis_ujian)
                    ->whereHas('jadwal', function($q) use ($kelas_id, $m, $semester, $tp) {
                        $q->where('kelas_id', $kelas_id)->where('mapel_id', $m->id)
                          ->where('semester', $semester)->where('tahun_pelajaran', 'LIKE', "%{$tp}%");
                    })->first();

                $nilaiMurni = $skorUjian ? ($skorUjian->{$jenis_ujian} ?? 0) : 0;
                $nilaiMapelArray[$m->id] = $nilaiMurni;
                
                if ($nilaiMurni > 0) {
                    $totalSkorSiswa += $nilaiMurni;
                    $counterMapelSkor++;
                }
            }

            $rataRataSiswa = $counterMapelSkor > 0 ? round($totalSkorSiswa / $counterMapelSkor, 2) : 0;

            $siswaLeger[] = [
                'nama' => $s->nama,
                'nisn' => $s->nisn,
                'scores' => $nilaiMapelArray,
                'total' => $totalSkorSiswa,
                'rata_rata' => $rataRataSiswa,
                'rangking' => 1
            ];
        }

        usort($siswaLeger, function($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        foreach ($siswaLeger as $index => $data) {
            $siswaLeger[$index]['rangking'] = $index + 1;
        }

        $statMapel = [];
        foreach ($allMapelsSorted as $m) {
            $koleksiNilai = [];
            foreach ($siswaLeger as $row) {
                if (isset($row['scores'][$m->id]) && $row['scores'][$m->id] > 0) {
                    $koleksiNilai[] = $row['scores'][$m->id];
                }
            }
            $statMapel[$m->id] = [
                'jumlah'    => count($koleksiNilai) > 0 ? array_sum($koleksiNilai) : 0,
                'rata_rata' => count($koleksiNilai) > 0 ? round(array_sum($koleksiNilai) / count($koleksiNilai), 1) : 0,
                'terbesar'  => count($koleksiNilai) > 0 ? max($koleksiNilai) : 0,
                'terkecil'  => count($koleksiNilai) > 0 ? min($koleksiNilai) : 0,
            ];
        }

        return view('wali.leger_ujian', compact('siswaLeger', 'kelas', 'allMapelsSorted', 'kelas_id', 'infoKelas', 'statMapel', 'jenis_ujian', 'semester', 'tp'));
    }

    public function rekapTranskripHarian(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return redirect()->back()->with('error', 'Akses dibatasi! Halaman ini hanya dapat dibuka oleh Admin.');
        }

        $semester = $request->semester ?? '1';
        $tp = $request->tahun_pelajaran ?? '2025/2026';
        $kelas_id = $request->kelas_id;
        $model_filter = $request->model_filter ?? 'tugas'; 

        if (!$kelas_id) {
            $kelas = Kelas::all();
            return view('wali.rekap_transkrip', ['siswaLeger' => [], 'kelas' => $kelas, 'mapelHeaders' => [], 'kelas_id' => null, 'model_filter' => $model_filter]);
        }

        $infoKelas = Kelas::findOrFail($kelas_id);
        $kelas = Kelas::all();

        $allMapels = Mapel::whereHas('jadwals', function($q) use ($kelas_id, $semester, $tp) {
            $q->where('kelas_id', $kelas_id)->where('semester', $semester)->where('tahun_pelajaran', 'LIKE', "%{$tp}%");
        })->get();

        $mapelHeaders = [];
        foreach ($allMapels as $m) {
            $jadwalIds = Jadwal::where('mapel_id', $m->id)
                ->where('kelas_id', $kelas_id)
                ->where('semester', $semester)
                ->where('tahun_pelajaran', 'LIKE', "%{$tp}%")
                ->pluck('id');

            $listBab = Nilai::whereIn('jadwal_id', $jadwalIds)
                ->where('jenis', 'harian')
                ->where('model_nilai', $model_filter)
                ->select('id_bab', 'nama_bab')
                ->groupBy('id_bab', 'nama_bab')
                ->orderBy('id_bab', 'asc')
                ->get();

            $mapelHeaders[$m->id] = [
                'info' => $m,
                'bab_list' => $listBab
            ];
        }

        $daftarSiswa = User::where('role', 'siswa')->where('kelas_id', $kelas_id)->orderBy('nama', 'asc')->get();
        $siswaLeger = [];

        foreach ($daftarSiswa as $s) {
            $matrixScores = [];
            $totalSkorSiswa = 0;
            $counterDataValid = 0;

            foreach ($mapelHeaders as $mId => $dataMapel) {
                foreach ($dataMapel['bab_list'] as $bab) {
                    $skorNode = Nilai::where('siswa_id', $s->id)
                        ->where('jenis', 'harian')
                        ->where('model_nilai', $model_filter)
                        ->where('id_bab', $bab->id_bab)
                        ->whereHas('jadwal', function($query) use ($mId, $kelas_id, $semester, $tp) {
                            $query->where('mapel_id', $mId)->where('kelas_id', $kelas_id)
                                  ->where('semester', $semester)->where('tahun_pelajaran', 'LIKE', "%{$tp}%");
                        })->first();

                    $nilaiMurni = $skorNode ? $skorNode->mingguan : null;
                    $matrixScores[$mId . '_' . $bab->id_bab] = $nilaiMurni;

                    if ($nilaiMurni !== null) {
                        $totalSkorSiswa += $nilaiMurni;
                        $counterDataValid++;
                    }
                }
            }

            $rataRataSiswa = $counterDataValid > 0 ? round($totalSkorSiswa / $counterDataValid, 1) : 0;

            $siswaLeger[] = [
                'nama' => $s->nama,
                'nisn' => $s->nisn,
                'matrix' => $matrixScores,
                'total' => $totalSkorSiswa,
                'rata_rata' => $rataRataSiswa
            ];
        }

        $statMatrix = [];
        foreach ($mapelHeaders as $mId => $dataMapel) {
            foreach ($dataMapel['bab_list'] as $bab) {
                $koleksiNilaiBab = [];
                $key = $mId . '_' . $bab->id_bab;

                foreach ($siswaLeger as $row) {
                    if (isset($row['matrix'][$key]) && $row['matrix'][$key] !== null) {
                        $koleksiNilaiBab[] = $row['matrix'][$key];
                    }
                }

                $statMatrix[$key] = [
                    'jumlah' => array_sum($koleksiNilaiBab),
                    'rata_rata' => count($koleksiNilaiBab) > 0 ? round(array_sum($koleksiNilaiBab) / count($koleksiNilaiBab), 1) : 0,
                    'terbesar' => count($koleksiNilaiBab) > 0 ? max($koleksiNilaiBab) : 0,
                    'terkecil' => count($koleksiNilaiBab) > 0 ? min($koleksiNilaiBab) : 0,
                ];
            }
        }

        return view('wali.rekap_transkrip', compact('siswaLeger', 'kelas', 'mapelHeaders', 'kelas_id', 'infoKelas', 'model_filter', 'statMatrix', 'semester', 'tp'));
    }

    public function rekapNilaiAkhirMapel($mapel_id, $kelas_id, Request $request)
    {
        $semester = $request->semester ?? '1';
        $tp = $request->tahun_pelajaran ?? '2025/2026';

        $mapelInfo = Mapel::findOrFail($mapel_id);
        $kelasInfo = Kelas::findOrFail($kelas_id);

        $jadwalIds = Jadwal::where('mapel_id', $mapel_id)
            ->where('kelas_id', $kelas_id)
            ->where('semester', $semester)
            ->where('tahun_pelajaran', 'LIKE', "%{$tp}%")
            ->pluck('id');

        $jadwalSampel = Jadwal::whereIn('id', $jadwalIds)->first();
        $kkmMapelGuru = 75; 
        if ($jadwalSampel) {
            $pivotData = DB::table('mapel_guru')
                ->where('user_id', $jadwalSampel->guru_id)
                ->where('mapel_id', $mapel_id)
                ->first();
            if ($pivotData && isset($pivotData->kkm)) {
                $kkmMapelGuru = $pivotData->kkm;
            }
        }

        $listBabTerinput = Nilai::whereIn('jadwal_id', $jadwalIds)
            ->where('jenis', 'harian')
            ->select('jadwal_id', 'id_bab', 'nama_bab', 'model_nilai')
            ->groupBy('jadwal_id', 'id_bab', 'nama_bab', 'model_nilai')
            ->get()
            ->map(function($b) {
                $detailJadwal = Jadwal::find($b->jadwal_id);
                $tanggalFormat = $detailJadwal ? \Carbon\Carbon::parse($detailJadwal->tanggal)->format('d/m') : '';
                
                return [
                    'jadwal_id' => $b->jadwal_id,
                    'id_bab' => $b->id_bab,
                    'nama_bab' => $b->nama_bab,
                    'model_nilai' => $b->model_nilai ?? 'tugas',
                    'tanggal' => $tanggalFormat
                ];
            })->toArray();

        $daftarSiswa = User::where('role', 'siswa')->where('kelas_id', $kelas_id)->get();
        $dataRekap = [];

        foreach ($daftarSiswa as $s) {
            $nilaiGroup = Nilai::where('siswa_id', $s->id)->whereIn('jadwal_id', $jadwalIds)->get();

            $arrayNilaiPerBab = [];
            $sumNilaiHarian = 0;
            $countNilaiHarian = 0;

            foreach ($listBabTerinput as $bab) {
                $nilaiBabRow = $nilaiGroup->where('jenis', 'harian')
                                         ->where('jadwal_id', $bab['jadwal_id'])
                                         ->where('model_nilai', $bab['model_nilai'])
                                         ->first();
                
                $skorBab = $nilaiBabRow ? $nilaiBabRow->mingguan : null;
                
                $uniqueKey = $bab['jadwal_id'] . '_' . str_replace(' ', '_', $bab['model_nilai']);
                $arrayNilaiPerBab[$uniqueKey] = $skorBab !== null ? $skorBab : '-';

                if ($skorBab !== null) {
                    $sumNilaiHarian += $skorBab;
                    $countNilaiHarian++;
                }
            }

            $avgHarian = $countNilaiHarian > 0 ? ($sumNilaiHarian / $countNilaiHarian) : 0;
            $porsiHarian = round($avgHarian * 0.70, 1);

            $uasObj = $nilaiGroup->where('jenis', 'uas')->first();
            $skorUas = $uasObj ? ($uasObj->uas ?? 0) : 0;
            $porsiUas = round($skorUas * 0.30, 1);

            $nilaiAkhirMurni = round($porsiHarian + $porsiUas);

            $dataRekap[] = [
                'nama' => $s->nama,
                'nisn' => $s->nisn,
                'nilai_bab_list' => $arrayNilaiPerBab,
                'avg_harian' => round($avgHarian, 1),
                'porsi_harian' => $porsiHarian,
                'uas' => $skorUas,
                'porsi_uas' => $porsiUas,
                'nilai_akhir' => ($countNilaiHarian > 0 || $skorUas > 0) ? $nilaiAkhirMurni : 0,
                'rangking' => 1
            ];
        }

        usort($dataRekap, function($a, $b) {
            return $b['nilai_akhir'] <=> $a['nilai_akhir'];
        });

        foreach ($dataRekap as $index => $data) {
            $dataRekap[$index]['rangking'] = $index + 1;
        }

        if ($request->query('export') === 'excel') {
            $namaFile = 'RINCIAN_NILAI_MAPEL_' . str_replace(' ', '_', strtoupper($mapelInfo->nama_mapel)) . '_KELAS_' . str_replace(' ', '_', strtoupper($kelasInfo->nama_kelas)) . '.xls';
            if (ob_get_contents()) { ob_end_clean(); }
            header("Content-Type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=\"$namaFile\"");
            header("Pragma: no-cache");
            header("Expires: 0");
            return view('wali.rekap_nilai_akhir_mapel', compact('dataRekap', 'listBabTerinput', 'mapelInfo', 'kelasInfo', 'semester', 'tp', 'kkmMapelGuru'));
        }

        if ($request->query('export') === 'pdf') {
            return view('wali.rekap_nilai_akhir_mapel', compact('dataRekap', 'listBabTerinput', 'mapelInfo', 'kelasInfo', 'semester', 'tp', 'kkmMapelGuru'))->with('is_pdf', true);
        }

        return view('wali.rekap_nilai_akhir_mapel', compact('dataRekap', 'listBabTerinput', 'mapelInfo', 'kelasInfo', 'semester', 'tp', 'kkmMapelGuru'));
    }

    public function halamanKkmGuru()
    {
        $user = Auth::user();
        if ($user->role !== 'guru' && $user->role !== 'walikelas') {
            return redirect()->back()->with('error', 'Akses dibatasi hanya untuk Guru Pengampu.');
        }

        $mapelDiampu = Mapel::whereHas('gurus', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with(['gurus' => function($q) use ($user) {
            $q->where('user_id', $user->id);
        }])->get();

        return view('guru.kkm', compact('mapelDiampu'));
    }

    public function storeKkmGuru(Request $request)
    {
        $request->validate([
            'kkm' => 'required|array',
            'kkm.*' => 'required|numeric|min:0|max:100'
        ]);

        $user = Auth::user();

        foreach ($request->kkm as $mapel_id => $nilai_kkm) {
            DB::table('mapel_guru')
                ->where('user_id', $user->id)
                ->where('mapel_id', $mapel_id)
                ->update(['kkm' => $nilai_kkm]);
        }

        return redirect()->back()->with('success', 'Batas standar nilai KKM kompetensi mata pelajaran berhasil diperbarui secara default!');
    }

    public function daftarGuru(Request $request) {
        $search = $request->search;
        $guru = User::whereIn('role', ['guru', 'walikelas'])
            ->when($search, function($query) use ($search) {
                return $query->where('nama', 'LIKE', "%{$search}%");
            })->paginate(10);

        $semuaGuru = User::whereIn('role', ['guru', 'walikelas'])
            ->when($search, function($query) use ($search) {
                return $query->where('nama', 'LIKE', "%{$search}%");
            })->get();

        return view('admin.guru', compact('guru', 'semuaGuru'));
    }

    public function storeJadwal(Request $request) {
        $tipe = $request->input('tipe', 'mingguan');
        $data = $request->all();
        
        if ($request->has('multi_tanggal') && !empty($request->multi_tanggal)) {
            $arrayTanggal = explode(',', $request->multi_tanggal);
            foreach ($arrayTanggal as $tgl) {
                $tgl = trim($tgl);
                if (!empty($tgl)) {
                    $d = $data;
                    $d['tanggal'] = $tgl;
                    $d['hari'] = $this->getHariIndonesia(Carbon::parse($tgl)->format('l'));
                    Jadwal::create($d);
                }
            }
            return back()->with('success', "Jadwal berhasil digandakan!");
        }

        if ($request->tanggal) {
            $data['hari'] = $this->getHariIndonesia(Carbon::parse($request->tanggal)->format('l'));
        }
        Jadwal::create($data);
        return back()->with('success', 'Jadwal berhasil dibuat!');
    }

    public function updateJadwal(Request $request, $id) { 
        $data = $request->all();
        if($request->tanggal) {
            $data['hari'] = $this->getHariIndonesia(Carbon::parse($request->tanggal)->format('l'));
        }
        Jadwal::findOrFail($id)->update($data); 
        return back()->with('success', 'Jadwal diperbarui!'); 
    }

    public function storeGuru(Request $request) {
        $role = $request->role; 
        $prefix = ($role == 'guru') ? 'gr' : 'wkl';
        $lastUser = User::where('username', 'LIKE', $prefix . '%')->orderBy('username', 'desc')->first();
        $newNumber = $lastUser ? ((int) substr($lastUser->username, 3)) + 1 : 1;
        $username = $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        User::create([
            'username' => $username, 
            'nama' => $request->nama,
            'password' => Hash::make('password'), 
            'role' => $role,
            'status' => 1
        ]);
        return back()->with('success', "Data berhasil ditambah! Username: $username");
    }

    public function daftarSiswa(Request $request) {
        $search = $request->search;
        $kelas_id = $request->kelas_id;

        $siswa = User::where('role', 'siswa')
            ->with('kelas')
            ->when($search, function($query) use ($search) {
                return $query->where(function($q) use ($search) {
                    $q->where('nama', 'LIKE', "%{$search}%")
                        ->orWhere('nisn', 'LIKE', "%{$search}%");
                });
            })
            ->when($kelas_id, function($query) use ($kelas_id) {
                return $query->where('kelas_id', $kelas_id);
            })->paginate(10);

        $siswa->appends($request->all());
        $kelas = Kelas::all();
        return view('admin.siswa', compact('siswa', 'kelas'));
    }

    public function storeSiswa(Request $request) {
        if ($request->input('type_input') === 'file_excel') {
            $request->validate([
                'file_siswa' => 'required|file|mimes:csv,txt,xls,xlsx'
            ]);

            $file = $request->file('file_siswa');
            $filePath = $file->getRealPath();

            if (($handle = fopen($filePath, "r")) !== FALSE) {
                $rawFirstLine = fgets($handle);
                
                $delimiter = ","; 
                if (str_contains($rawFirstLine, ';')) {
                    $delimiter = ";";
                }

                rewind($handle);

                fgetcsv($handle, 1000, $delimiter);

                $barisBerhasil = 0;

                while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                    if (isset($data[0]) && isset($data[1]) && trim($data[0]) !== '' && trim($data[1]) !== '') {
                        
                        $nisn = trim(str_replace('"', '', $data[0]));
                        $nama = trim($data[1]);
                        $kelas_id = (isset($data[2]) && trim($data[2]) !== '') ? trim($data[2]) : null;

                        if (strtolower($nisn) === 'nisn' || strtolower($nama) === 'nama') {
                            continue;
                        }

                        $cekSiswa = User::where('nisn', $nisn)->first();
                        if (!$cekSiswa) {
                            $prefix = 'ssw';
                            $lastUser = User::where('username', 'LIKE', $prefix . '%')->orderBy('username', 'desc')->first();
                            $newNumber = $lastUser ? ((int) substr($lastUser->username, 3)) + 1 : 1;
                            $username = $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

                            User::create([
                                'username' => $username,
                                'nama' => $nama,
                                'nisn' => $nisn,
                                'kelas_id' => $kelas_id,
                                'password' => Hash::make('password'), 
                                'role' => 'siswa',
                                'status' => 1
                            ]);

                            $barisBerhasil++;
                        }
                    }
                }
                fclose($handle);

                if ($barisBerhasil > 0) {
                    return back()->with('success', "Berhasil mengimpor <strong>{$barisBerhasil}</strong> data siswa baru ke database!");
                } else {
                    return back()->with('error', "Tidak ada data siswa baru yang berhasil diimpor. Periksa kembali isi file Anda.");
                }
            }

            return back()->with('error', "Gagal membaca struktur file template.");
        }

        $prefix = 'ssw';
        $lastUser = User::where('username', 'LIKE', $prefix . '%')->orderBy('username', 'desc')->first();
        $newNumber = $lastUser ? ((int) substr($lastUser->username, 3)) + 1 : 1;
        $username = $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        User::create([
            'username' => $username, 
            'nama' => $request->nama, 
            'nisn' => $request->nisn,
            'password' => Hash::make('password'), 
            'role' => 'siswa', 
            'status' => 1
        ]);

        return back()->with('success', "Siswa berhasil ditambah! Username: $username");
    }

    public function deleteAllSiswa()
    {
        User::where('role', 'siswa')->delete();
        return back()->with('success', "Seluruh data siswa dalam sistem berhasil dikosongkan!");
    }

    public function plottingGuru() {
        $gurus = User::whereIn('role', ['guru', 'walikelas'])->with('mapels')->get();
        $mapels = Mapel::all();
        return view('admin.plotting_guru', compact('gurus', 'mapels'));
    }

    public function storePlottingGuru(Request $request) {
        $guru = User::findOrFail($request->user_id);
        $guru->mapels()->sync($request->mapel_ids); 
        return back()->with('success', 'Plotting Guru diperbarui!');
    }

    public function getGuruByMapel($mapel_id) {
        $mapel = Mapel::with('gurus')->findOrFail($mapel_id);
        return response()->json($mapel->gurus);
    }

    public function plottingSiswa() { $kelas = Kelas::withCount('siswa')->get(); return view('admin.plotting_index', compact('kelas')); }
    public function detailPlotting($kelas_id) {
        $kelas = Kelas::findOrFail($kelas_id);
        $siswaDiKelas = User::where('role', 'siswa')->where('kelas_id', $kelas_id)->get();
        $siswaTersedia = User::where('role', 'siswa')->whereNull('kelas_id')->get();
        return view('admin.plotting_detail', compact('kelas', 'siswaDiKelas', 'siswaTersedia'));
    }

    public function updatePlotSiswa(Request $request) {
        User::where('id', $request->siswa_id)->update(['kelas_id' => $request->kelas_id]);
        return back()->with('success', 'Plotting siswa diperbarui!');
    }

    public function plottingWaliKelas() {
        $walikelas = User::where('role', 'walikelas')->get();
        $kelas = Kelas::all();
        return view('admin.plotting_walikelas', compact('walikelas', 'kelas'));
    }

    public function updatePlotWaliKelas(Request $request) {
        User::where('id', $request->user_id)->update(['kelas_id' => $request->kelas_id]);
        return back()->with('success', 'Wali Kelas di-plot!');
    }

    public function clearPlotWaliKelas($user_id) {
        User::where('id', $user_id)->update(['kelas_id' => null]);
        return back()->with('success', 'Wali kelas dihapus!');
    }

    public function profil()
    {
        $user = Auth::user();
        return view('user.profil', compact('user'));
    }

    public function updateProfil(Request $request)
    {
        $request->validate([
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'password' => 'nullable|min:6'
        ]);

        $user = User::findOrFail(Auth::id());
        if ($request->filled('password')) { $user->password = Hash::make($request->password); }

        if ($request->hasFile('foto')) {
            if ($user->foto && Storage::disk('public')->exists('profil/' . $user->foto)) {
                Storage::disk('public')->delete('profil/' . $user->foto);
            }
            $file = $request->file('foto');
            $nama_file = time() . "_" . $user->username . "." . $file->getClientOriginalExtension();
            $file->storeAs('profil', $nama_file, 'public'); 
            $user->foto = $nama_file;
        }
        $user->save();
        return back()->with('success', 'Profil Anda berhasil diperbarui!');
    }
    
    // ================= ALTERNATIF CPANEL: DOWNLOAD TEMPLATE ASLI CSV =================
    // ================= SINKRONISASI TOTAL: DOWNLOAD TEMPLATE HARIAN MURNI BERBENTUK TABEL EXCEL =================
    // ================= SINKRONISASI MAATWEBSITE: EXPORT TEMPLATE EXCEL ASLI =================
    // ================= SINKRONISASI TOTAL: DOWNLOAD TEMPLATE EXCEL (.XLSX) ASLI DENGAN DROPDOWN =================
    public function unduhTemplateNilaiHarian(Request $request, $jadwal_id)
    {
        $jadwal = Jadwal::with('kelas.siswa')->findOrFail($jadwal_id);
        $namaFile = 'TEMPLATE_NILAI_HARIAN_KELAS_' . str_replace(' ', '_', strtoupper($jadwal->kelas->nama_kelas)) . '.xlsx';
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'ID_SISWA (JANGAN DIUBAH)');
        $sheet->setCellValue('B1', 'NAMA SISWA');
        $sheet->setCellValue('C1', 'NIS');
        $sheet->setCellValue('D1', 'MODEL NILAI (Tugas/Postest Harian/Postest Bulanan/Ulangan Harian/Praktikum)');
        $sheet->setCellValue('E1', 'NOMOR BAB');
        $sheet->setCellValue('F1', 'NAMA MATERI BAB');
        $sheet->setCellValue('G1', 'PRESENSI (Hadir/Sakit/Izin/Alfa)');
        $sheet->setCellValue('H1', 'NILAI (0-100)');
        
        // AMBIL FILTER DINAMIS: Ambil tanggapan isian input text box halaman web jika terdeteksi, jika kosong pakai default
        $defModel = $request->query('model') ? ucwords($request->query('model')) : 'Tugas';
        $defNoBab = $request->query('no_bab') ? $request->query('no_bab') : '1';
        $defNamaBab = $request->query('nama_bab') ? $request->query('nama_bab') : 'Matriks Dasar';

        $rowIdx = 2;
        foreach ($jadwal->kelas->siswa as $s) {
            $existing = Nilai::where('siswa_id', $s->id)->where('jadwal_id', $jadwal_id)->where('jenis', 'harian')->first();
            $score = $existing ? $existing->mingguan : '';
            $presensi = $existing ? $existing->presensi : 'Hadir';
            
            $sheet->setCellValue('A' . $rowIdx, $s->id);
            $sheet->setCellValue('B' . $rowIdx, strtoupper($s->nama));
            $sheet->setCellValue('C' . $rowIdx, ' ' . $s->nisn); 
            $sheet->setCellValue('D' . $rowIdx, $defModel); // Menyuntikkan model pilihan guru secara dinamis
            $sheet->setCellValue('E' . $rowIdx, $defNoBab); // Menyuntikkan nomor bab pilihan guru secara dinamis
            $sheet->setCellValue('F' . $rowIdx, $defNamaBab); // Menyuntikkan nama materi bab pilihan guru secara dinamis
            $sheet->setCellValue('G' . $rowIdx, $presensi);
            $sheet->setCellValue('H' . $rowIdx, $score);
            $rowIdx++;
        }

        $validation = $sheet->getCell('D2')->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1('"Tugas,Postest Harian,Postest Bulanan,Ulangan Harian,Praktikum"');

        for ($i = 3; $i < $rowIdx; $i++) {
            $sheet->getCell("D{$i}")->setDataValidation(clone $validation);
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        if (ob_get_contents()) { ob_end_clean(); }
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $namaFile . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }

    public function importExcelNilaiHarianAsync(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|file',
            'jadwal_id'  => 'required'
        ]);

        try {
            $jadwal = Jadwal::findOrFail($request->jadwal_id);
            $file = $request->file('file_excel');
            $filePath = $file->getRealPath();

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            $parsedData = [];
            $savedCount = 0;

            for ($rowIdx = 2; $rowIdx <= $highestRow; $rowIdx++) {
                $siswa_id = trim($sheet->getCell('A' . $rowIdx)->getValue());
                if (!$siswa_id || !is_numeric($siswa_id)) { continue; }

                $nama_siswa = trim($sheet->getCell('B' . $rowIdx)->getValue());
                $excelModel = strtolower(trim($sheet->getCell('D' . $rowIdx)->getValue()));
                $model_nilai = in_array($excelModel, ['tugas', 'postest harian', 'postest bulanan', 'ulangan harian', 'praktikum']) ? $excelModel : 'tugas';
                
                $nomor_bab = trim($sheet->getCell('E' . $rowIdx)->getValue());
                $nomor_bab = is_numeric($nomor_bab) ? (int)$nomor_bab : 1;
                
                $nama_bab = trim($sheet->getCell('F' . $rowIdx)->getValue());
                if (empty($nama_bab)) { $nama_bab = 'Materi Bab'; }
                
                $presensiRaw = ucfirst(strtolower(trim($sheet->getCell('G' . $rowIdx)->getValue())));
                $presensi = in_array($presensiRaw, ['Hadir', 'Sakit', 'Izin', 'Alfa']) ? $presensiRaw : 'Hadir';
                
                $nilaiRaw = trim($sheet->getCell('H' . $rowIdx)->getValue());
                $skor = (is_numeric($nilaiRaw) && $nilaiRaw !== '') ? (int)$nilaiRaw : null;

                Nilai::updateOrCreate(
                    [
                        'siswa_id'    => $siswa_id,
                        'jadwal_id'   => $request->jadwal_id,
                        'jenis'       => 'harian'
                    ],
                    [
                        'model_nilai' => $model_nilai,
                        'id_bab'      => $nomor_bab,
                        'nama_bab'    => $nama_bab, 
                        'mingguan'    => $skor, 
                        'presensi'    => $presensi,
                        'tanggal'     => $jadwal->tanggal
                    ]
                );

                $savedCount++;
                $parsedData[] = [
                    'siswa_id'    => (int)$siswa_id,
                    'nama_siswa'  => $nama_siswa,
                    'model_nilai' => $model_nilai,
                    'nomor_bab'   => $nomor_bab,
                    'nama_bab'    => $nama_bab,
                    'presensi'    => $presensi,
                    'nilai'       => $skor !== null ? $skor : ''
                ];
            }

            return response()->json([
                'success'     => true,
                'saved_count' => $savedCount,
                'data'        => $parsedData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membedah spreadsheet berkas: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateUser(Request $request, $id) {
        $user = User::findOrFail($id);
        $roleBaru = $request->role ?? $user->role;
        $username = $user->username;

        if ($user->role !== $roleBaru && in_array($roleBaru, ['guru', 'walikelas'])) {
            $prefix = ($roleBaru == 'guru') ? 'gr' : 'wkl';
            $lastUser = User::where('username', 'LIKE', $prefix . '%')->orderBy('username', 'desc')->first();
            $newNumber = $lastUser ? ((int) substr($lastUser->username, 3)) + 1 : 1;
            $username = $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
        }

        $user->update([
            'username' => $username,
            'nama' => $request->nama, 
            'nisn' => $request->nisn ?? $user->nisn,
            'status' => $request->has('status') ? 1 : 0, 
            'role' => $roleBaru
        ]);
        return back()->with('success', "Data diperbarui!");
    }

    public function getRiwayatNilaiUmat($mapel_id, $kelas_id, $jenis, Request $request) { return $this->getRiwayatNil($mapel_id, $kelas_id, $jenis, $request); }
    public function daftarMapel() { $mapel = Mapel::all(); return view('admin.mapel', compact('mapel')); }
    public function storeMapel(Request $request) { $request->validate(['nama_mapel' => 'required']); Mapel::create(['nama_mapel' => $request->nama_mapel]); return back()->with('success', 'Mapel ditambahkan!'); }
    public function updateMapel(Request $request, $id) { $request->validate(['nama_mapel' => 'required']); Mapel::findOrFail($id)->update(['nama_mapel' => $request->nama_mapel]); return back()->with('success', 'Mata pelajaran berhasil diperbarui!'); }
    public function deleteMapel($id) { Mapel::findOrFail($id)->delete(); return back()->with('success', 'Mata pelajaran berhasil dihapus!'); }
    public function deleteUser($id) { User::findOrFail($id)->delete(); return back()->with('success', 'Data dihapus!'); }
    public function deleteKelas($id) { User::where('kelas_id', $id)->update(['kelas_id' => null]); Kelas::findOrFail($id)->delete(); return back()->with('success', 'Kelas dihapus!'); }
    
    // ================= CETAK RAPOT MURNI BERBENTUK SPREADSHEET EXCEL INDIVIDUAL SISWA =================
    public function exportRapotSiswaExcel(Request $request, $siswa_id)
    {
        $siswa = User::with(['kelas'])->findOrFail($siswa_id);
        $semester = $request->query('semester', '1');
        $tp = $request->query('tahun_pelajaran', '2025/2026');
        
        $existingRapot = Rapot::where('siswa_id', $siswa_id)
            ->where('semester', $semester)
            ->where('tahun_pelajaran', $tp)
            ->first();

        // 1. Ambil Nilai Siswa
        $allNilai = Nilai::where('siswa_id', $siswa_id)
            ->whereHas('jadwal', function($q) use ($semester, $tp) {
                $q->where('semester', $semester)->where('tahun_pelajaran', 'LIKE', "%{$tp}%");
            })->with('jadwal.mapel')->get();

        $rekap_nilai = $allNilai->groupBy(function($item) {
            return trim($item->jadwal->mapel->nama_mapel ?? '');
        });

        // 2. Tentukan Struktur Mapel Berdasarkan Jenis Unit Kelas
        $namaKelasStr = strtoupper($siswa->kelas->nama_kelas ?? '');
        $isKelas10 = str_contains($namaKelasStr, 'X ') || $namaKelasStr === 'X' || str_contains($namaKelasStr, 'KELAS 10') || str_contains($namaKelasStr, '10');
        $faseStr = $isKelas10 ? 'E' : 'F';

        if ($isKelas10) {
            $strukturRapot = [
                'Mata Pelajaran Wajib' => [
                    'Pendidikan Agama dan Budi Pekerti', 'Pendidikan Pancasila', 'Bahasa Indonesia', 'Bahasa Inggris', 'Matematika',
                    'Ilmu Pengetahuan Alam (IPA)', 'Ilmu Pengetahuan Sosial (IPS)', 'Pendidikan Jasmani Olahraga dan Kesehatan', 'Informatika'
                ],
                'Mata Pelajaran Mulok' => ['Basa Sunda', 'Bahasa Arab', 'Science Project']
            ];
        } else {
            $mapelPilihan = str_contains($namaKelasStr, 'IPA') 
                ? ['Matematika Tingkat Lanjut', 'Fisika', 'Kimia', 'Biologi', 'Informatika']
                : ['Sejarah Tingkat Lanjut', 'Geografi', 'Ekonomi', 'Sosiologi', 'Informatika'];
                
            $mulok = str_contains($namaKelasStr, 'IPA') ? ['Basa Sunda', 'Bahasa Arab', 'Science Project'] : ['Basa Sunda', 'Bahasa Arab', 'Desain Grafis'];

            $strukturRapot = [
                'Mata Pelajaran Wajib' => [
                    'Pendidikan Agama dan Budi Pekerti', 'Pendidikan Pancasila', 'Bahasa Indonesia', 'Matematika', 'Bahasa Inggris', 'Sejarah Umum',
                    'Pendidikan Jasmani Olahraga dan Kesehatan', 'Seni Budaya'
                ],
                'Mata Pelajaran Pilihan' => $mapelPilihan,
                'Mata Pelajaran Mulok' => $mulok
            ];
        }

        // 3. Bangun Spreadsheet via PhpSpreadsheet Engine
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Lembar Rapot');

        // Layout Metadata Atas
        $sheet->setCellValue('A1', 'LAPORAN HASIL BELAJAR SUMATIF AKHIR SEMESTER');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $metadata = [
            ['Nama Siswa', ': ' . strtoupper($siswa->nama), 'Kelas', ': ' . ($siswa->kelas->nama_kelas ?? '-')],
            ['NISN', ': ' . $siswa->nisn, 'Fase', ': ' . $faseStr],
            ['Nama Sekolah', ': SMA EL FITRA', 'Semester', ': ' . $semester],
            ['Alamat', ': Jl. Soekarno Hatta No. 04', 'Tahun Pelajaran', ': ' . $tp]
        ];

        $rowIdx = 3;
        foreach ($metadata as $meta) {
            $sheet->setCellValue('A' . $rowIdx, $meta[0]);
            $sheet->setCellValue('B' . $rowIdx, $meta[1]);
            $sheet->setCellValue('C' . $rowIdx, $meta[2]);
            $sheet->setCellValue('D' . $rowIdx, $meta[3]);
            $rowIdx++;
        }

        // Header Tabel Utama Nilai
        $rowIdx += 1;
        $sheet->setCellValue('A' . $rowIdx, 'No');
        $sheet->setCellValue('B' . $rowIdx, 'Mata Pelajaran');
        $sheet->setCellValue('C' . $rowIdx, 'Nilai Akhir');
        $sheet->setCellValue('D' . $rowIdx, 'Capaian Kompetensi');
        
        $sheet->getStyle("A{$rowIdx}:D{$rowIdx}")->getFont()->setBold(true);
        $sheet->getStyle("A{$rowIdx}:D{$rowIdx}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F2F2F2');

        $startTableRows = $rowIdx;
        $globalIdx = 1;
        $rowIdx++;

        // Loop Kategori Kelompok Pelajaran
        foreach ($strukturRapot as $namaKelompok => $listMapel) {
            $sheet->setCellValue('A' . $rowIdx, $namaKelompok);
            $sheet->mergeCells("A{$rowIdx}:D{$rowIdx}");
            $sheet->getStyle('A' . $rowIdx)->getFont()->setBold(true);
            $sheet->getStyle("A{$rowIdx}:D{$rowIdx}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('EAEAEA');
            $rowIdx++;

            foreach ($listMapel as $mapelItem) {
                $mapelKey = trim($mapelItem);
                $nilaiAkhir = '-';
                $deskripsiCapaian = 'Belum ada data nilai harian maupun ujian.';

                // Engine Perhitungan Nilai (Sama dengan Blade Logic)
                if (($mapelKey === 'Ilmu Pengetahuan Alam (IPA)' || $mapelKey === 'Ilmu Pengetahuan Sosial (IPS)') && $isKelas10) {
                    $subMapels = ($mapelKey === 'Ilmu Pengetahuan Alam (IPA)') ? ['Fisika', 'Kimia', 'Biologi'] : ['Sosiologi', 'Ekonomi', 'Sejarah Umum', 'Geografi'];
                    $totalNilaiAkhir = 0; $countValidMapel = 0; $tuntasMateri = []; $belumTuntasMateri = [];

                    foreach ($subMapels as $sub) {
                        if (isset($rekap_nilai[$sub])) {
                            $nilaiGroup = $rekap_nilai[$sub];
                            $valUas = $nilaiGroup->where('jenis', 'uas')->whereNotNull('uas')->first()?->uas;
                            $harianColl = $nilaiGroup->where('jenis', 'harian')->whereNotNull('mingguan');
                            $harianAvg = $harianColl->count() > 0 ? $harianColl->avg('mingguan') : null;

                            if ($harianAvg !== null || $valUas !== null) {
                                $totalNilaiAkhir += round(($harianAvg * 0.70) + ($valUas * 0.30));
                                $countValidMapel++;
                            }
                            $jadwalSampel = $nilaiGroup->first()->jadwal ?? null;
                            $kkmAktif = $jadwalSampel ? (DB::table('mapel_guru')->where('user_id', $jadwalSampel->guru_id)->where('mapel_id', $jadwalSampel->mapel_id)->first()->kkm ?? 75) : 75;

                            foreach ($nilaiGroup->where('jenis', 'harian')->whereNotNull('mingguan')->groupBy('id_bab') as $records) {
                                if ($records->avg('mingguan') >= $kkmAktif) { $tuntasMateri[] = strtolower($records->first()->nama_bab ?? ''); }
                                else { $belumTuntasMateri[] = strtolower($records->first()->nama_bab ?? ''); }
                            }
                        }
                    }
                    if ($countValidMapel > 0) { $nilaiAkhir = round($totalNilaiAkhir / $countValidMapel); }
                    if (count($tuntasMateri) > 0 || count($belumTuntasMateri) > 0) {
                        $textSkenario = "";
                        if (count($tuntasMateri) > 0) { $textSkenario .= "Memahami dengan baik seluruh materi " . implode(', ', $tuntasMateri) . ". "; }
                        if (count($belumTuntasMateri) > 0) { $textSkenario .= "Belum memahami pada materi " . implode(', ', $belumTuntasMateri) . "."; }
                        $deskripsiCapaian = trim($textSkenario);
                    }
                } else {
                    if (isset($rekap_nilai[$mapelKey])) {
                        $nilaiGroup = $rekap_nilai[$mapelKey];
                        $valUas = $nilaiGroup->where('jenis', 'uas')->whereNotNull('uas')->first()?->uas;
                        $harianAvg = $nilaiGroup->where('jenis', 'harian')->whereNotNull('mingguan')->avg('mingguan');
                        if ($harianAvg !== null || $valUas !== null) { $nilaiAkhir = round(($harianAvg * 0.70) + ($valUas * 0.30)); }

                        $jadwalSampel = $nilaiGroup->first()->jadwal ?? null;
                        $kkmAktif = $jadwalSampel ? (DB::table('mapel_guru')->where('user_id', $jadwalSampel->guru_id)->where('mapel_id', $jadwalSampel->mapel_id)->first()->kkm ?? 75) : 75;
                        $babTuntas = []; $babBelumTuntas = [];

                        foreach ($nilaiGroup->where('jenis', 'harian')->whereNotNull('mingguan')->groupBy('id_bab') as $records) {
                            if ($records->avg('mingguan') >= $kkmAktif) { $babTuntas[] = strtolower($records->first()->nama_bab ?? ''); }
                            else { $babBelumTuntas[] = strtolower($records->first()->nama_bab ?? ''); }
                        }
                        if (count($babTuntas) > 0 || count($babBelumTuntas) > 0) {
                            $textSkenario = "";
                            if (count($babTuntas) > 0) { $textSkenario .= "Memahami dengan baik seluruh kompetensi dasar pada materi " . implode(', ', $babTuntas) . ". "; }
                            if (count($babBelumTuntas) > 0) { $textSkenario .= "Belum memahami pada materi " . implode(', ', $babBelumTuntas) . "."; }
                            $deskripsiCapaian = trim($textSkenario);
                        }
                    }
                }

                $sheet->setCellValue('A' . $rowIdx, $globalIdx++);
                $sheet->setCellValue('B' . $rowIdx, $mapelItem);
                $sheet->setCellValue('C' . $rowIdx, $nilaiAkhir);
                $sheet->setCellValue('D' . $rowIdx, $deskripsiCapaian);
                $rowIdx++;
            }
        }

        // Border Seluruh Tabel Utama
        $styleBorder = [
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']]
            ]
        ];
        $sheet->getStyle("A{$startTableRows}:D" . ($rowIdx - 1))->applyFromArray($styleBorder);

        // Bagian Kokurikuler & Ekstrakurikuler
        $rowIdx += 1;
        $sheet->setCellValue('A' . $rowIdx, 'KOKURIKULER');
        $sheet->mergeCells("A{$rowIdx}:D{$rowIdx}");
        $sheet->getStyle('A' . $rowIdx)->getFont()->setBold(true);
        $rowIdx++;
        $sheet->setCellValue('A' . $rowIdx, $existingRapot ? $existingRapot->kokurikuler : '-');
        $sheet->mergeCells("A{$rowIdx}:D{$rowIdx}");
        $sheet->getStyle("A{$rowIdx}:D{$rowIdx}")->applyFromArray($styleBorder);

        $rowIdx += 2;
        $sheet->setCellValue('A' . $rowIdx, 'KEGIATAN EKSTRAKURIKULER');
        $sheet->mergeCells("A{$rowIdx}:D{$rowIdx}");
        $sheet->getStyle('A' . $rowIdx)->getFont()->setBold(true);
        $rowIdx++;
        
        $sheet->setCellValue('A' . $rowIdx, 'No');
        $sheet->setCellValue('B' . $rowIdx, 'Kegiatan Ekstrakurikuler');
        $sheet->setCellValue('C' . $rowIdx, 'Predikat');
        $sheet->setCellValue('D' . $rowIdx, 'Keterangan');
        $sheet->getStyle("A{$rowIdx}:D{$rowIdx}")->getFont()->setBold(true);
        $sheet->getStyle("A{$rowIdx}:D{$rowIdx}")->applyFromArray($styleBorder);
        $rowIdx++;

        $sheet->setCellValue('A' . $rowIdx, '1');
        $sheet->setCellValue('B' . $rowIdx, $existingRapot?->eskul_nama1 ?: '-');
        $sheet->setCellValue('C' . $rowIdx, $existingRapot?->eskul_nama1 ? $existingRapot->eskul_pred1 : '-');
        $sheet->setCellValue('D' . $rowIdx, $existingRapot?->eskul_nama1 ? $existingRapot->eskul_desc1 : '-');
        $sheet->getStyle("A{$rowIdx}:D{$rowIdx}")->applyFromArray($styleBorder);
        $rowIdx++;

        $sheet->setCellValue('A' . $rowIdx, '2');
        $sheet->setCellValue('B' . $rowIdx, $existingRapot?->eskul_nama2 ?: '-');
        $sheet->setCellValue('C' . $rowIdx, $existingRapot?->eskul_nama2 ? $existingRapot->eskul_pred2 : '-');
        $sheet->setCellValue('D' . $rowIdx, $existingRapot?->eskul_nama2 ? $existingRapot->eskul_desc2 : '-');
        $sheet->getStyle("A{$rowIdx}:D{$rowIdx}")->applyFromArray($styleBorder);

        // Ketidakhadiran & Catatan Wali Kelas
        $rowIdx += 2;
        $sheet->setCellValue('A' . $rowIdx, 'KETIDAKHADIRAN');
        $sheet->setCellValue('C' . $rowIdx, 'CATATAN WALI KELAS');
        $sheet->getStyle("A{$rowIdx}")->getFont()->setBold(true);
        $sheet->getStyle("C{$rowIdx}")->getFont()->setBold(true);
        $rowIdx++;

        $sheet->setCellValue('A' . $rowIdx, 'Sakit');
        $sheet->setCellValue('B' . $rowIdx, ($existingRapot && $existingRapot->abs_sakit) ? $existingRapot->abs_sakit . ' Hari' : '-');
        $sheet->setCellValue('C' . $rowIdx, $existingRapot?->catatan_wali ?: '-');
        $sheet->mergeCells("C{$rowIdx}:D" . ($rowIdx + 2));
        $sheet->getStyle("C{$rowIdx}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP)->setWrapText(true);
        $sheet->getStyle("A{$rowIdx}:B{$rowIdx}")->applyFromArray($styleBorder);
        $rowIdx++;

        $sheet->setCellValue('A' . $rowIdx, 'Izin');
        $sheet->setCellValue('B' . $rowIdx, ($existingRapot && $existingRapot->abs_izin) ? $existingRapot->abs_izin . ' Hari' : '-');
        $sheet->getStyle("A{$rowIdx}:B{$rowIdx}")->applyFromArray($styleBorder);
        $rowIdx++;

        $sheet->setCellValue('A' . $rowIdx, 'Alfa');
        $sheet->setCellValue('B' . $rowIdx, ($existingRapot && $existingRapot->abs_alfa) ? $existingRapot->abs_alfa . ' Hari' : '-');
        $sheet->getStyle("A{$rowIdx}:B{$rowIdx}")->applyFromArray($styleBorder);
        
        $sheet->getStyle("C" . ($rowIdx - 2) . ":D" . $rowIdx)->applyFromArray($styleBorder);

        // AutoSize
        foreach (range('A', 'D') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

        $writer = new Xlsx($spreadsheet);
        $namaFile = 'RAPOT_EXCEL_' . str_replace(' ', '_', strtoupper($siswa->nama)) . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $namaFile . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
    
    private function getHariIndonesia($dayName)
    {
        $daftarHari = [
            'Sunday'    => 'Minggu',
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu'
        ];

        return $daftarHari[$dayName] ?? $dayName;
    }
}