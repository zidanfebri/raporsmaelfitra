@extends('layouts.main')

@section('content')
<style>
    .nav-tabs .nav-link {
        color: #6c757d !important;
        border: none !important;
        border-bottom: 3px solid transparent !important;
        transition: all 0.3s ease;
    }

    .nav-tabs .nav-link.active {
        color: #4e73df !important;
        background: none !important;
        border-bottom: 3px solid #4e73df !important;
        font-weight: bold;
    }

    .nav-tabs .nav-link:hover {
        color: #4e73df !important;
        border-bottom: 3px solid #dee2e6 !important;
    }
    
    /* FIX LAYOUT MOBILE: Optimalisasi Tombol Presensi di HP */
    .btn-group-presensi d-inline-flex {
        display: inline-flex;
    }
    .btn-group-presensi input[type="radio"] {
        display: none;
    }
    .btn-group-presensi label {
        padding: 6px 12px;
        font-size: 12px;
        font-weight: bold;
        border: 1px solid #ddd;
        background: #fff;
        color: #555;
        cursor: pointer;
        border-radius: 4px;
        margin-right: 2px;
        transition: all 0.2s ease;
    }
    .btn-group-presensi input[type="radio"]:checked + label.lbl-h { background: #1cc88a; color: #fff; border-color: #1cc88a; }
    .btn-group-presensi input[type="radio"]:checked + label.lbl-s { background: #f6c23e; color: #fff; border-color: #f6c23e; }
    .btn-group-presensi input[type="radio"]:checked + label.lbl-i { background: #36b9cc; color: #fff; border-color: #36b9cc; }
    .btn-group-presensi input[type="radio"]:checked + label.lbl-a { background: #e74a3b; color: #fff; border-color: #e74a3b; }

    /* STICKY COLUMN STYLE UNTUK TABEL MOBILE HP */
    .table-responsive-mobile {
        position: relative;
        overflow-x: auto;
    }
    @media (max-width: 768px) {
        .sticky-col-nama {
            position: -webkit-sticky;
            position: sticky;
            left: 0;
            background-color: #fff !important;
            z-index: 2;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        th.sticky-col-nama {
            z-index: 3;
            background-color: #f8f9fc !important;
        }
    }
</style>

<div class="row mb-4">
    <div class="col">
        <h4 class="fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Panel Input Nilai & Presensi Siswa</h4>
        <p class="text-muted">Kelola nilai harian dan ujian siswa berdasarkan jadwal.</p>
    </div>
</div>

{{-- SINKRONISASI NOTIFIKASI PRIVAT PERSONAL PROGRESS BEBAN INPUT GURU --}}
<div class="card border-0 shadow-sm mb-4 bg-light border-start border-4 border-warning">
    <div class="card-body p-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-shield-exclamation text-warning fs-5"></i>
            <h6 class="m-0 fw-bold text-dark">Kontrol Progress Pemenuhan Batas Wajib Minimal 1 Input Nilai Per Mata Pelajaran</h6>
        </div>
        <p class="text-muted small mb-3">Sesuai aturan kurikulum, Anda wajib melakukan minimal 1 kali instalan input harian terpisah **murni per mata pelajaran** di setiap kelas tempat Anda mengajar.</p>
        
        <div class="row g-2">
            @foreach($kelasDiampu as $kls)
                @php
                    $mapelTerplot = \App\Models\Jadwal::where('kelas_id', $kls->id)
                        ->where('guru_id', Auth::id())
                        ->select('mapel_id')
                        ->groupBy('mapel_id')
                        ->with('mapel')
                        ->get();
                @endphp

                @foreach($mapelTerplot as $plotJadwal)
                    @php
                        $listJadwal = \App\Models\Jadwal::where('kelas_id', $kls->id)
                            ->where('guru_id', Auth::id())
                            ->where('mapel_id', $plotJadwal->mapel_id)
                            ->when(request('semester'), fn($q) => $q->where('semester', request('semester')))
                            ->when(request('tahun_pelajaran'), fn($q) => $q->where('tahun_pelajaran', 'LIKE', "%".request('tahun_pelajaran')."%"))
                            ->pluck('id');

                        $totalInputHarianSelesai = \App\Models\Nilai::whereIn('jadwal_id', $listJadwal)
                            ->where('jenis', 'harian')
                            ->whereNotNull('mingguan')
                            ->select('jadwal_id')
                            ->groupBy('jadwal_id')
                            ->get()
                            ->count();
                        
                        $isLengkap = $totalInputHarianSelesai >= 1;
                    @endphp
                    <div class="col-md-6 col-lg-4">
                        <div class="p-2.5 rounded bg-white border d-flex justify-content-between align-items-center h-100">
                            <div style="max-width: 65%;">
                                <span class="small fw-bold text-dark d-block text-truncate">Kelas: {{ $kls->nama_kelas }}</span>
                                <span class="badge bg-primary-subtle text-primary d-inline-block text-truncate small" style="font-size: 10px; max-width: 100%;">
                                    {{ $plotJadwal->mapel->nama_mapel }}
                                </span>
                            </div>
                            <div class="text-end">
                                <span class="badge {{ $isLengkap ? 'bg-success' : 'bg-warning text-dark' }} font-weight-bold px-2 py-1">
                                    {{ $totalInputHarianSelesai }} / 1 Input
                                </span>
                                @if(!$isLengkap)
                                    <small class="text-danger d-block mt-1 small fw-bold" style="font-size: 10px;">Kurang {{ 1 - $totalInputHarianSelesai }}</small>
                                @else
                                    <small class="text-success d-block mt-1 small fw-bold" style="font-size: 10px;"><i class="bi bi-check2-all"></i> Aman</small>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="{{ route('dashboard') }}" method="GET" class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="small fw-bold text-muted mb-1 d-block">Semester</label>
                <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    <option value="1" {{ request('semester') == '1' ? 'selected' : '' }}>Semester 1</option>
                    <option value="2" {{ request('semester') == '2' ? 'selected' : '' }}>Semester 2</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="small fw-bold text-muted mb-1 d-block">Tahun Pelajaran</label>
                <select name="tahun_pelajaran" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Tahun</option>
                    @foreach(['2025/2026', '2026/2027', '2027/2028', '2028/2029', '2029/2030'] as $tp_opt)
                        <option value="{{ $tp_opt }}" {{ request('tahun_pelajaran') == $tp_opt ? 'selected' : '' }}>{{ $tp_opt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="small fw-bold text-muted mb-1 d-block">Filter Kelas</label>
                <select name="kelas_filter" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Kelas</option>
                    @foreach($kelasDiampu as $kls)
                        <option value="{{ $kls->id }}" {{ request('kelas_filter') == $kls->id ? 'selected' : '' }}>{{ $kls->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="small fw-bold text-muted mb-1 d-block">Filter Mapel</label>
                <select name="mapel_filter" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Mapel</option>
                    @foreach($mapelDiampu as $mp)
                        <option value="{{ $mp->id }}" {{ request('mapel_filter') == $mp->id ? 'selected' : '' }}>{{ $mp->nama_mapel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="small fw-bold text-muted mb-1 d-block">Filter Tanggal</label>
                <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ request('tanggal') }}" onchange="this.form.submit()">
            </div>
            <div class="col-6 col-md-2">
                <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white pt-3 border-bottom">
        <ul class="nav nav-tabs card-header-tabs" id="nilaiTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active px-3 px-md-4" id="harian-tab" data-bs-toggle="tab" data-bs-target="#harian" type="button" role="tab">
                    <i class="bi bi-calendar-week me-1 me-md-2"></i>Nilai Harian
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link px-3 px-md-4" id="ujian-tab" data-bs-toggle="tab" data-bs-target="#ujian" type="button" role="tab">
                    <i class="bi bi-file-earmark-text me-1"></i>Nilai UTS/UAS
                </button>
            </li>
        </ul>
    </div>
    
    <div class="card-body">
        <div class="tab-content" id="nilaiTabContent">
            
            {{-- TAB NILAI HARIAN --}}
            <div class="tab-pane fade show active" id="harian" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Status</th>
                                <th>Semester/TP</th>
                                <th>Hari/Tanggal</th>
                                <th>Mata Pelajaran</th>
                                <th>Kelas</th>
                                <th>Jam Mengajar</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $jadwalHarian = $jadwalSemua->filter(fn($j) => $j->tipe == 'mingguan'); @endphp
                            @forelse($jadwalHarian as $j)
                            @php
                                $sudahAdaNilaiHarian = \App\Models\Nilai::where('jadwal_id', $j->id)->where('jenis', 'harian')->exists();
                            @endphp
                            <tr>
                                <td>
                                    <span class="badge {{ $j->is_aktif ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $j->is_aktif ? 'Terbuka' : 'Terkunci' }}
                                    </span>
                                </td>
                                <td><small class="fw-bold">{{ $j->semester }}</small> | <small>{{ $j->tahun_pelajaran }}</small></td>
                                <td>{{ $j->hari }}, {{ \Carbon\Carbon::parse($j->tanggal)->format('d/m/Y') }}</td>
                                <td class="fw-bold text-primary">{{ $j->mapel->nama_mapel }}</td>
                                <td><span class="badge bg-info text-dark">{{ $j->kelas->nama_kelas }}</span></td>
                                <td><code class="text-primary fw-bold">{{ $j->jam_mulai }} </code> - <code class="fw-bold text-danger"> {{ $j->jam_akhir }}</code></td>
                                <td class="text-center">
                                    @if($sudahAdaNilaiHarian)
                                        <button class="btn btn-outline-warning btn-sm px-3 btn-trigger-modal-input" id="main-btn-{{ $j->id }}" data-jadwal="{{ $j->id }}" data-bs-toggle="modal" data-bs-target="#inputNilaiHarian{{ $j->id }}">
                                            <i class="bi bi-pencil-square me-1"></i> Edit Nilai
                                        </button>
                                    @else
                                        <button class="btn btn-primary btn-sm px-3 btn-trigger-modal-input" id="main-btn-{{ $j->id }}" data-jadwal="{{ $j->id }}" data-bs-toggle="modal" data-bs-target="#inputNilaiHarian{{ $j->id }}">
                                            <i class="bi bi-pencil-fill me-1"></i> Input Nilai
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted small">Tidak ada jadwal harian ditemukan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TAB UTS & UAS --}}
            <div class="tab-pane fade" id="ujian" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tipe</th>
                                <th>Semester/TP</th>
                                <th>Hari/Tanggal</th>
                                <th>Mata Pelajaran</th>
                                <th>Kelas</th>
                                <th>Jam</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $jadwalUjian = $jadwalSemua->filter(fn($j) => in_array($j->tipe, ['uts', 'uas'])); @endphp
                            @forelse($jadwalUjian as $u)
                            @php
                                $sudahAdaNilaiUjian = \App\Models\Nilai::where('jadwal_id', $u->id)->where('jenis', $u->tipe)->exists();
                            @endphp
                            <tr>
                                <td><span class="badge {{ $u->tipe == 'uts' ? 'bg-primary' : 'bg-success' }}">{{ strtoupper($u->tipe) }}</span></td>
                                <td><small class="fw-bold">{{ $u->semester }}</small> | <small>{{ $u->tahun_pelajaran }}</small></td>
                                <td>{{ $u->hari }}, {{ \Carbon\Carbon::parse($u->tanggal)->format('d/m/Y') }}</td>
                                <td class="fw-bold">{{ $u->mapel->nama_mapel }}</td>
                                <td><span class="badge bg-info text-dark">{{ $u->kelas->nama_kelas }}</span></td>
                                <td><code class="text-primary fw-bold">{{ $u->jam_mulai }}</code></td>
                                <td class="text-center">
                                    @if($sudahAdaNilaiUjian)
                                        <button class="btn btn-outline-warning btn-sm px-3" data-bs-toggle="modal" data-bs-target="#inputNilaiUjian{{ $u->id }}">
                                            <i class="bi bi-pencil-square"></i> Edit {{ strtoupper($u->tipe) }}
                                        </button>
                                    @else
                                        <button class="btn btn-outline-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#inputNilaiUjian{{ $u->id }}">
                                            <i class="bi bi-pencil-square"></i> Input {{ strtoupper($u->tipe) }}
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted small">Tidak ada jadwal UTS/UAS ditemukan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-center">
             {{ $jadwalSemua->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

{{-- MODAL SECTION --}}
@foreach($jadwalSemua as $j)
    @if($j->tipe == 'mingguan')
        @php
            $sampleNilai = \App\Models\Nilai::where('jadwal_id', $j->id)->where('jenis', 'harian')->latest('id')->first();
            $lastNomorBab = $sampleNilai ? $sampleNilai->id_bab : '';
            $lastNamaBab = $sampleNilai ? $sampleNilai->nama_bab : '';
            $lastModelNilai = $sampleNilai ? $sampleNilai->model_nilai : 'tugas';
        @endphp
        {{-- MODAL INPUT NILAI HARIAN --}}
        <div class="modal fade modal-penilaian-score" id="inputNilaiHarian{{ $j->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <form action="{{ route('nilai.harian.store') }}" method="POST" class="form-input-nilai">
                    @csrf
                    <input type="hidden" name="jadwal_id" value="{{ $j->id }}">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Input Nilai Harian: {{ $j->mapel->nama_mapel }}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="max-height: 450px; overflow-y: auto;">
                            
                            {{-- INTEGRASI COMPONENT LAYOUT MAATWEBSITE GAYA EXCEL ASLI --}}
                            <div class="bg-light p-2.5 rounded border mb-3 mx-2 d-flex justify-content-between align-items-center flex-wrap gap-2 text-dark">
                                <div>
                                    <span class="small d-block fw-bold text-dark"><i class="bi bi-file-earmark-excel-fill text-success me-1"></i>Isi Form Otomatis Via Template Excel (.xlsx)</span>
                                    <small class="text-muted" style="font-size:11px;">Unduh lembar kelas ini, isi data nilai lengkap beserta materi bab lalu unggah berkas excel untuk Auto-Save instan.</small>
                                </div>
                                <div class="d-flex align-items-center gap-1.5">
                                    {{-- PERBAIKAN: Mengaktifkan fungsi click handler dinamis agar nilai unduhan file mengikuti ketikan di form monitor --}}
                                    <a href="#" onclick="redirectDinamisTemplateDownload(event, '{{ $j->id }}')" class="btn btn-xs btn-success fw-bold text-white px-2.5 py-1 small" style="font-size:11px;">
                                        <i class="bi bi-download me-1"></i> Unduh Template Excel
                                    </a>
                                    <div class="position-relative">
                                        <button type="button" class="btn btn-xs btn-outline-dark fw-bold px-2.5 py-1 small" style="font-size:11px;" onclick="document.getElementById('file_excel_preview_{{ $j->id }}').click()">
                                            <i class="bi bi-upload me-1"></i> Unggah Berkas Excel
                                        </button>
                                        <input type="file" id="file_excel_preview_{{ $j->id }}" class="d-none input-async-excel-parse" data-jadwal="{{ $j->id }}" accept=".xls, .xlsx">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-3 px-2">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small text-muted">MODEL NILAI <span class="text-danger">*</span></label>
                                    <select name="model_nilai" id="model_nilai_{{ $j->id }}" class="form-select select-model-nilai" data-jadwal="{{ $j->id }}" required>
                                        <option value="tugas" {{ $lastModelNilai == 'tugas' ? 'selected' : '' }}>Tugas</option>
                                        <option value="postest harian" {{ $lastModelNilai == 'postest harian' ? 'selected' : '' }}>Postest Harian</option>
                                        <option value="postest bulanan" {{ $lastModelNilai == 'postest bulanan' ? 'selected' : '' }}>Postest Bulanan</option>
                                        <option value="ulangan harian" {{ $lastModelNilai == 'ulangan harian' ? 'selected' : '' }}>Ulangan Harian</option>
                                        <option value="praktikum" {{ $lastModelNilai == 'praktikum' ? 'selected' : '' }}>Praktikum</option>
                                    </select>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label fw-bold small text-muted">NOMOR BAB <span class="text-danger">*</span></label>
                                    <input type="number" name="nomor_bab" id="nomor_bab_{{ $j->id }}" class="form-control input-nomor-bab" data-jadwal="{{ $j->id }}" value="{{ $lastNomorBab }}" placeholder="Contoh : 1" min="1" required>
                                </div>
                                <div class="col-6 col-md-5">
                                    <label class="form-label fw-bold small text-muted">NAMA BAB <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_bab" id="nama_bab_{{ $j->id }}" class="form-control input-nama-bab" data-jadwal="{{ $j->id }}" value="{{ $lastNamaBab }}" placeholder="Masukkan nama materi bab..." required>
                                </div>
                            </div>

                            <hr class="my-2">

                            <div class="table-responsive-mobile">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr class="small text-muted text-uppercase text-center fw-bold">
                                            <th class="sticky-col-nama">Nama Siswa</th>
                                            <th width="210">Presensi Kehadiran</th>
                                            <th width="120">Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($j->kelas->siswa as $siswa)
                                        @php 
                                            $existing = $siswa->nilai->where('jadwal_id', $j->id)
                                                                     ->where('jenis', 'harian')
                                                                     ->first();
                                                                     
                                            $currentPresensi = $existing ? $existing->presensi : 'Hadir';
                                        @endphp
                                        <tr class="row-siswa-{{ $j->id }}" data-siswa="{{ $siswa->id }}">
                                            <td class="fw-bold ps-3 nama-label-siswa sticky-col-nama">{{ $siswa->nama }}</td>
                                            <td class="text-center">
                                                <div class="btn-group-presensi d-inline-flex">
                                                    <input type="radio" class="rad-h" name="presensi[{{ $siswa->id }}]" id="p_h_{{$j->id}}_{{ $siswa->id }}" value="Hadir" {{ $currentPresensi == 'Hadir' ? 'checked' : '' }}>
                                                    <label {{ $currentPresensi == 'Hadir' ? 'style=background:#1cc88a;color:#fff;border-color:#1cc88a;' : '' }} for="p_h_{{$j->id}}_{{ $siswa->id }}" class="lbl-h lbl-p-choice">H</label>
                                                    
                                                    <input type="radio" class="rad-s" name="presensi[{{ $siswa->id }}]" id="p_s_{{$j->id}}_{{ $siswa->id }}" value="Sakit" {{ $currentPresensi == 'Sakit' ? 'checked' : '' }}>
                                                    <label {{ $currentPresensi == 'Sakit' ? 'style=background:#f6c23e;color:#fff;border-color:#f6c23e;' : '' }} for="p_s_{{$j->id}}_{{ $siswa->id }}" class="lbl-s lbl-p-choice">S</label>
                                                    
                                                    <input type="radio" class="rad-i" name="presensi[{{ $siswa->id }}]" id="p_i_{{$j->id}}_{{ $siswa->id }}" value="Izin" {{ $currentPresensi == 'Izin' ? 'checked' : '' }}>
                                                    <label {{ $currentPresensi == 'Izin' ? 'style=background:#36b9cc;color:#fff;border-color:#36b9cc;' : '' }} for="p_i_{{$j->id}}_{{ $siswa->id }}" class="lbl-i lbl-p-choice">I</label>
                                                    
                                                    <input type="radio" class="rad-a" name="presensi[{{ $siswa->id }}]" id="p_a_{{$j->id}}_{{ $siswa->id }}" value="Alfa" {{ $currentPresensi == 'Alfa' ? 'checked' : '' }}>
                                                    <label {{ $currentPresensi == 'Alfa' ? 'style=background:#e74a3b;color:#fff;border-color:#e74a3b;' : '' }} for="p_a_{{$j->id}}_{{ $siswa->id }}" class="lbl-a lbl-p-choice">A</label>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number" name="nilai[{{ $siswa->id }}]" class="form-control form-control-sm text-center input-skor-harian validator-input-score" min="0" max="100" value="{{ $existing->mingguan ?? '' }}" placeholder="0-100">
                                                <div class="invalid-feedback text-start small fw-bold" style="font-size: 11px;"></div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer bg-light border-0 py-2">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">Simpan Nilai Harian</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @else
        {{-- MODAL INPUT NILAI UTS & UAS --}}
        <div class="modal fade modal-penilaian-score" id="inputNilaiUjian{{ $j->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <form action="{{ route('nilai.uts_uas.store') }}" method="POST" class="form-input-nilai">
                    @csrf
                    <input type="hidden" name="jadwal_id" value="{{ $j->id }}">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-dark text-white py-2">
                            <h5 class="modal-title" style="font-size: 16px;">Input Nilai {{ strtoupper($j->tipe) }} - {{ $j->mapel->nama_mapel }}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="max-height: 450px; overflow-y: auto; padding: 0;">
                            <div class="table-responsive-mobile">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-light text-center small fw-bold">
                                        <tr>
                                            <th class="sticky-col-nama">Nama Peserta Didik</th>
                                            <th width="210">Status Kehadiran</th>
                                            <th width="120">Skor Ujian</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($j->kelas->siswa as $siswa)
                                        @php 
                                            $existing = $siswa->nilai->where('jadwal_id', $j->id)->where('jenis', $j->tipe)->first(); 
                                            $currentPresensi = $existing ? $existing->presensi : 'Hadir';
                                        @endphp
                                        <tr>
                                            <td class="fw-bold ps-3 name-label-siswa sticky-col-nama">{{ $siswa->nama }}</td>
                                            <td class="text-center">
                                                <div class="btn-group-presensi d-inline-flex">
                                                    <input type="radio" name="presensi[{{ $siswa->id }}][{{ $j->tipe }}]" id="u_h_{{$j->id}}_{{ $siswa->id }}" value="Hadir" {{ $currentPresensi == 'Hadir' ? 'checked' : '' }}>
                                                    <label for="u_h_{{$j->id}}_{{ $siswa->id }}" class="lbl-h">H</label>
                                                    
                                                    <input type="radio" name="presensi[{{ $siswa->id }}][{{ $j->tipe }}]" id="u_s_{{$j->id}}_{{ $siswa->id }}" value="Sakit" {{ $currentPresensi == 'Sakit' ? 'checked' : '' }}>
                                                    <label for="u_s_{{$j->id}}_{{ $siswa->id }}" class="lbl-s">S</label>
                                                    
                                                    <input type="radio" name="presensi[{{ $siswa->id }}][{{ $j->tipe }}]" id="u_i_{{$j->id}}_{{ $siswa->id }}" value="Izin" {{ $currentPresensi == 'Izin' ? 'checked' : '' }}>
                                                    <label for="u_i_{{$j->id}}_{{ $siswa->id }}" class="lbl-i">I</label>
                                                    
                                                    <input type="radio" name="presensi[{{ $siswa->id }}][{{ $j->tipe }}]" id="u_a_{{$j->id}}_{{ $siswa->id }}" value="Alfa" {{ $currentPresensi == 'Alfa' ? 'checked' : '' }}>
                                                    <label for="u_a_{{$j->id}}_{{ $siswa->id }}" class="lbl-a">A</label>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number" name="nilai[{{ $siswa->id }}][{{ $j->tipe }}]" class="form-control form-control-sm text-center border-primary validator-input-score" min="0" max="100" value="{{ $existing->{$j->tipe} ?? '' }}" placeholder="0-100">
                                                <div class="invalid-feedback text-start small fw-bold" style="font-size: 11px;"></div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer bg-light border-0 py-2">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-dark btn-sm px-4">Simpan {{ strtoupper($j->tipe) }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endforeach

<script>
// BARU: FUNGSI HANDLER INTERSEPSI TAUTAN LINK UNDUH TEMPLATE SECARA DINAMIS JAN
function redirectDinamisTemplateDownload(e, jadwalId) {
    e.preventDefault();
    const currentModal = document.getElementById(`inputNilaiHarian${jadwalId}`);
    if (!currentModal) return;

    // Ambil isi teks masukan ter-update yang sedang diketik guru di form monitor monitor
    const model = currentModal.querySelector('.select-model-nilai').value;
    const noBab = currentModal.querySelector('.input-nomor-bab').value;
    const namaBab = currentModal.querySelector('.input-nama-bab').value;

    // Susun URL dengan menyuntikkan query strings filter dinamis
    let baseUrl = "{{ route('nilai.harian.template', ':id') }}";
    baseUrl = baseUrl.replace(':id', jadwalId);
    
    const finalUrl = `${baseUrl}?model=${encodeURIComponent(model)}&no_bab=${encodeURIComponent(noBab)}&nama_bab=${encodeURIComponent(namaBab)}`;
    
    // Alihkan window browser untuk mengunduh berkas dengan aman
    window.location.href = finalUrl;
}

document.addEventListener("DOMContentLoaded", function () {
    
    document.querySelectorAll('.btn-group-presensi label').forEach(label => {
        label.addEventListener('click', function() {
            const container = this.closest('.btn-group-presensi');
            container.querySelectorAll('label').forEach(lbl => {
                lbl.style.background = '#fff';
                lbl.style.color = '#555';
                lbl.style.borderColor = '#ddd';
            });
            
            if(this.classList.contains('lbl-h')) { this.style.background = '#1cc88a'; this.style.borderColor = '#1cc88a'; }
            if(this.classList.contains('lbl-s')) { this.style.background = '#f6c23e'; this.style.borderColor = '#f6c23e'; }
            if(this.classList.contains('lbl-i')) { this.style.background = '#36b9cc'; this.style.borderColor = '#36b9cc'; }
            if(this.classList.contains('lbl-a')) { this.style.background = '#e74a3b'; this.style.borderColor = '#e74a3b'; }
            this.style.color = '#fff';
        });
    });

    document.querySelectorAll('.input-nomor-bab').forEach(input => {
        ['input', 'paste'].forEach(ev => {
            input.addEventListener(ev, function() {
                setTimeout(() => {
                    this.value = this.value.replace(/[^0-9]/g, '');
                }, 0);
            });
        });
    });

    function fetchAndRenderExistingData(jadwalId) {
        const currentModal = document.getElementById(`inputNilaiHarian${jadwalId}`);
        if (!currentModal) return;

        @foreach($jadwalSemua as $jadwalItem)
            if(jadwalId == "{{ $jadwalItem->id }}") {
                @foreach($jadwalItem->kelas->siswa as $siswaItem)
                    var found = false;
                    @foreach($siswaItem->nilai as $n)
                        if("{{ $n->jadwal_id }}" == jadwalId && "{{ $n->jenis }}" == "harian") {
                            
                            let scoreInput = currentModal.querySelector(`.row-siswa-${jadwalId}[data-siswa="{{ $siswaItem->id }}"] .input-skor-harian`);
                            if(scoreInput && scoreInput.value.trim() === '') {
                                scoreInput.value = "{{ $n->mingguan }}";
                            }
                            
                            var targetRadio = currentModal.querySelector(`.row-siswa-${jadwalId}[data-siswa="{{ $siswaItem->id }}"] input[value="{{ $n->presensi }}"]`);
                            if(targetRadio) {
                                targetRadio.checked = true;
                                targetRadio.nextElementSibling.click();
                            }
                            found = true;
                        }
                    @endforeach
                    
                    if(!found) {
                        let scoreInput = currentModal.querySelector(`.row-siswa-${jadwalId}[data-siswa="{{ $siswaItem->id }}"] .input-skor-harian`);
                    }
                @endforeach
            }
        @endforeach
    }

    document.querySelectorAll('.btn-trigger-modal-input').forEach(btn => {
        btn.addEventListener('click', function() {
            const jadwalId = this.getAttribute('data-jadwal');
            fetchAndRenderExistingData(jadwalId);
        });
    });

    // ================= FIX LOGIK SINKRONISASI ASYNC EXCEL MAATWEBSITE PARSER & AUTO SAVE =================
    document.querySelectorAll('.input-async-excel-parse').forEach(uploader => {
        uploader.addEventListener('change', function() {
            const jadwalId = this.getAttribute('data-jadwal');
            const currentModal = document.getElementById(`inputNilaiHarian${jadwalId}`);
            if (!this.files.length || !currentModal) return;

            const formData = new FormData();
            formData.append('file_excel', this.files[0]);
            formData.append('jadwal_id', jadwalId);
            formData.append('_token', '{{ csrf_token() }}');

            fetch('{{ route("nilai.harian.baca_excel") }}', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success && result.data) {
                    let totalTerpetakan = 0;
                    let firstRowData = null;
                    let hasAnyExcelScore = false;

                    result.data.forEach(item => {
                        let cleanId = parseInt(String(item.siswa_id).replace(/[^0-9]/g, ''));
                        let rowElement = currentModal.querySelector(`.row-siswa-${jadwalId}[data-siswa="${cleanId}"]`);
                        
                        if (rowElement) {
                            if (!firstRowData) {
                                firstRowData = {
                                    model: item.model_nilai,
                                    noBab: item.nomor_bab,
                                    namaBab: item.nama_bab
                                };
                            }

                            if(item.nilai !== null && item.nilai !== '') {
                                hasAnyExcelScore = true;
                            }

                            let scoreInput = rowElement.querySelector('.input-skor-harian');
                            if (scoreInput) scoreInput.value = item.nilai;

                            let targetRadio = rowElement.querySelector(`input[value="${item.presensi}"]`);
                            if (targetRadio) {
                                targetRadio.checked = true;
                                
                                const container = targetRadio.closest('.btn-group-presensi');
                                if (container) {
                                    container.querySelectorAll('label').forEach(lbl => {
                                        lbl.style.background = '#fff';
                                        lbl.style.color = '#555';
                                        lbl.style.borderColor = '#ddd';
                                    });
                                    
                                    let labelElemen = targetRadio.nextElementSibling;
                                    if (labelElemen) {
                                        if(labelElemen.classList.contains('lbl-h')) { labelElemen.style.background = '#1cc88a'; labelElemen.style.borderColor = '#1cc88a'; }
                                        if(labelElemen.classList.contains('lbl-s')) { labelElemen.style.background = '#f6c23e'; labelElemen.style.borderColor = '#f6c23e'; }
                                        if(labelElemen.classList.contains('lbl-i')) { labelElemen.style.background = '#36b9cc'; labelElemen.style.borderColor = '#36b9cc'; }
                                        if(labelElemen.classList.contains('lbl-a')) { labelElemen.style.background = '#e74a3b'; labelElemen.style.borderColor = '#e74a3b'; }
                                        labelElemen.style.color = '#fff';
                                    }
                                }
                            }
                            totalTerpetakan++;
                        }
                    });

                    if (totalTerpetakan > 0 && firstRowData) {
                        currentModal.querySelector('.select-model-nilai').value = firstRowData.model;
                        currentModal.querySelector('.input-nomor-bab').value = firstRowData.noBab;
                        currentModal.querySelector('.input-nama-bab').value = firstRowData.namaBab;
                        
                        const mainButtonElement = document.getElementById(`main-btn-${jadwalId}`);
                        if (mainButtonElement) {
                            mainButtonElement.className = "btn btn-outline-warning btn-sm px-3 btn-trigger-modal-input";
                            mainButtonElement.innerHTML = '<i class="bi bi-pencil-square me-1"></i> Edit Nilai';
                        }

                        if (hasAnyExcelScore) {
                            alert(`✓ Sukses! Berhasil membaca ${totalTerpetakan} data siswa dari file Excel Maatwebsite dan otomatis tersimpan permanen ke database!`);
                        } else {
                            alert(`✓ Sukses! Identitas materi bab berhasil tersimpan di sistem, namun data hit progress total belum ditambahkan karena nilai seluruh siswa kosong.`);
                        }
                    } else {
                        alert('⚠️ Perhatian: File berhasil dibaca backend, namun tidak ada ID Siswa yang cocok dengan kelas ini. Pastikan menggunakan file Excel hasil unduhan template.');
                    }
                } else {
                    alert('X Gagal: ' + (result.message || 'Struktur file excel tidak valid.'));
                }
                this.value = '';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('X Terjadi kesalahan internal saat memproses file Excel Maatwebsite.');
                this.value = '';
            });
        });
    });

document.querySelectorAll('.form-input-nilai').forEach(form => {
    form.addEventListener('submit', function (e) {
        let listInputs = this.querySelectorAll('.validator-input-score');
        let firstErrorElement = null;
        let isValid = true;

        listInputs.forEach(input => {
            input.classList.remove('is-invalid');
            let feedback = input.nextElementSibling;
            if(feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.innerText = '';
                feedback.style.display = 'none';
            }
        });

        for (let i = 0; i < listInputs.length; i++) {
            let input = listInputs[i];
            let value = input.value.trim();

            if (value !== '') {
                let numericValue = parseFloat(value);

                if (numericValue > 100 || numericValue < 0 || isNaN(numericValue)) {
                    isValid = false;
                    input.classList.add('is-invalid');
                    
                    let feedback = input.nextElementSibling;
                    if(feedback && feedback.classList.contains('invalid-feedback')) {
                        feedback.innerText = `Nilai tidak boleh lebih dari 100!`;
                        feedback.style.display = 'block';
                    }

                    if (!firstErrorElement) {
                        firstErrorElement = input;
                    }
                }
            }
        }

        if (!isValid) {
            e.preventDefault(); 

            if (firstErrorElement) {
                let modalBody = firstErrorElement.closest('.modal-body');
                if (modalBody) {
                    modalBody.scrollTo({
                        top: firstErrorElement.offsetTop - modalBody.offsetTop - 20,
                        behavior: 'smooth'
                    });
                }
                firstErrorElement.focus(); 
            }
        } else {
            const jadwalId = this.querySelector('input[name="jadwal_id"]').value;
            let hasScoreManual = false;
            
            this.querySelectorAll('.input-skor-harian').forEach(inp => {
                if (inp.value.trim() !== '' && !isNaN(inp.value)) {
                    hasScoreManual = true;
                }
            });

            const mainButtonElement = document.getElementById(`main-btn-${jadwalId}`);
            if (mainButtonElement) {
                mainButtonElement.className = "btn btn-outline-warning btn-sm px-3 btn-trigger-modal-input";
                mainButtonElement.innerHTML = '<i class="bi bi-pencil-square me-1"></i> Edit Nilai';
            }
        }
    });
});
});
</script>
@endsection