@extends('layouts.main')
@section('content')
<div class="card border-0 shadow-sm p-4">
    <h5 class="fw-bold mb-4"><i class="bi bi-diagram-3 me-2"></i>Manajemen Plotting Siswa</h5>
    
    <div class="row">
        @foreach($kelas as $k)
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h5 class="fw-bold text-primary">{{ $k->nama_kelas }}</h5>
                        <i class="bi bi-folder2-open fs-4 text-muted"></i>
                    </div>
                    <p class="text-muted">{{ $k->siswa_count }} Siswa Terdaftar</p>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('admin.plotting.detail', $k->id) }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-eye me-1"></i> Kelola Siswa
                        </a>
                        <div class="d-flex gap-2">
                             <form action="{{ route('admin.kelas.delete', $k->id) }}" method="POST" onsubmit="return confirm('Hapus kelas dan semua plotting di dalamnya?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection