@extends('layouts.main')

@section('content')
<div class="row mb-4">
    <div class="col">
        <h4 class="fw-bold"><i class="bi bi-person-badge me-2 text-primary"></i>Dashboard Siswa</h4>
        <p class="text-muted">Halo, <strong>{{ Auth::user()->nama }}</strong>. Berikut adalah rincian capaian nilai akademik Anda.</p>
    </div>
</div>

{{-- PANEL INTERAKTIF: FILTER SEMESTER & TAHUN PELAJARAN --}}
<div class="card border-0 shadow-sm mb-4 bg-light">
    <div class="card-body p-3">
        <form action="{{ route('dashboard') }}" method="GET" class="row g-2">
            <div class="col-6 col-md-4">
                <label class="small fw-bold text-muted mb-1">Semester</label>
                <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="1" {{ $semester == '1' ? 'selected' : '' }}>Semester 1 (Ganjil)</option>
                    <option value="2" {{ $semester == '2' ? 'selected' : '' }}>Semester 2 (Genap)</option>
                </select>
            </div>
            <div class="col-6 col-md-5">
                <label class="small fw-bold text-muted mb-1">Tahun Pelajaran</label>
                <select name="tahun_pelajaran" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach(['2025/2026', '2026/2027', '2027/2028', '2028/2029', '2029/2030'] as $tp_opt)
                        <option value="{{ $tp_opt }}" {{ $tp == $tp_opt ? 'selected' : '' }}>{{ $tp_opt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex align-items-end mt-2 mt-md-0">
                <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary w-100">Reset Filter</a>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card border-0 shadow-sm p-3">
            <div class="card-body text-center">
                <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->nama) }}&background=4e73df&color=fff" 
                     class="rounded-circle mb-3 shadow-sm" width="80">
                <h5 class="fw-bold mb-0">{{ Auth::user()->nama }}</h5>
                <small class="text-muted fw-bold d-block mt-1"><i class="bi bi-card-text me-1"></i>NIS: {{ Auth::user()->nisn ?? '-' }}</small>
                <hr>
                <div class="text-start">
                    <p class="small mb-1"><strong>Kelas:</strong> {{ Auth::user()->kelas->nama_kelas ?? '-' }}</p>
                    <p class="small mb-1"><strong>Semester Aktif:</strong> Semester {{ $semester }}</p>
                    <p class="small mb-1"><strong>Tahun Pelajaran:</strong> {{ $tp }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white pt-3 border-bottom-0">
                <ul class="nav nav-tabs card-header-tabs" id="siswaTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active fw-bold px-4" id="ujian-tab" data-bs-toggle="tab" data-bs-target="#ujian" type="button" role="tab">
                            <i class="bi bi-award me-1"></i> Nilai Ujian
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold px-4" id="mingguan-tab" data-bs-toggle="tab" data-bs-target="#mingguan" type="button" role="tab">
                            <i class="bi bi-calendar-week me-1"></i> Nilai Harian
                        </button>
                    </li>
                </ul>
            </div>
            
            <div class="card-body mt-3">
                <div class="tab-content" id="siswaTabContent">
                    
                    {{-- TAB NILAI UJIAN (HANYA UTS DAN UAS) --}}
                    <div class="tab-pane fade show active" id="ujian" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border-top">
                                <thead class="table-light">
                                    <tr class="text-center">
                                        <th class="text-start">Mata Pelajaran</th>
                                        <th>UTS</th>
                                        <th>UAS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($nilaiUjian as $mapel => $group)
                                        @php
                                            $valUts = $group->where('jenis', 'uts')->first()->uts ?? null;
                                            $valUas = $group->where('jenis', 'uas')->first()->uas ?? null;
                                        @endphp
                                        <tr class="text-center">
                                            <td class="text-start fw-bold text-primary">{{ $mapel }}</td>
                                            <td class="fw-bold text-secondary">{{ $valUts !== null ? $valUts : '-' }}</td>
                                            <td class="fw-bold text-success">{{ $valUas !== null ? $valUas : '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center py-4 text-muted">Belum ada rincian nilai ujian pada parameter filter ini.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- TAB NILAI HARIAN (DENGAN DETAIL TANGGAL, MODEL, BAB & NAMA MATERI) --}}
                    <div class="tab-pane fade" id="mingguan" role="tabpanel">
                        @forelse($nilaiMingguan as $mapel => $items)
                            <div class="mb-4">
                                <h6 class="fw-bold border-start border-primary border-3 padd-left ps-2 mb-3 text-dark">{{ $mapel }}</h6>
                                <div class="row row-cols-1 row-cols-md-2 g-3">
                                    @foreach($items->sortBy('tanggal') as $n)
                                        <div class="col">
                                            <div class="card border shadow-sm h-100 bg-white">
                                                <div class="card-body p-3">
                                                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                                        <span class="badge bg-primary text-uppercase px-2 py-1" style="font-size: 10px;">
                                                            {{ $n->model_nilai ?? 'Tugas' }}
                                                        </span>
                                                        <small class="text-muted fw-bold" style="font-size: 11px;">
                                                            <i class="bi bi-calendar3 me-1"></i>{{ \Carbon\Carbon::parse($n->tanggal)->format('d/m/Y') }}
                                                        </small>
                                                    </div>
                                                    
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div style="max-width: 70%;">
                                                            <div class="fw-bold text-dark mb-0.5" style="font-size: 13px;">BAB {{ $n->id_bab ?? '?' }}</div>
                                                            <small class="text-muted text-truncate d-block" style="font-size: 11px;" title="{{ $n->nama_bab }}">{{ $n->nama_bab ?? 'Materi Tidak Diisi' }}</small>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="fw-bold text-primary fs-4" style="line-height: 1;">{{ $n->mingguan ?? '-' }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted small italic">Belum ada rincian data komponen nilai harian pada parameter filter ini.</div>
                        @endforelse
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Styling agar teks menu tetap terlihat jelas saat tidak aktif */
    .nav-tabs .nav-link {
        color: #6c757d !important; /* Warna abu-abu gelap agar terlihat jelas */
        border: 1px solid transparent;
        border-radius: 8px 8px 0 0;
        transition: all 0.3s ease;
    }

    /* Warna saat kursor diarahkan ke menu */
    .nav-tabs .nav-link:hover {
        color: #4e73df !important;
        background-color: #f8f9fc;
    }

    /* Styling menu saat aktif */
    .nav-tabs .nav-link.active {
        color: #4e73df !important; /* Warna biru primer saat aktif */
        background-color: #fff !important;
        border-top: 3px solid #4e73df !important;
        border-left: 1px solid #dee2e6 !important;
        border-right: 1px solid #dee2e6 !important;
        border-bottom: 2px solid #fff !important;
    }
</style>
@endsection