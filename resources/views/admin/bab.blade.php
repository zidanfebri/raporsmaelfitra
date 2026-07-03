@extends('layouts.main')

@section('content')
<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-4 h-100">
            <h5 class="fw-bold mb-4"><i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Bab</h5>
            <form action="{{ route('admin.bab.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">NOMOR BAB</label>
                    <input type="number" name="nomor_bab" class="form-control py-2" placeholder="Contoh: 1" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">NAMA BAB</label>
                    <input type="text" name="nama_bab" class="form-control py-2" placeholder="Contoh: Sistem Pencernaan" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">GURU PENGAMPU</label>
                    <select name="guru_id" class="form-select py-2" required>
                        <option value="" disabled selected>-- Pilih Guru --</option>
                        @foreach($guru as $g)
                            <option value="{{ $g->id }}">{{ $g->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 shadow-sm">
                    <i class="bi bi-save me-2"></i>Simpan Bab
                </button>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm p-4 h-100">
            <h5 class="fw-bold mb-4"><i class="bi bi-journal-text me-2 text-primary"></i>Daftar Bab Pelajaran</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle border-top">
                    <thead class="table-light">
                        <tr>
                            <th width="80">No. Bab</th>
                            <th>Nama Bab</th>
                            <th>Guru</th>
                            <th width="120" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bab as $b)
                        <tr>
                            <td class="text-center fw-bold">{{ $b->nomor_bab }}</td>
                            <td>{{ $b->nama_bab }}</td>
                            <td><small class="fw-semibold text-primary">{{ $b->guru->nama }}</small></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <button class="btn btn-sm btn-outline-warning" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEditBab"
                                            onclick="editBab('{{ $b->id }}', '{{ $b->nomor_bab }}', '{{ $b->nama_bab }}', '{{ $b->guru_id }}')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('admin.bab.delete', $b->id) }}" method="POST" onsubmit="return confirm('Hapus bab ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-5 text-muted italic">Belum ada data bab.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditBab" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Edit Bab</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditBab" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">NOMOR BAB</label>
                        <input type="number" name="nomor_bab" id="edit_nomor_bab" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">NAMA BAB</label>
                        <input type="text" name="nama_bab" id="edit_nama_bab" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">GURU PENGAMPU</label>
                        <select name="guru_id" id="edit_guru_id" class="form-select" required>
                            @foreach($guru as $g)
                                <option value="{{ $g->id }}">{{ $g->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Update Bab</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editBab(id, nomor, nama, guru_id) {
        const form = document.getElementById('formEditBab');
        form.action = `/admin/bab/update/${id}`;
        document.getElementById('edit_nomor_bab').value = nomor;
        document.getElementById('edit_nama_bab').value = nama;
        document.getElementById('edit_guru_id').value = guru_id;
    }
</script>
@endsection