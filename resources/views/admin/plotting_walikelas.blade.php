@extends('layouts.main')

@section('content')
<div class="card border-0 shadow-sm p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold m-0"><i class="bi bi-person-check me-2"></i>Plotting Wali Kelas</h5>
            <p class="text-muted small m-0">Hubungkan Guru (Wali Kelas) ke kelas yang mereka pimpin.</p>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalPlotWali" onclick="prepareAdd()">
            <i class="bi bi-plus-lg me-1"></i> Plot Wali Kelas
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="bg-light">
                <tr>
                    <th>Nama Kelas</th>
                    <th>Wali Kelas Terpilih</th>
                    <th>Username Guru</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($kelas as $k)
                <tr>
                    <td class="fw-bold">{{ $k->nama_kelas }}</td>
                    <td>
                        @if($k->walikelas)
                            <span class="text-dark">{{ $k->walikelas->nama }}</span>
                        @else
                            <span class="text-danger small italic">Belum ditentukan</span>
                        @endif
                    </td>
                    <td>
                        <code class="text-primary">{{ $k->walikelas->username ?? '-' }}</code>
                    </td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-2">
                            <!-- Tombol Edit: Mengirim data ke fungsi JavaScript -->
                            <button class="btn btn-sm btn-outline-warning" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalPlotWali"
                                    onclick="prepareEdit('{{ $k->id }}', '{{ $k->walikelas->id ?? '' }}')">
                                <i class="bi bi-pencil"></i>
                            </button>

                            @if($k->walikelas)
                            <form action="{{ route('admin.plotting.walikelas.clear', $k->walikelas->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Wali Kelas dari kelas ini?')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-eraser"></i> Clear
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- ==========================================
     MODAL PLOT WALI KELAS (TAMBAHKAN KODE INI)
     ========================================== -->
<div class="modal fade" id="modalPlotWali" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalTitle">Plot Wali Kelas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.plotting.walikelas.update') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Pilih Kelas</label>
                        <select name="kelas_id" id="selectKelas" class="form-select" required>
                            <option value="" disabled selected>-- Pilih Kelas --</option>
                            @foreach($kelas as $k)
                                <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Pilih Guru (Wali Kelas)</label>
                        <select name="user_id" id="selectGuru" class="form-select" required>
                            <option value="" disabled selected>-- Pilih Guru --</option>
                            @foreach($walikelas as $w)
                                <option value="{{ $w->id }}">{{ $w->nama }} ({{ $w->username }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Fungsi untuk Reset Modal saat klik tambah baru
    function prepareAdd() {
        document.getElementById('modalTitle').innerText = 'Plot Wali Kelas';
        document.getElementById('selectKelas').value = '';
        document.getElementById('selectGuru').value = '';
        document.getElementById('selectKelas').disabled = false;
    }

    // Fungsi untuk Mengisi Modal saat klik edit
    function prepareEdit(kelasId, guruId) {
        document.getElementById('modalTitle').innerText = 'Edit Wali Kelas';
        document.getElementById('selectKelas').value = kelasId;
        document.getElementById('selectGuru').value = guruId;
        
        // Opsional: Kunci kelas agar tidak bisa diubah saat edit, hanya ganti gurunya
        // document.getElementById('selectKelas').disabled = true; 
    }
</script>
@endsection