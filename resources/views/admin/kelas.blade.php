@extends('layouts.main')

@section('content')
<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-4 h-100">
            <h5 class="fw-bold mb-4"><i class="bi bi-plus-circle me-2"></i>Tambah Kelas</h5>
            <form action="{{ route('admin.kelas.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Nama Kelas</label>
                    <input type="text" name="nama_kelas" class="form-control" placeholder="Contoh: X A" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="bi bi-save me-2"></i>Simpan Kelas
                </button>
            </form>
            <div class="mt-4 p-3 bg-light rounded-3">
                <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Gunakan format yang konsisten untuk memudahkan pengelolaan jadwal dan plotting.</small>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm p-4 h-100">
            <h5 class="fw-bold mb-4"><i class="bi bi-door-open me-2"></i>Daftar Kelas Terdaftar</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle border-top">
                    <thead class="table-light">
                        <tr>
                            <th width="80" class="text-center">ID</th>
                            <th>Nama Kelas</th>
                            <th width="120" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kelas as $k)
                        <tr>
                            <td class="text-center text-muted small">{{ $k->id }}</td>
                            <td class="fw-bold text-dark">{{ $k->nama_kelas }}</td>
                            <td class="text-center">
                                <form action="{{ route('admin.kelas.delete', $k->id) }}" method="POST" onsubmit="return confirm('Hapus kelas ini? Plotting siswa di kelas ini akan dikosongkan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger px-3">
                                        <i class="bi bi-trash"></i> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-5 text-muted italic">Belum ada data kelas tersedia.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4 d-flex justify-content-center">
                {{ $kelas->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection