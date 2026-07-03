@extends('layouts.main')

@section('content')
<div class="row mb-4">
    <div class="col">
        <h4 class="fw-bold"><i class="bi bi-grid-3x3-gap-fill me-2 text-success"></i>Buku Leger Kumpulan Nilai Akhir Kelas</h4>
        <p class="text-muted">Kompilasi otomatis rapot seluruh siswa, perhitungan jumlah nilai, rata-rata, dan pemeringkatan rangking otomatis.</p>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4 bg-light">
    <div class="card-body p-3">
        <form action="{{ route('wali.leger') }}" method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="small fw-bold text-muted mb-1">Semester</label>
                <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="1" {{ request('semester') == '1' ? 'selected' : '' }}>Semester 1</option>
                    <option value="2" {{ request('semester') == '2' ? 'selected' : '' }}>Semester 2</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="small fw-bold text-muted mb-1">Tahun Pelajaran</label>
                <select name="tahun_pelajaran" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach(['2025/2026', '2026/2027', '2027/2028', '2028/2029', '2029/2030'] as $tp_opt)
                        <option value="{{ $tp_opt }}" {{ request('tahun_pelajaran','2025/2026') == $tp_opt ? 'selected' : '' }}>{{ $tp_opt }}</option>
                    @endforeach
                </select>
            </div>
            @if(Auth::user()->role == 'admin')
            <div class="col-md-3">
                <label class="small fw-bold text-muted mb-1">Pilih Unit Kelas</label>
                <select name="kelas_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Pilih Kelas --</option>
                    @foreach($kelas as $k)
                        <option value="{{ $k->id }}" {{ $kelas_id == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-2 shadow-none">
                <a href="{{ route('wali.leger') }}" class="btn btn-sm btn-outline-secondary w-100">Reset Filter</a>
            </div>
        </form>
    </div>
</div>

@if($kelas_id && count($siswaLeger) > 0)
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white pt-3 border-0 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold m-0"><i class="bi bi-table me-1"></i> Data Transkrip Leger Kelas: {{ $infoKelas->nama_kelas }}</h6>
        <div class="d-flex gap-2">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}" class="btn btn-sm btn-success px-3 d-print-none">
                <i class="bi bi-file-earmark-excel-fill me-1"></i> Unduh Format Excel (.xls)
            </a>
        </div>
    </div>
    <div class="card-body p-0 mt-2">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0 text-center small" style="font-size:12px;">
                <thead class="table-dark align-middle">
                    <tr>
                        <th width="3%">No</th>
                        <th width="15%" class="text-start ps-3">Nama Lengkap Siswa</th>
                        <th width="8%">NIS</th>
                        @foreach($allMapelsSorted as $m)
                            <th title="{{ $m->nama_mapel }}">{{ Str::limit($m->nama_mapel, 12) }}</th>
                        @endforeach
                        <th class="bg-primary text-white" width="6%">Jumlah</th>
                        <th class="bg-info text-dark" width="6%">Rata-Rata</th>
                        <th class="bg-danger text-white" width="5%">Rangking</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($siswaLeger as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="text-start fw-bold text-uppercase text-dark ps-3">{{ $row['nama'] }}</td>
                        <td>{{ $row['nisn'] }}</td>
                        @foreach($allMapelsSorted as $m)
                            <td class="fw-bold text-secondary">{{ $row['scores'][$m->id] ?? '-' }}</td>
                        @endforeach
                        <td class="fw-bold bg-primary bg-opacity-10 text-primary">{{ $row['total'] }}</td>
                        <td class="fw-bold bg-info bg-opacity-10 text-info">{{ $row['rata_rata'] }}</td>
                        <td class="fw-bold bg-danger bg-opacity-10 text-danger fs-6">{{ $row['rangking'] }}</td>
                    </tr>
                    @endforeach

                    <tr class="table-light border-top border-dark">
                        <td colspan="3" class="text-end fw-bold bg-light">JUMLAH NILAI</td>
                        @foreach($allMapelsSorted as $m)
                            <td class="fw-bold text-dark bg-light">{{ $statMapel[$m->id]['jumlah'] ?? 0 }}</td>
                        @endforeach
                        <td colspan="3" class="bg-light"></td>
                    </tr>
                    <tr class="table-light">
                        <td colspan="3" class="text-end fw-bold bg-light">RATA-RATA MAPEL</td>
                        @foreach($allMapelsSorted as $m)
                            <td class="fw-bold text-primary bg-light bg-opacity-25">{{ $statMapel[$m->id]['rata_rata'] ?? 0 }}</td>
                        @endforeach
                        <td colspan="3" class="bg-light"></td>
                    </tr>
                    <tr class="table-light">
                        <td colspan="3" class="text-end fw-bold bg-light">NILAI TERTINGGI</td>
                        @foreach($allMapelsSorted as $m)
                            <td class="fw-bold text-success bg-light">{{ $statMapel[$m->id]['terbesar'] ?? 0 }}</td>
                        @endforeach
                        <td colspan="3" class="bg-light"></td>
                    </tr>
                    <tr class="table-light">
                        <td colspan="3" class="text-end fw-bold bg-light">NILAI TERENDAH</td>
                        @foreach($allMapelsSorted as $m)
                            <td class="fw-bold text-danger bg-light">{{ $statMapel[$m->id]['terkecil'] ?? 0 }}</td>
                        @endforeach
                        <td colspan="3" class="bg-light"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@elseif($kelas_id)
    <div class="alert alert-warning text-center">Belum ada data nilai terkalkulasi pada kombinasi filter ini.</div>
@else
    <div class="alert alert-info text-center">Silakan tentukan filter kelas dan parameter tahun ajaran untuk merender lembar buku leger.</div>
@endif
@endsection