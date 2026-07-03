@extends('layouts.main')

@section('content')
<div class="d-print-none">
    {{-- SINKRONISASI NOTIFIKASI MONITORING PROGRESS COOTA INPUT GURU KELAS --}}
    @if($kelas_id)
        <div class="card border-0 shadow-sm mb-4 bg-light border-start border-4 {{ $raportBisaDicetak ? 'border-success' : 'border-warning' }}">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi {{ $raportBisaDicetak ? 'bi-check-circle-fill text-success' : 'bi-shield-exclamation text-warning' }} fs-5"></i>
                    <h6 class="m-0 fw-bold text-dark">
                        {{ $raportBisaDicetak ? 'Seluruh Aturan Beban Kerja Terpenuhi!' : 'Kontrol Progress Pemenuhan Batas Wajib Minimal 1 Input Nilai Per Mata Pelajaran' }}
                    </h6>
                </div>
                <p class="text-muted small mb-3">
                    Sesuai aturan kurikulum, berkas cetak rapot unit kelas baru dapat dibuka penuh apabila masing-masing guru pengampu mata pelajaran telah memenuhi minimal 1 kali instalan input harian terpisah serta melengkapi Nilai UAS.
                </p>
                
                <div class="row g-2">
                    @php
                        $plotIndikator = \App\Models\Jadwal::where('kelas_id', $kelas_id)
                            ->where('semester', request('semester', '1'))
                            ->where('tahun_pelajaran', 'LIKE', "%".request('tahun_pelajaran', '2025/2026')."%")
                            ->select('guru_id', 'mapel_id')
                            ->groupBy('guru_id', 'mapel_id')
                            ->with(['guru', 'mapel'])
                            ->get();
                    @endphp
                    @foreach($plotIndikator as $pInd)
                        @php
                            $subJadwalIds = \App\Models\Jadwal::where('kelas_id', $kelas_id)
                                ->where('guru_id', $pInd->guru_id)
                                ->where('mapel_id', $pInd->mapel_id)
                                ->where('semester', request('semester', '1'))
                                ->where('tahun_pelajaran', 'LIKE', "%".request('tahun_pelajaran', '2025/2026')."%")
                                ->pluck('id');
                            
                            $subCount = \App\Models\Nilai::whereIn('jadwal_id', $subJadwalIds)
                                ->where('jenis', 'harian')
                                ->whereNotNull('mingguan')
                                ->select('jadwal_id')
                                ->groupBy('jadwal_id')
                                ->get()
                                ->count();
                                
                            $subUasTerisi = \App\Models\Nilai::whereIn('jadwal_id', $subJadwalIds)
                                ->where('jenis', 'uas')
                                ->whereNotNull('uas')
                                ->exists();
                        @endphp
                        <div class="col-md-6 col-lg-4">
                            <div class="p-2.5 rounded bg-white border d-flex justify-content-between align-items-center h-100 shadow-sm">
                                <div style="max-width: 65%;">
                                    <span class="small fw-bold text-dark d-block text-truncate">{{ $pInd->mapel->nama_mapel }}</span>
                                    <small class="text-muted text-truncate d-block" style="font-size: 11px;">Oleh: {{ $pInd->guru->nama }}</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge {{ $subCount >= 1 ? 'bg-success' : 'bg-warning text-dark' }} font-weight-bold px-2 py-1">
                                        {{ $subCount }} / 1 Input
                                    </span>
                                    
                                    @if(!$subUasTerisi)
                                        <span class="badge bg-danger text-white d-block mt-1" style="font-size: 9px; padding: 2px 4px;">UAS Belum Diisi</span>
                                    @else
                                        <span class="badge bg-success text-white d-block mt-1" style="font-size: 9px; padding: 2px 4px;">UAS Selesai</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h5 class="fw-bold m-0"><i class="bi bi-file-earmark-bar-graph me-2 text-primary"></i>Laporan Cetak Hasil Belajar Siswa</h5>
        </div>
        
        @if($kelas_id)
        <div class="d-flex align-items-center gap-1.5">
            <a href="#" onclick="unduhTemplateRapotKelasDinamis(event, '{{ $kelas_id }}')" class="btn btn-sm btn-success fw-bold text-white px-3 shadow-sm">
                <i class="bi bi-download me-1"></i> Unduh Template Rapot Kelas
            </a>
            <div class="position-relative">
                <button type="button" class="btn btn-sm btn-dark fw-bold px-3 shadow-sm" onclick="document.getElementById('excel_rapot_massal').click()">
                    <i class="bi bi-upload me-1"></i> Impor Rapot Massal (.xlsx)
                </button>
                <input type="file" id="excel_rapot_massal" class="d-none" onchange="uploadRapotMassalExcel('{{ $kelas_id }}')" accept=".xlsx, .xls">
            </div>
        </div>
        @endif
    </div>

    <div class="card border-0 shadow-sm p-3 mb-4 bg-light">
        <form action="{{ route('admin.laporan') }}" method="GET" id="form-filter-laporan" class="row g-2">
            <div class="col-md-3">
                <label class="small fw-bold text-muted">Semester</label>
                <select name="semester" id="filter_semester" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="1" {{ request('semester') == '1' || !request('semester') ? 'selected' : '' }}>Semester 1</option>
                    <option value="2" {{ request('semester') == '2' ? 'selected' : '' }}>Semester 2</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="small fw-bold text-muted">Tahun Pelajaran</label>
                <select name="tahun_pelajaran" id="filter_tp" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach(['2025/2026', '2026/2027', '2027/2028', '2028/2029', '2029/2030'] as $tp_opt)
                        <option value="{{ $tp_opt }}" {{ request('tahun_pelajaran', '2025/2026') == $tp_opt ? 'selected' : '' }}>{{ $tp_opt }}</option>
                    @endforeach
                </select>
            </div>
            
            @if(Auth::user()->role !== 'walikelas')
            <div class="col-md-3">
                <label class="small fw-bold text-muted">Filter Unit Kelas (Admin Only)</label>
                <select name="kelas_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Pilih Kelas --</option>
                    @foreach($kelas as $k)
                        <option value="{{ $k->id }}" {{ $kelas_id == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-2 d-flex align-items-end">
                <a href="{{ route('admin.laporan') }}" class="btn btn-sm btn-outline-secondary w-100">Reset Filter</a>
            </div>
        </form>
    </div>

    @if($kelas_id)
    <div class="card border-0 p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="5%">No</th>
                        <th>NIS</th>
                        <th>Nama Lengkap Siswa</th>
                        <th>Kelas</th>
                        <th class="text-center" width="40%">Aksi Manajemen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($siswa as $s)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $s->nisn }}</td>
                        <td class="fw-bold text-uppercase">{{ $s->nama }}</td>
                        <td><span class="badge bg-info text-dark">{{ $s->kelas->nama_kelas ?? '-' }}</span></td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" id="btn-rapot-{{ $s->id }}" 
                                        class="btn btn-sm {{ $s->rapot_kokurikuler ? 'btn-success text-white' : 'btn-outline-primary' }} px-2" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalLengkap-{{ $s->id }}">
                                    <i class="bi bi-pencil-square me-1"></i> <span class="btn-text">{{ $s->rapot_kokurikuler ? 'Edit Data Rapot' : 'Isi Data Rapot' }}</span>
                                </button>
                                
                                @if($raportBisaDicetak)
                                    <button onclick="printRaport('raport-{{ $s->id }}')" class="btn btn-sm btn-primary px-2">
                                        <i class="bi bi-printer-fill me-1"></i> Cetak A4
                                    </button>
                                    <a href="{{ route('admin.laporan.export_excel_siswa', [$s->id]) }}?semester={{ request('semester', '1') }}&tahun_pelajaran={{ request('tahun_pelajaran', '2025/2026') }}" class="btn btn-sm btn-success text-white px-2">
                                        <i class="bi bi-file-earmark-excel-fill me-1"></i> Excel Rapot
                                    </a>
                                @else
                                    <button class="btn btn-sm btn-secondary px-2" disabled>
                                        <i class="bi bi-lock-fill me-1"></i> Terkunci
                                    </button>
                                    <button type="button" onclick="bukaPreviewLapot('raport-{{ $s->id }}')" class="btn btn-sm btn-danger px-2 fw-bold shadow-sm">
                                        <i class="bi bi-eye-fill me-1"></i> View
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>

                    {{-- MODAL INTEGRASI INPUT MANUAL WALI KELAS --}}
                    <div class="modal fade modal-parent-node" id="modalLengkap-{{ $s->id }}" data-siswa="{{ $s->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-dark text-white">
                                    <h6 class="modal-title fw-bold text-uppercase" style="font-size: 13px;">Kelola Lembar Rapot Manual: {{ $s->nama }}</h6>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body" style="max-height: 520px; overflow-y: auto;">
                                    
                                    <h6 class="fw-bold text-primary mb-2 border-bottom pb-1 small"><i class="bi bi-journal-bookmark-fill me-1"></i> KOKURIKULER</h6>
                                    <div class="mb-3">
                                        <textarea id="in_kurikuler_{{ $s->id }}" class="form-control form-control-sm text-box-koku" rows="2" placeholder="Tulis catatan kurikuler memanjang horizontal di sini...">{{ $s->rapot_kokurikuler }}</textarea>
                                    </div>

                                    <h6 class="fw-bold text-success mb-2 border-bottom pb-1 small"><i class="bi bi-star-fill me-1"></i> Kegiatan Ekstrakurikuler (Maksimal 2)</h6>
                                    <div class="row g-2 mb-3" id="eskul-container-{{ $s->id }}">
                                        <div class="col-12">
                                            <div class="row g-2">
                                                <div class="col-md-5">
                                                    <input type="text" id="in_eskul_nama_1_{{ $s->id }}" class="form-control form-control-sm text-eskul-name1" value="{{ $s->rapot_eskul_nama1 }}" placeholder="Nama Ekstrakurikuler 1">
                                                </div>
                                                <div class="col-md-2">
                                                    <select id="in_eskul_predikat_1_{{ $s->id }}" class="form-select form-select-sm select-eskul-pred1">
                                                        <option value="A" {{ $s->rapot_eskul_pred1 == 'A' ? 'selected' : '' }}>A (Sangat Baik)</option>
                                                        <option value="B" {{ $s->rapot_eskul_pred1 == 'B' ? 'selected' : '' }}>B (Baik)</option>
                                                        <option value="C" {{ $s->rapot_eskul_pred1 == 'C' ? 'selected' : '' }}>C (Cukup)</option>
                                                        <option value="D" {{ $s->rapot_eskul_pred1 == 'D' ? 'selected' : '' }}>D (Kurang)</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-5">
                                                    <input type="text" id="in_eskul_desc_1_{{ $s->id }}" class="form-control form-control-sm text-eskul-desc1" value="{{ $s->rapot_eskul_desc1 }}" placeholder="Deskripsi kemajuan eskul 1...">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="row g-2">
                                                <div class="col-md-5">
                                                    <input type="text" id="in_eskul_nama_2_{{ $s->id }}" class="form-control form-control-sm text-eskul-name2" value="{{ $s->rapot_eskul_nama2 }}" placeholder="Nama Ekstrakurikuler 2 (Opsional)">
                                                </div>
                                                <div class="col-md-2">
                                                    <select id="in_eskul_predikat_2_{{ $s->id }}" class="form-select form-select-sm select-eskul-pred2">
                                                        <option value="A" {{ $s->rapot_eskul_pred2 == 'A' ? 'selected' : '' }}>A (Sangat Baik)</option>
                                                        <option value="B" {{ $s->rapot_eskul_pred2 == 'B' ? 'selected' : '' }}>B (Baik)</option>
                                                        <option value="C" {{ $s->rapot_eskul_pred2 == 'C' ? 'selected' : '' }}>C (Cukup)</option>
                                                        <option value="D" {{ $s->rapot_eskul_pred2 == 'D' ? 'selected' : '' }}>D (Kurang)</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-5">
                                                    <input type="text" id="in_eskul_desc_2_{{ $s->id }}" class="form-control form-control-sm text-eskul-desc2" value="{{ $s->rapot_eskul_desc2 }}" placeholder="Deskripsi kemajuan eskul 2...">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <h6 class="fw-bold text-warning mb-2 border-bottom pb-1 small"><i class="bi bi-clock-history me-1"></i> Rekap Absen Kehadiran</h6>
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-4">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Sakit</span>
                                                <input type="number" id="in_absen_sakit_{{ $s->id }}" class="form-control num-abs-sakit" value="{{ $s->rapot_abs_sakit }}">
                                                <span class="input-group-text">Hari</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Izin</span>
                                                <input type="number" id="in_absen_izin_{{ $s->id }}" class="form-control num-abs-izin" value="{{ $s->rapot_abs_izin }}">
                                                <span class="input-group-text">Hari</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Alfa</span>
                                                <input type="number" id="in_absen_alfa_{{ $s->id }}" class="form-control num-abs-alfa" value="{{ $s->rapot_abs_alfa }}">
                                                <span class="input-group-text">Hari</span>
                                            </div>
                                        </div>
                                    </div>

                                    <h6 class="fw-bold text-danger mb-2 border-bottom pb-1 small"><i class="bi bi-chat-left-text-fill me-1"></i> Catatan Wali Kelas</h6>
                                    <div class="mb-2">
                                        <textarea id="in_catatan_wali_{{ $s->id }}" class="form-control form-control-sm text-box-catatan" rows="3" placeholder="Tulis evaluasi motivasi belajar siswa di sini...">{{ $s->rapot_catatan_wali }}</textarea>
                                    </div>
                                </div>
                                <div class="modal-footer bg-light py-2">
                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="button" onclick="singkronisasiDataLaporan('{{ $s->id }}')" class="btn btn-sm btn-success" data-bs-dismiss="modal">
                                        <i class="bi bi-check-circle me-1"></i> Terapkan & Save Permanen
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada data siswa ditemukan di kelas ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @else
        <div class="alert alert-warning text-center"><i class="bi bi-exclamation-triangle me-2"></i>Silakan hubungi admin untuk melakukan plot kelas perwalian Anda.</div>
    @endif
</div>

{{-- PANEL UTAMA PREVIEW LAYAR MONITOR --}}
<div id="container-preview-layar" class="d-print-none p-4 bg-secondary rounded mb-4 d-none" style="background-color: #6c757d !important;">
    <div class="d-flex justify-content-between align-items-center mb-3 text-white border-bottom pb-2">
        <span class="small fw-bold"><i class="bi bi-display me-2"></i> Mode Pratinjau Layar Monitor</span>
        <button type="button" class="btn btn-sm btn-light py-0 px-2 fw-bold" onclick="tutupPreviewLayarRapot()">Tutup Preview</button>
    </div>
    <div class="bg-white p-4 shadow mx-auto" style="max-width: 800px; border: 1px solid #ccc;">
        <div id="wrapper-render-preview"></div>
    </div>
</div>

{{-- AREA CETAK RAPOT MURNI BERUKURAN KERTAS A4 --}}
<div id="print-area" class="d-none d-print-block">
    @foreach($siswa as $s)
    @php
        $displaySemester = request('semester', '1');
        $displayTP = request('tahun_pelajaran', '2025/2026');
        $namaKelasStr = strtoupper($s->kelas->nama_kelas ?? '');
        
        $isKelas10 = false;
        $faseStr = 'E';
        if (str_contains($namaKelasStr, 'X ') || $namaKelasStr === 'X' || str_contains($namaKelasStr, 'KELAS 10') || str_contains($namaKelasStr, 'KELAS X' ) || str_contains($namaKelasStr, '10')) {
            $isKelas10 = true;
            $faseStr = 'E';
        } elseif (str_contains($namaKelasStr, 'XI ') || str_contains($namaKelasStr, 'KELAS 11') || str_contains($namaKelasStr, 'KELAS XI') || str_contains($namaKelasStr, '11')) {
            $isKelas10 = false;
            $faseStr = 'F';
        } elseif (str_contains($namaKelasStr, 'XII') || str_contains($namaKelasStr, 'KELAS 12') || str_contains($namaKelasStr, '12')) {
            $isKelas10 = false;
            $faseStr = 'F';
        }
        
        $strukturRapot = [];
        if ($isKelas10) {
            $strukturRapot = [
                'Mata Pelajaran Wajib' => [
                    'Pendidikan Agama dan Budi Pekerti', 'Pendidikan Pancasila', 'Bahasa Indonesia', 'Bahasa Inggris', 'Matematika',
                    'Ilmu Pengetahuan Alam (IPA)', 'Ilmu Pengetahuan Sosial (IPS)',
                    'Pendidikan Jasmani Olahraga dan Kesehatan', 'Informatika'
                ],
                'Mata Pelajaran Mulok' => [
                     'Basa Sunda', 'Bahasa Arab', 'Science Project'
                ]
            ];
        } else {
            if (str_contains($namaKelasStr, 'IPA')) {
                $strukturRapot = [
                    'Mata Pelajaran Wajib' => [
                        'Pendidikan Agama dan Budi Pekerti', 'Pendidikan Pancasila', 'Bahasa Indonesia', 'Matematika', 'Bahasa Inggris', 'Sejarah Umum',
                        'Pendidikan Jasmani Olahraga dan Kesehatan', 'Seni Budaya'
                    ],
                    'Mata Pelajaran Pilihan' => [
                        'Matematika Tingkat Lanjut', 'Fisika', 'Kimia', 'Biologi', 'Informatika'
                    ],
                    'Mata Pelajaran Mulok' => [
                        'Basa Sunda', 'Bahasa Arab', 'Science Project'
                    ]
                ];
            } else {
                $strukturRapot = [
                    'Mata Pelajaran Wajib' => [
                        'Pendidikan Agama dan Budi Pekerti', 'Pendidikan Pancasila', 'Bahasa Indonesia', 'Matematika', 'Bahasa Inggris', 'Sejarah Umum',
                        'Pendidikan Jasmani Olahraga dan Kesehatan', 'Seni Budaya'
                    ],
                    'Mata Pelajaran Pilihan' => [
                        'Sejarah Tingkat Lanjut', 'Geografi', 'Ekonomi', 'Sosiologi', 'Informatika'
                    ],
                    'Mata Pelajaran Mulok' => [
                        'Basa Sunda', 'Bahasa Arab', 'Desain Grafis'
                    ]
                ];
            }
        }
    @endphp
   
    <div id="raport-{{ $s->id }}" class="raport-page origin-raport-node" data-siswa-id="{{ $s->id }}">
        
        {{-- ==================== HALAMAN 1 (UNIVERSAL) ==================== --}}
        <div class="page-container-A4">
            {{-- IDENTITAS METADATA SISWA --}}
            <table class="w-100 mb-3 student-metadata-table" style="font-size: 11.5px; line-height: 1.4;">
                <tr>
                    <td width="18%">Nama Siswa</td>
                    <td width="2%">:</td>
                    <td width="45%" class="text-uppercase">{{ $s->nama }}</td>
                    <td width="15%">Kelas</td>
                    <td width="2%">:</td>
                    <td width="18%">{{ $s->kelas->nama_kelas ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Nomor Induk Siswa</td>
                    <td>:</td>
                    <td>{{ $s->nisn }}</td>
                    <td>Fase</td>
                    <td>:</td>
                    <td>{{ $faseStr }}</td>
                </tr>
                <tr>
                    <td>Nama Sekolah</td>
                    <td>:</td>
                    <td>SMA EL FITRA</td>
                    <td>Semester</td>
                    <td>:</td>
                    <td>{{ $displaySemester }} ({{ $displaySemester == '1' ? 'Ganjil' : 'Genap' }})</td>
                </tr>
                <tr>
                    <td>Alamat Sekolah</td>
                    <td>:</td>
                    <td>Jl. Soekarno Hatta No. 04 Kota Bandung</td>
                    <td>Tahun Pelajaran</td>
                    <td>:</td>
                    <td>{{ $displayTP }}</td>
                </tr>
            </table>

            <div class="text-center header-title-block">
                <h4 class="fw-bold m-0 text-uppercase" style="font-size: 14px; letter-spacing: 0.5px;">
                    LAPORAN HASIL BELAJAR SUMATIF AKHIR SEMESTER {{ $displaySemester }}
                </h4>
            </div>

            <div class="text-uppercase mb-1 section-title" style="font-weight: bold !important; font-size: 13px;">PENGETAHUAN DAN KETERAMPILAN</div>
            
            {{-- TABEL UTAMA HALAMAN 1 --}}
            <table class="table-raport mb-3 text-split-target">
                <colgroup>
                    <col style="width: 5%;">
                    <col style="width: 37%;">
                    <col style="width: 13%;">
                    <col style="width: 45%;">
                </colgroup>
                <thead>
                    <tr class="text-center" style="font-size: 13.5px; font-weight: bold;">
                        <th>No</th>
                        <th>Mata Pelajaran</th>
                        <th>Nilai Akhir</th>
                        <th>Capaian Kompetensi</th>
                    </tr>
                </thead>
                <tbody>
                    @php $globalIdx = 1; @endphp
                    @foreach($strukturRapot as $namaKelompok => $listMapel)
                        {{-- JIKA KELAS XI/XII DAN MASUK MULOK, JANGAN RENDERING DI HALAMAN 1 --}}
                        @if(!$isKelas10 && $namaKelompok === 'Mata Pelajaran Mulok')
                            @continue
                        @endif

                        <tr>
                            <td colspan="4" class="text-start text-uppercase py-2 ps-2 subheader-row" style="font-size: 12px; font-weight: bold; background-color: #f5f5f5 !important; border: 1px solid #000000 !important;">
                                {{ $namaKelompok }}
                            </td>
                        </tr>

                        @foreach($listMapel as $mapelItem)
                            @php
                                $mapelKey = trim($mapelItem);
                                $nilaiAkhir = '-';
                                $deskripsiCapaian = 'Belum ada data nilai harian maupun ujian.';
                               
                                if ($mapelKey === 'Ilmu Pengetahuan Alam (IPA)' && $isKelas10) {
                                    $subMapels = ['Fisika', 'Kimia', 'Biologi'];
                                    $totalNilaiAkhir = 0; $countValidMapel = 0; $tuntasMateri = []; $belumTuntasMateri = [];
                                   
                                    foreach ($subMapels as $sub) {
                                        if (isset($s->rekap_nilai[$sub])) {
                                            $nilaiGroup = $s->rekap_nilai[$sub];
                                            $valUas = $nilaiGroup->where('jenis', 'uas')->whereNotNull('uas')->first()?->uas;
                                            $harianColl = $nilaiGroup->where('jenis', 'harian')->whereNotNull('mingguan');
                                            $harianAvg = $harianColl->count() > 0 ? $harianColl->avg('mingguan') : null;
                                           
                                            if ($harianAvg !== null || $valUas !== null) {
                                                $totalNilaiAkhir += round((($harianAvg ?? 0) * 0.70) + (($valUas ?? 0) * 0.30));
                                                $countValidMapel++;
                                            }
                                            $jadwalSampel = $nilaiGroup->first()->jadwal ?? null;
                                            $kkmAktif = $jadwalSampel ? (Illuminate\Support\Facades\DB::table('mapel_guru')->where('user_id', $jadwalSampel->guru_id)->where('mapel_id', $jadwalSampel->mapel_id)->first()->kkm ?? 75) : 75;
                                           
                                            foreach ($nilaiGroup->where('jenis', 'harian')->whereNotNull('mingguan')->groupBy('id_bab') as $records) {
                                                if ($records->avg('mingguan') >= $kkmAktif) { $tuntasMateri[] = strtolower($records->first()->nama_bab ?? ''); }
                                                else { $belumTuntasMateri[] = strtolower($records->first()->nama_bab ?? ''); }
                                            }
                                        }
                                    }
                                    if ($countValidMapel > 0) { $nilaiAkhir = round($totalNilaiAkhir / $countValidMapel); }
                                    if (count($tuntasMateri) > 0 || count($belumTuntasMateri) > 0) {
                                        $textSkenario = "";
                                        if (count($tuntasMateri) > 0) { $textSkenario .= "Memahami dengan baik seluruh kompetensi dasar pada materi " . implode(', ', $tuntasMateri) . ". "; }
                                        if (count($belumTuntasMateri) > 0) { $textSkenario .= "Belum memahami pada materi " . implode(', ', $belumTuntasMateri) . "."; }
                                        $deskripsiCapaian = trim($textSkenario);
                                    }
                                }
                                elseif ($mapelKey === 'Ilmu Pengetahuan Sosial (IPS)' && $isKelas10) {
                                    $subMapels = ['Sosiologi', 'Ekonomi', 'Sejarah Umum', 'Geografi'];
                                    $totalNilaiAkhir = 0; $countValidMapel = 0; $tuntasMateri = []; $belumTuntasMateri = [];
                                   
                                    foreach ($subMapels as $sub) {
                                        if (isset($s->rekap_nilai[$sub])) {
                                            $nilaiGroup = $s->rekap_nilai[$sub];
                                            $valUas = $nilaiGroup->where('jenis', 'uas')->whereNotNull('uas')->first()?->uas;
                                            $harianColl = $nilaiGroup->where('jenis', 'harian')->whereNotNull('mingguan');
                                            $harianAvg = $harianColl->count() > 0 ? $harianColl->avg('mingguan') : null;
                                           
                                            if ($harianAvg !== null || $valUas !== null) {
                                                $totalNilaiAkhir += round((($harianAvg ?? 0) * 0.70) + (($valUas ?? 0) * 0.30));
                                                $countValidMapel++;
                                            }
                                            $jadwalSampel = $nilaiGroup->first()->jadwal ?? null;
                                            $kkmAktif = $jadwalSampel ? (Illuminate\Support\Facades\DB::table('mapel_guru')->where('user_id', $jadwalSampel->guru_id)->where('mapel_id', $jadwalSampel->mapel_id)->first()->kkm ?? 75) : 75;
                                           
                                            foreach ($nilaiGroup->where('jenis', 'harian')->whereNotNull('mingguan')->groupBy('id_bab') as $records) {
                                                if ($records->avg('mingguan') >= $kkmAktif) { $tuntasMateri[] = strtolower($records->first()->nama_bab ?? ''); }
                                                else { $belumTuntasMateri[] = strtolower($records->first()->nama_bab ?? ''); }
                                            }
                                        }
                                    }
                                    if ($countValidMapel > 0) { $nilaiAkhir = round($totalNilaiAkhir / $countValidMapel); }
                                    if (count($tuntasMateri) > 0 || count($belumTuntasMateri) > 0) {
                                        $textSkenario = "";
                                        if (count($tuntasMateri) > 0) { $textSkenario .= "Memahami dengan baik seluruh kompetensi dasar pada materi " . implode(', ', $tuntasMateri) . ". "; }
                                        if (count($belumTuntasMateri) > 0) { $textSkenario .= "Belum memahami pada materi " . implode(', ', $belumTuntasMateri) . "."; }
                                        $deskripsiCapaian = trim($textSkenario);
                                    }
                                }
                                else {
                                    if (isset($s->rekap_nilai[$mapelKey])) {
                                        $nilaiGroup = $s->rekap_nilai[$mapelKey];
                                        $valUas = $nilaiGroup->where('jenis', 'uas')->whereNotNull('uas')->first()?->uas;
                                        $harianAverage = $nilaiGroup->where('jenis', 'harian')->whereNotNull('mingguan')->avg('mingguan');
                                       
                                        if ($harianAverage !== null || $valUas !== null) {
                                            $nilaiAkhir = round((($harianAverage ?? 0) * 0.70) + (($valUas ?? 0) * 0.30));
                                        }

                                        $jadwalSampel = $nilaiGroup->first()->jadwal ?? null;
                                        $kkmAktif = $jadwalSampel ? (Illuminate\Support\Facades\DB::table('mapel_guru')->where('user_id', $jadwalSampel->guru_id)->where('mapel_id', $jadwalSampel->mapel_id)->first()->kkm ?? 75) : 75;
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
                            @endphp

                            <tr>
                                <td class="text-center" style="font-size: 11.2px; padding: 7px; border: 1px solid #000000 !important;">{{ $globalIdx++ }}</td>
                                <td class="ps-2" style="font-size: 11.4px; padding: 7px; border: 1px solid #000000 !important;">
                                    <span class="d-block" style="font-size: 11.4px;">{{ $mapelItem }}</span>
                                    @if($mapelKey === 'Ilmu Pengetahuan Alam (IPA)' && $isKelas10)
                                        <span class="d-block text-muted" style="font-size: 9.5px;">(Fisika, Kimia, Biologi)</span>
                                    @elseif($mapelKey === 'Ilmu Pengetahuan Sosial (IPS)' && $isKelas10)
                                        <span class="d-block text-muted" style="font-size: 9.5px;">(Sosiologi, Ekonomi, Sejarah, Geografi)</span>
                                    @endif
                                </td>
                                <td class="text-center" style="font-size: 11.2px; padding: 7px; border: 1px solid #000000 !important;">
                                    {{ $nilaiAkhir }}
                                </td>
                                <td style="text-align: justify; padding: 7px; font-size: 11.2px; line-height: 1.4; color:#000; border: 1px solid #000000 !important;">
                                    {{ $deskripsiCapaian }}
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table> 
        </div> {{-- PENUTUP HALAMAN 1 --}}

        <div class="page-break-divider"></div>

        {{-- ==================== HALAMAN 2 (UNIVERSAL) ==================== --}}
        <div class="page-container-A4 h2-padding-top">
            
            {{-- KHUSUS KELAS XI / XII: Render Kelompok Mata Pelajaran Mulok Di Sini --}}
            @if(!$isKelas10)
                <table class="table-raport mb-3 text-split-target">
                    <colgroup>
                        <col style="width: 5%;">
                        <col style="width: 37%;">
                        <col style="width: 13%;">
                        <col style="width: 45%;">
                    </colgroup>
                    <tbody>
                        <tr>
                            <td colspan="4" class="text-start text-uppercase py-2 ps-2 subheader-row" style="font-size: 12px; font-weight: bold; background-color: #f5f5f5 !important; border: 1px solid #000000 !important;">
                                Mata Pelajaran Mulok
                            </td>
                        </tr>
                        @foreach($strukturRapot['Mata Pelajaran Mulok'] as $mapelItem)
                            @php
                                $mapelKey = trim($mapelItem);
                                $nilaiAkhir = '-';
                                $deskripsiCapaian = 'Belum ada data nilai harian maupun ujian.';
                               
                                if (isset($s->rekap_nilai[$mapelKey])) {
                                    $nilaiGroup = $s->rekap_nilai[$mapelKey];
                                    $valUas = $nilaiGroup->where('jenis', 'uas')->whereNotNull('uas')->first()?->uas;
                                    $harianAverage = $nilaiGroup->where('jenis', 'harian')->whereNotNull('mingguan')->avg('mingguan');
                                   
                                    if ($harianAverage !== null || $valUas !== null) {
                                        $nilaiAkhir = round((($harianAverage ?? 0) * 0.70) + (($valUas ?? 0) * 0.30));
                                    }

                                    $jadwalSampel = $nilaiGroup->first()->jadwal ?? null;
                                    $kkmAktif = $jadwalSampel ? (Illuminate\Support\Facades\DB::table('mapel_guru')->where('user_id', $jadwalSampel->guru_id)->where('mapel_id', $jadwalSampel->mapel_id)->first()->kkm ?? 75) : 75;
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
                            @endphp
                            <tr>
                                <td class="text-center" style="font-size: 12px; padding: 7px; border: 1px solid #000000 !important;">{{ $globalIdx++ }}</td>
                                <td class="ps-2" style="font-size: 12.5px; padding: 7px; border: 1px solid #000000 !important;">
                                    <span class="d-block" style="font-size: 12px;">{{ $mapelItem }}</span>
                                </td>
                                <td class="text-center" style="font-size: 12px; padding: 7px; border: 1px solid #000000 !important;">
                                    {{ $nilaiAkhir }}
                                <td style="text-align: justify; padding: 7px; font-size: 12px; line-height: 1.4; color:#000; border: 1px solid #000000 !important;">
                                    {{ $deskripsiCapaian }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- BLOK NON-MAPEL BERSIH TANPA GANGGUAN STRUKTUR TABEL INDUK --}}
            <div class="non-mapel-block">
                <div class="text-uppercase mb-1 section-title" style="font-weight: bold !important; font-size: 13px;">KOKURIKULER</div>
                <table class="w-100 table-raport mb-3">
                    <tbody>
                        <tr>
                            <td id="out_kurikuler_{{ $s->id }}" class="ps-2 py-2 text-split-koku" style="text-align: justify; font-size: 12px; min-height: 35px; line-height: 1.4; border: 1px solid #000000 !important;">{{ $s->rapot_kokurikuler ? $s->rapot_kokurikuler : '-' }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="text-uppercase mb-1 section-title" style="font-weight: bold !important; font-size: 13px;">KEGIATAN EKSTRAKURIKULER</div>
                <table class="w-100 table-raport mb-3">
                    <colgroup>
                        <col style="width: 5%;">
                        <col style="width: 37%;">
                        <col style="width: 13%;">
                        <col style="width: 45%;">
                    </colgroup>
                    <thead>
                        <tr class="text-center" style="font-size: 12px; font-weight: bold;">
                            <th>No</th>
                            <th>Kegiatan Ekstrakurikuler</th>
                            <th>Predikat</th>
                            <th>Keterangan / Deskripsi Kemajuan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center" style="padding: 5px; border: 1px solid #000000 !important;">1</td>
                            <td id="out_eskul_nama_1_{{ $s->id }}" class="ps-2 text-lbl-name1" style="padding: 5px; font-size: 12px; border: 1px solid #000000 !important;">{{ $s->rapot_eskul_nama1 ? $s->rapot_eskul_nama1 : '-' }}</td>
                            <td id="out_eskul_predikat_1_{{ $s->id }}" class="text-center text-lbl-pred1" style="padding: 5px; font-size: 12px; border: 1px solid #000000 !important;">{{ $s->rapot_eskul_nama1 ? $s->rapot_eskul_pred1 : '-' }}</td>
                            <td id="out_eskul_desc_1_{{ $s->id }}" class="ps-2 text-lbl-desc1" style="font-size: 12px; padding: 5px; line-height: 1.3; border: 1px solid #000000 !important;">{{ $s->rapot_eskul_nama1 ? $s->rapot_eskul_desc1 : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-center" style="padding: 5px; border: 1px solid #000000 !important;">2</td>
                            <td id="out_eskul_nama_2_{{ $s->id }}" class="ps-2 text-lbl-name2" style="padding: 5px; font-size: 12px; border: 1px solid #000000 !important;">{{ $s->rapot_eskul_nama2 ? $s->rapot_eskul_nama2 : '-' }}</td>
                            <td id="out_eskul_predikat_2_{{ $s->id }}" class="text-center text-lbl-pred2" style="padding: 5px; font-size: 12px; border: 1px solid #000000 !important;">{{ $s->rapot_eskul_nama2 ? $s->rapot_eskul_pred2 : '-' }}</td>
                            <td id="out_eskul_desc_2_{{ $s->id }}" class="ps-2 text-lbl-desc2" style="font-size: 12px; padding: 5px; line-height: 1.3; border: 1px solid #000000 !important;">{{ $s->rapot_eskul_nama2 ? $s->rapot_eskul_desc2 : '-' }}</td>
                        </tr>
                    </tbody>
                </table>

                <div style="display: flex; gap: 15px; width: 100%; align-items: flex-start; margin-top: 5px;">
                    <div style="width: 42%;">
                        <div class="text-uppercase mb-1" style="font-size: 13px; font-weight: bold !important;">KETIDAKHADIRAN</div>
                        <table class="w-100 table-raport">
                            <tbody>
                                <tr style="height: 25px;">
                                    <td width="65%" class="ps-2" style="font-size: 12px; border: 1px solid #000000 !important;">Sakit</td>
                                    <td id="out_absen_sakit_{{ $s->id }}" class="text-center fw-bold text-lbl-sakit" style="font-size: 12px; border: 1px solid #000000 !important;">
                                        {{ ($s->rapot_abs_sakit !== null && $s->rapot_abs_sakit !== '' && $s->rapot_abs_sakit !== 0) ? $s->rapot_abs_sakit . ' Hari' : '-' }}
                                    </td>
                                </tr>
                                <tr style="height: 25px;">
                                    <td class="ps-2" style="font-size: 12px; border: 1px solid #000000 !important;">Izin</td>
                                    <td id="out_absen_izin_{{ $s->id }}" class="text-center fw-bold text-lbl-izin" style="font-size: 12px; border: 1px solid #000000 !important;">
                                        {{ ($s->rapot_abs_izin !== null && $s->rapot_abs_izin !== '' && $s->rapot_abs_izin !== 0) ? $s->rapot_abs_izin . ' Hari' : '-' }}
                                    </td>
                                </tr>
                                <tr style="height: 25px;">
                                    <td class="ps-2" style="font-size: 12px; border: 1px solid #000000 !important;">Tanpa Keterangan (Alfa)</td>
                                    <td id="out_absen_alfa_{{ $s->id }}" class="text-center fw-bold text-lbl-alfa" style="font-size: 12px; border: 1px solid #000000 !important;">
                                        {{ ($s->rapot_abs_alfa !== null && $s->rapot_abs_alfa !== '' && $s->rapot_abs_alfa !== 0) ? $s->rapot_abs_alfa . ' Hari' : '-' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="flex: 1;">
                        <div class="text-uppercase mb-1" style="font-size: 13px; font-weight: bold !important;">CATATAN WALI KELAS</div>
                        <div id="print-text-{{ $s->id }}" class="text-split-catatan" style="border: 1px solid #000000; padding: 6px 8px; height: 93px; text-align: justify; font-size: 12px; line-height: 1.35; background-color: #fff; box-sizing: border-box; overflow: hidden;">{{ $s->rapot_catatan_wali ? $s->rapot_catatan_wali : '-' }}</div>
                    </div>
                </div>

                {{-- PERBAIKAN STRUKTUR KOLOM TANDA TANGAN (KOLOM KETIGA MENJADI TEXT-ALIGN: LEFT KONSISTEN) --}}
                <div class="signature-container-block" style="display: flex; justify-content: space-between; text-align: center; font-size: 12px; margin-top: 35px;">
                    <div style="width: 30%;">
                        <p class="mb-0" style="margin-bottom: 50px; margin-top: 91px; margin-right: 46px;">Mengetahui,</p>
                        <p style="margin-bottom: 50px;">Orang Tua/Wali Murid</p>
                        <p class="mb-0">...................................</p>
                    </div>
                    <div style="width: 33%;">
                        <br>
                        <p style="margin-bottom: 50px; margin-top: 91px;">Kepala SMA EL FITRA</p>
                        <p class="mb-0" style="text-decoration: underline;">Tetep Kurnia, S.Kom.</p>
                    </div>
                    <div style="width: 37%; text-align: left; box-sizing: border-box; padding-left: 40px;">
                        <p class="mb-0" style="font-weight: bold;">Keputusan:</p>
                        <p class="mb-0" style="line-height: 1.3;">Berdasarkan hasil yang dicapai pada semester 1 dan 2, peserta didik</p>
                        <p class="mb-0">dinyatakan:</p>
                        <p class="mb-2" style="font-weight: bold; font-size: 12.5px;">
                            @if($isKelas10)
                                Naik ke Kelas: XI (Sebelas)
                            @elseif(str_contains($namaKelasStr, 'XI') || str_contains($namaKelasStr, '11'))
                                Naik ke Kelas: XII (Dua Belas)
                            @else
                                Lulus / Menyelesaikan Program
                            @endif
                        </p>
                        <p class="mb-0">Bandung, 18 Juni 2026</p>
                        <p style="margin-bottom: 50px;">Wali Kelas,</p>
                        <p class="mb-0" style="text-decoration: underline;">{{ Auth::user()->nama }}</p>
                    </div>
                </div>
            </div>

        </div> {{-- PENUTUP HALAMAN 2 --}}

    </div>
    @endforeach
</div>

<style>
    .raport-page, 
    .raport-page *, 
    .student-metadata-table, 
    .table-raport, 
    .table-raport th, 
    .table-raport td, 
    .signature-container-block {
        font-family: 'Calibri', 'Segoe UI', sans-serif !important;
    }
    
    @page {
        size: A4 portrait;
        margin: 0;
    }
    
    @media print {
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        @if(!$raportBisaDicetak)
            body * { display: none !important; visibility: hidden !important; }
        @else
            .d-print-none { display: none !important; }
            #print-area, #print-area * { visibility: visible; }
            #print-area { position: absolute; left: 0; top: 0; width: 100%; }
        @endif
        
        .raport-page {
            box-sizing: border-box;
            width: 100%;
            background: #fff !important;
        }

        .page-break-divider {
            page-break-before: always !important;
            break-before: page !important;
            height: 1px;
            background: transparent;
        }

        .page-container-A4 {
            padding: 22mm 15mm 15mm 15mm !important;
            box-sizing: border-box;
        }

        .h2-padding-top {
            padding-top: 25mm !important;
        }
        
        .table-raport, .table-raport th, .table-raport td, .subheader-row {
            border: 1px solid #000000 !important;
        }

        .non-mapel-block {
            page-break-inside: avoid !important;
        }

        .text-split-target tr {
            page-break-inside: avoid;
        }
    }
    
    .raport-page {
        color: #000;
        background: #fff;
        font-size: 13.5px;
        line-height: 1.4;
    }
    .header-title-block {
        margin-bottom: 20px;
    }
    .section-title {
        font-size: 11px;
        margin-top: 10px;
    }
    .table-raport {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed !important;
    }
    .table-raport th, .table-raport td {
        border: 1px solid #000000 !important;
        padding: 7px 5px !important;
        vertical-align: middle;
        background-color: #fff !important;
        word-wrap: break-word !important;
    }
    .table-raport th {
        background-color: #f2f2f2 !important;
    }
    
    #wrapper-render-preview .raport-page {
        display: block !important;
    }
    #wrapper-render-preview .page-break-divider {
        border-top: 3px dashed #ff0000;
        margin: 40px 0 20px 0;
    }
</style>

<script>
    function singkronisasiDataLaporan(siswaId) {
        const koku = document.getElementById('in_kurikuler_' + siswaId).value;
        const eNama1 = document.getElementById('in_eskul_nama_1_' + siswaId).value;
        const ePred1 = document.getElementById('in_eskul_predikat_1_' + siswaId).value;
        const eDesc1 = document.getElementById('in_eskul_desc_1_' + siswaId).value;
        const eNama2 = document.getElementById('in_eskul_nama_2_' + siswaId).value;
        const ePred2 = document.getElementById('in_eskul_predikat_2_' + siswaId).value;
        const eDesc2 = document.getElementById('in_eskul_desc_2_' + siswaId).value;
        const sakit = document.getElementById('in_absen_sakit_' + siswaId).value;
        const izin = document.getElementById('in_absen_izin_' + siswaId).value;
        const alfa = document.getElementById('in_absen_alfa_' + siswaId).value;
        const catatan = document.getElementById('in_catatan_wali_' + siswaId).value;

        const urlParams = new URLSearchParams(window.location.search);
        const currentSemester = urlParams.get('semester') || '1';
        const currentTP = urlParams.get('tahun_pelajaran') || '2025/2026';

        const dataPayload = new FormData();
        dataPayload.append('siswa_id', siswaId);
        dataPayload.append('kelas_id', '{{ $kelas_id }}');
        dataPayload.append('semester', currentSemester);
        dataPayload.append('tahun_pelajaran', currentTP);
        dataPayload.append('kokurikuler', koku);
        dataPayload.append('eskul_nama1', eNama1);
        dataPayload.append('eskul_pred1', ePred1);
        dataPayload.append('eskul_desc1', eDesc1);
        dataPayload.append('eskul_nama2', eNama2);
        dataPayload.append('eskul_pred2', ePred2);
        dataPayload.append('eskul_desc2', eDesc2);
        dataPayload.append('sakit', sakit);
        dataPayload.append('izin', izin);
        dataPayload.append('alfa', alfa);
        dataPayload.append('catatan_wali', catatan);
        dataPayload.append('_token', '{{ csrf_token() }}');

        fetch('{{ route("admin.laporan.simpan_manual") }}', {
            method: 'POST',
            body: dataPayload
        })
        .then(res => res.json())
        .then(result => {
            if(result.success) {
                document.getElementById('out_kurikuler_' + siswaId).innerText = koku.trim() !== "" ? koku : "-";
                document.getElementById('out_eskul_nama_1_' + siswaId).innerText = eNama1.trim() !== "" ? eNama1 : "-";
                document.getElementById('out_eskul_predikat_1_' + siswaId).innerText = eNama1.trim() !== "" ? ePred1 : "-";
                document.getElementById('out_eskul_desc_1_' + siswaId).innerText = eDesc1.trim() !== "" ? eDesc1 : "-";
                document.getElementById('out_eskul_nama_2_' + siswaId).innerText = eNama2.trim() !== "" ? eNama2 : "-";
                document.getElementById('out_eskul_predikat_2_' + siswaId).innerText = eNama2.trim() !== "" ? ePred2 : "-";
                document.getElementById('out_eskul_desc_2_' + siswaId).innerText = eDesc2.trim() !== "" ? eDesc2 : "-";
                
                document.getElementById('out_absen_sakit_' + siswaId).innerText = (sakit.trim() !== "" && parseInt(sakit) !== 0) ? sakit + " Hari" : "-";
                document.getElementById('out_absen_izin_' + siswaId).innerText = (izin.trim() !== "" && parseInt(izin) !== 0) ? izin + " Hari" : "-";
                document.getElementById('out_absen_alfa_' + siswaId).innerText = (alfa.trim() !== "" && parseInt(alfa) !== 0) ? alfa + " Hari" : "-";
                
                document.getElementById('print-text-' + siswaId).innerText = catatan.trim() !== "" ? catatan : "-";
                
                const btn = document.getElementById('btn-rapot-' + siswaId);
                if (btn) {
                    btn.className = "btn btn-sm btn-success text-white px-2";
                    btn.querySelector('.btn-text').innerText = 'Edit Data Rapot';
                }
                alert('✓ Sukses! Lembar evaluasi rapot siswa berhasil diamankan.');
            } else {
                alert('⚠️ Gagal menyimpan data manual rapot ke server.');
            }
        });
    }

    function uploadRapotMassalExcel(kelasId) {
        const uploader = document.getElementById('excel_rapot_massal');
        if (!uploader.files.length) return;

        const urlParams = new URLSearchParams(window.location.search);
        const currentSemester = urlParams.get('semester') || '1';
        const currentTP = urlParams.get('tahun_pelajaran') || '2025/2026';

        const formData = new FormData();
        formData.append('file_excel', uploader.files[0]);
        formData.append('kelas_id', kelasId);
        formData.append('_token', '{{ csrf_token() }}');

        fetch(`{{ route("admin.laporan.import_warmup") }}?semester=${currentSemester}&tahun_pelajaran=${currentTP}`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.success && result.data) {
                let totalSuccess = 0;
                result.data.forEach(item => {
                    const modalElement = document.querySelector(`.modal-parent-node[data-siswa="${item.siswa_id}"]`);
                    const raportNode = document.querySelector(`.origin-raport-node[data-siswa-id="${item.siswa_id}"]`);
                    
                    if (modalElement && raportNode) {
                        modalElement.querySelector('.text-box-koku').value = item.kokurikuler;
                        modalElement.querySelector('.text-eskul-name1').value = item.eskul_nama1;
                        modalElement.querySelector('.select-eskul-pred1').value = item.eskul_pred1;
                        modalElement.querySelector('.text-eskul-desc1').value = item.eskul_desc1;
                        modalElement.querySelector('.text-eskul-name2').value = item.eskul_nama2;
                        modalElement.querySelector('.select-eskul-pred2').value = item.eskul_pred2;
                        modalElement.querySelector('.text-eskul-desc2').value = item.eskul_desc2;
                        modalElement.querySelector('.num-abs-sakit').value = item.sakit !== null ? item.sakit : '';
                        modalElement.querySelector('.num-abs-izin').value = item.izin !== null ? item.izin : '';
                        modalElement.querySelector('.num-abs-alfa').value = item.alfa !== null ? item.alfa : '';
                        modalElement.querySelector('.text-box-catatan').value = item.catatan_wali;

                        if (raportNode.querySelector('.text-split-koku')) raportNode.querySelector('.text-split-koku').innerText = item.kokurikuler;
                        if (raportNode.querySelector('.text-lbl-name1')) raportNode.querySelector('.text-lbl-name1').innerText = item.eskul_nama1 || "-";
                        if (raportNode.querySelector('.text-lbl-pred1')) raportNode.querySelector('.text-lbl-pred1').innerText = item.eskul_nama1 ? item.eskul_pred1 : "-";
                        if (raportNode.querySelector('.text-lbl-desc1')) raportNode.querySelector('.text-lbl-desc1').innerText = item.eskul_nama1 ? item.eskul_desc1 : "-";
                        if (raportNode.querySelector('.text-lbl-name2')) raportNode.querySelector('.text-lbl-name2').innerText = item.eskul_nama2 || "-";
                        if (raportNode.querySelector('.text-lbl-pred2')) raportNode.querySelector('.text-lbl-pred2').innerText = item.eskul_nama2 ? item.eskul_pred2 : "-";
                        if (raportNode.querySelector('.text-lbl-desc2')) raportNode.querySelector('.text-lbl-desc2').innerText = item.eskul_nama2 ? item.eskul_desc2 : "-";
                        if (raportNode.querySelector('.text-lbl-sakit')) raportNode.querySelector('.text-lbl-sakit').innerText = (item.sakit !== null && item.sakit !== 0) ? item.sakit + " Hari" : "-";
                        if (raportNode.querySelector('.text-lbl-izin')) raportNode.querySelector('.text-lbl-izin').innerText = (item.izin !== null && item.izin !== 0) ? item.izin + " Hari" : "-";
                        if (raportNode.querySelector('.text-lbl-alfa')) raportNode.querySelector('.text-lbl-alfa').innerText = (item.alfa !== null && item.alfa !== 0) ? item.alfa + " Hari" : "-";
                        if (raportNode.querySelector('.text-split-catatan')) raportNode.querySelector('.text-split-catatan').innerText = item.catatan_wali;

                        const btnEdit = document.getElementById(`btn-rapot-${item.siswa_id}`);
                        if (btnEdit) {
                            btnEdit.className = "btn btn-sm btn-success text-white px-2";
                            btnEdit.querySelector('.btn-text').innerText = 'Edit Data Rapot';
                        }
                        totalSuccess++;
                    }
                });
                alert(`✓ Sukses! Berhasil menguraikan ${totalSuccess} data dari Excel massal.`);
            } else {
                alert('X Gagal: ' + (result.message || 'Berkas tidak valid.'));
            }
            uploader.value = '';
        })
        .catch(error => {
            alert('X Terjadi gangguan jaringan.');
            uploader.value = '';
        });
    }

    function unduhTemplateRapotKelasDinamis(e, kelasId) {
        e.preventDefault();
        const currentSemester = document.getElementById('filter_semester').value;
        const currentTP = document.getElementById('filter_tp').value;
        let baseUrl = "{{ route('admin.laporan.template_excel', ':id') }}";
        baseUrl = baseUrl.replace(':id', kelasId);
        window.location.href = `${baseUrl}?semester=${currentSemester}&tahun_pelajaran=${currentTP}`;
    }

    function bukaPreviewLapot(divId) {
        const targetNode = document.getElementById(divId);
        const renderBox = document.getElementById('wrapper-render-preview');
        const containerPreview = document.getElementById('container-preview-layar');
        if (targetNode) {
            const allRaportPages = document.querySelectorAll('.origin-raport-node');
            allRaportPages.forEach(page => { page.style.display = 'none'; });
            const clone = targetNode.cloneNode(true);
            clone.classList.remove('origin-raport-node');
            clone.style.display = 'block';
            renderBox.innerHTML = '';
            renderBox.appendChild(clone);
            containerPreview.classList.remove('d-none');
            containerPreview.scrollIntoView({ behavior: 'smooth' });
        }
    }

    function tutupPreviewLayarRapot() {
        document.getElementById('container-preview-layar').classList.add('d-none');
        document.getElementById('wrapper-render-preview').innerHTML = '';
    }

    function printRaport(divId) {
        const targetPage = document.getElementById(divId);
        if (targetPage) {
            tutupPreviewLayarRapot();
            const allPages = document.querySelectorAll('.origin-raport-node');
            allPages.forEach(page => { page.style.display = 'none'; });
            targetPage.style.display = 'block';
            window.print();
            setTimeout(() => {
                allPages.forEach(page => { page.style.display = 'block'; });
            }, 800);
        }
    }
</script>
@endsection