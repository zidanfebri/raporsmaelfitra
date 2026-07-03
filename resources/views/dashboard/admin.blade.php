@extends('layouts.main')

@section('content')
<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3" style="border-left: 5px solid #4e73df !important;">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <div class="small fw-bold text-primary text-uppercase mb-1">Total Guru</div>
                    <div class="h5 mb-0 fw-bold">{{ $guru->count() }}</div>
                </div>
                <i class="bi bi-person-badge fs-2 text-gray-300"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3" style="border-left: 5px solid #1cc88a !important;">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <div class="small fw-bold text-success text-uppercase mb-1">Total Siswa</div>
                    <div class="h5 mb-0 fw-bold">{{ $siswa->count() }}</div>
                </div>
                <i class="bi bi-people fs-2 text-gray-300"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3" style="border-left: 5px solid #f6c23e !important;">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <div class="small fw-bold text-warning text-uppercase mb-1">Total Kelas</div>
                    <div class="h5 mb-0 fw-bold">{{ $kelas->count() }}</div>
                </div>
                <i class="bi bi-door-open fs-2 text-gray-300"></i>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h6 class="m-0 fw-bold text-primary">Daftar Plotting Jadwal Pelajaran</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Hari</th>
                        <th>Mata Pelajaran</th>
                        <th>Guru</th>
                        <th>Kelas</th>
                        <th>Jam</th>
                        <th>tipe</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($jadwal as $j)
                    <tr>
                        <td>{{ $j->tanggal }}</td>
                        <td>{{ $j->hari }}</td>
                        <td>{{ $j->mapel->nama_mapel }}</td>
                        <td>{{ $j->guru->nama }}</td>
                        <td>{{ $j->kelas->nama_kelas }}</td>
                        <td>{{ $j->jam_mulai }}</td>
                        <td>{{ $j->tipe }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection