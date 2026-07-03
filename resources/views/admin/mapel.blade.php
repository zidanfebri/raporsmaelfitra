@extends('layouts.main')

@section('content')
<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-4 h-100">
            <h5 class="fw-bold mb-4"><i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Mapel</h5>
            <form action="{{ route('admin.mapel.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Nama Mata Pelajaran</label>
                    <input type="text" name="nama_mapel" class="form-control py-2" placeholder="Contoh: Matematika" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 shadow-sm">
                    <i class="bi bi-save me-2"></i>Simpan Mapel
                </button>
            </form>
            <div class="mt-4 p-3 bg-light rounded-3">
                <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Gunakan nama mata pelajaran yang baku untuk memudahkan pembuatan jadwal pelajaran.</small>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm p-4 h-100">
            <h5 class="fw-bold mb-4"><i class="bi bi-book me-2 text-primary"></i>Daftar Mata Pelajaran</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle border-top">
                    <thead class="table-light">
                        <tr>
                            <th>Mata Pelajaran</th>
                            <th width="150" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mapel as $m)
                        <tr>
                            <td class="fw-bold text-dark">{{ $m->nama_mapel }}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <!-- Tombol Edit dengan Data Attributes -->
                                    <button class="btn btn-sm btn-outline-warning" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEditMapel"
                                            onclick="editMapel('{{ $m->id }}', '{{ $m->nama_mapel }}')"
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <!-- Tombol Hapus -->
                                    <form action="{{ route('admin.mapel.delete', $m->id) }}" method="POST" onsubmit="return confirm('Hapus mata pelajaran ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="text-center py-5 text-muted italic">Belum ada mata pelajaran tersedia.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Mapel -->
<div class="modal fade" id="modalEditMapel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Edit Mata Pelajaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditMapel" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Nama Mata Pelajaran</label>
                        <input type="text" name="nama_mapel" id="edit_nama_mapel" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Update Mapel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editMapel(id, nama) {
        // Set action form secara dinamis
        const form = document.getElementById('formEditMapel');
        form.action = `/admin/mapel/update/${id}`;
        
        // Isi input dengan nama yang lama
        document.getElementById('edit_nama_mapel').value = nama;
    }
</script>
@endsection