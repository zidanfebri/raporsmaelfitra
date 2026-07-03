@extends('layouts.main')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="{{ route('admin.plotting') }}" class="btn btn-light"><i class="bi bi-arrow-left me-2"></i>Kembali</a>
    <h4 class="fw-bold m-0">Plotting Siswa: <span class="text-primary">{{ $kelas->nama_kelas }}</span></h4>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm p-4">
            <h6 class="fw-bold mb-3">Tambah Siswa ke Kelas</h6>
            <form action="{{ route('admin.plotting.update') }}" method="POST">
                @csrf
                <input type="hidden" name="kelas_id" value="{{ $kelas->id }}">
                <div class="mb-3">
                    <label class="small fw-bold">Pilih Siswa (Belum Berkelas)</label>
                    <select name="siswa_id" class="form-select" required>
                        <option value="">-- Pilih Siswa --</option>
                        @foreach($siswaTersedia as $st)
                            <option value="{{ $st->id }}">{{ $st->username }} - {{ $st->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-plus-lg me-1"></i> Tambahkan ke Kelas
                </button>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm p-4">
            <h6 class="fw-bold mb-3">Daftar Siswa di {{ $kelas->nama_kelas }}</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th>Nisn</th>
                            <th>Username</th>
                            <th>Nama Siswa</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($siswaDiKelas as $sdk)
                        <tr>
                            <td>{{ $sdk->nisn }}</td>
                            <td><code>{{ $sdk->username }}</code></td>
                            <td>{{ $sdk->nama }}</td>
                            <td class="text-center">
                                <form action="{{ route('admin.plotting.remove', $sdk->id) }}" method="POST" onsubmit="return confirm('Keluarkan siswa ini dari kelas?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger shadow-sm">
                                        <i class="bi bi-person-x me-1"></i> Keluarkan
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-4 text-muted">Belum ada siswa di kelas ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection