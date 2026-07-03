@extends('layouts.main')
@section('content')
<div class="card border-0 shadow-sm p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold m-0"><i class="bi bi-calendar-check me-2 text-primary"></i>Atur Jadwal Pelajaran Ujian (UTS & UAS)</h5>
        <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalJadwal">
            <i class="bi bi-plus-lg"></i> Tambah Jadwal
        </button>
    </div>

    <form action="{{ route('admin.jadwal.ujian') }}" method="GET" class="row g-2 mb-4 border-bottom pb-3">
        <div class="col-md-2">
            <label class="small fw-bold text-muted">Semester</label>
            <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Semua</option>
                <option value="1" {{ request('semester') == '1' ? 'selected' : '' }}>Semester 1</option>
                <option value="2" {{ request('semester') == '2' ? 'selected' : '' }}>Semester 2</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="small fw-bold text-muted">Tahun Pelajaran</label>
            {{-- PERBAIKAN: Mengubah input text menjadi Dropdown Select --}}
            <select name="tahun_pelajaran" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Semua</option>
                @foreach(['2025/2026', '2026/2027', '2027/2028', '2028/2029', '2029/2030'] as $tp_opt)
                    <option value="{{ $tp_opt }}" {{ request('tahun_pelajaran') == $tp_opt ? 'selected' : '' }}>{{ $tp_opt }}</option>
                @endforeach
            </select>
        </div>
        {{-- FILTER KELAS --}}
        <div class="col-md-2">
            <label class="small fw-bold text-muted">Kelas</label>
            <select name="kelas_filter" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                @foreach($kelas as $k)
                    <option value="{{ $k->id }}" {{ request('kelas_filter') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="small fw-bold text-muted">Mata Pelajaran</label>
            <select name="mapel_filter" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Semua Mapel</option>
                @foreach($mapel as $m)
                    <option value="{{ $m->id }}" {{ request('mapel_filter') == $m->id ? 'selected' : '' }}>{{ $m->nama_mapel }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <a href="{{ route('admin.jadwal.ujian') }}" class="btn btn-sm btn-outline-secondary w-100">Reset Filter</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle border-top">
            <thead class="table-light">
                <tr>
                    <th>Tipe Ujian</th>
                    <th>Semester/TP</th>
                    <th>Hari/Tanggal</th>
                    <th>Mata Pelajaran</th>
                    <th>Guru</th>
                    <th>Kelas</th>
                    <th>Waktu</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                {{-- FIX SINKRONISASI VARIABEL: Mengubah $jadwal menjadi $jadwalUjian sesuai kiriman Controller --}}
                @forelse($jadwalUjian as $j)
                <tr>
                    <td>
                        <span class="badge {{ $j->tipe == 'uts' ? 'bg-primary' : 'bg-success' }} text-uppercase">{{ $j->tipe }}</span>
                    </td>
                    <td>
                        <small class="fw-bold">Sem: {{ $j->semester }}</small><br>
                        <small class="text-muted small">{{ $j->tahun_pelajaran }}</small>
                    </td>
                    <td>
                        <span class="badge bg-dark mb-1">{{ $j->hari }}</span><br>
                        <small class="text-muted">{{ \Carbon\Carbon::parse($j->tanggal)->format('d/m/Y') }}</small>
                    </td>
                    <td class="fw-bold text-primary">{{ $j->mapel->nama_mapel }}</td>
                    <td>{{ $j->guru->nama }}</td>
                    <td><span class="badge bg-info text-dark">{{ $j->kelas->nama_kelas }}</span></td>
                    <td>
                        <code class="fw-bold text-primary">{{ $j->jam_mulai }}</code> - 
                        <code class="fw-bold text-danger">{{ $j->jam_akhir }}</code>
                    </td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-1">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#copyJadwal{{ $j->id }}" title="Salin Jadwal ke Tanggal Lain">
                                <i class="bi bi-files"></i> Salin
                            </button>
                            
                            <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editJadwal{{ $j->id }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            
                            <form action="{{ route('admin.jadwal.delete', $j->id) }}" method="POST" onsubmit="return confirm('Hapus jadwal ujian ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>

                {{-- MODAL SALIN JADWAL --}}
                <div class="modal fade" id="copyJadwal{{ $j->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-md">
                        <form action="{{ route('admin.jadwal.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="semester" value="{{ $j->semester }}">
                            <input type="hidden" name="tahun_pelajaran" value="{{ $j->tahun_pelajaran }}">
                            <input type="hidden" name="jam_mulai" value="{{ $j->jam_mulai }}">
                            <input type="hidden" name="jam_akhir" value="{{ $j->jam_akhir }}">

                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-primary text-white">
                                    <h6 class="modal-title fw-bold"><i class="bi bi-files me-2"></i>Duplikasi Jadwal Pelajaran</h6>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="p-3 bg-light rounded mb-3 small text-muted">
                                        <strong>Detail Waktu Asli:</strong> Jam {{ $j->jam_mulai }} - {{ $j->jam_akhir }}
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold small text-dark">Tipe Jadwal</label>
                                        <select name="tipe" class="form-select" required>
                                            <option value="uts" {{ $j->tipe == 'uts' ? 'selected' : '' }}>UTS</option>
                                            <option value="uas" {{ $j->tipe == 'uas' ? 'selected' : '' }}>UAS</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small text-dark">Hari</label>
                                        <select name="hari" class="form-select" required>
                                            @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $h)
                                                <option value="{{ $h }}" {{ $j->hari == $h ? 'selected' : '' }}>{{ $h }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold small text-dark">Kelas</label>
                                        <select name="kelas_id" class="form-select" required>
                                            @foreach($kelas as $k)
                                                <option value="{{ $k->id }}" {{ $j->kelas_id == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold small text-dark">Mata Pelajaran</label>
                                        <select name="mapel_id" class="form-select select-mapel-copy" data-target="select-guru-copy{{ $j->id }}" required>
                                            @foreach($mapel as $m)
                                                <option value="{{ $m->id }}" {{ $j->mapel_id == $m->id ? 'selected' : '' }}>{{ $m->nama_mapel }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold small text-dark">Guru Pengampu</label>
                                        <select name="guru_id" id="select-guru-copy{{ $j->id }}" class="form-select" required>
                                            <option value="{{ $j->guru_id }}">{{ $j->guru->nama }}</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold small text-dark">Pilih Tanggal Baru <span class="text-danger">*</span></label>
                                        <input type="date" name="tanggal" class="form-control" required>
                                    </div>
                                </div>
                                <div class="modal-footer py-2">
                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">Tempel Jadwal</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- MODAL EDIT JADWAL --}}
                <div class="modal fade" id="editJadwal{{ $j->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <form action="{{ route('admin.jadwal.update', $j->id) }}" method="POST">
                            @csrf @method('PUT')
                            
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-warning">
                                    <h5 class="modal-title fw-bold">Edit Jadwal Pelajaran Ujian</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold small">Tipe Jadwal</label>
                                            <select name="tipe" class="form-select" required>
                                                <option value="uts" {{ $j->tipe == 'uts' ? 'selected' : '' }}>UTS</option>
                                                <option value="uas" {{ $j->tipe == 'uas' ? 'selected' : '' }}>UAS</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold small">Semester</label>
                                            <select name="semester" class="form-select" required>
                                                <option value="1" {{ $j->semester == '1' ? 'selected' : '' }}>1 (Ganjil)</option>
                                                <option value="2" {{ $j->semester == '2' ? 'selected' : '' }}>2 (Genap)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold small">Tahun Pelajaran</label>
                                            <input type="text" name="tahun_pelajaran" class="form-control" value="{{ $j->tahun_pelajaran }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">Hari</label>
                                            <select name="hari" class="form-select" required>
                                                @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $h)
                                                    <option value="{{ $h }}" {{ $j->hari == $h ? 'selected' : '' }}>{{ $h }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">Tanggal</label>
                                            <input type="date" name="tanggal" class="form-control" value="{{ $j->tanggal }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold small">Jam Mulai</label>
                                            <input type="time" name="jam_mulai" class="form-control" value="{{ $j->jam_mulai }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold small">Jam Selesai</label>
                                            <input type="time" name="jam_akhir" class="form-control" value="{{ $j->jam_akhir }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold small">Mata Pelajaran</label>
                                            <select name="mapel_id" class="form-select select-mapel-edit" data-target="select-guru-edit{{ $j->id }}" required>
                                                @foreach($mapel as $m)
                                                    <option value="{{ $m->id }}" {{ $j->mapel_id == $m->id ? 'selected' : '' }}>{{ $m->nama_mapel }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold small">Kelas</label>
                                            <select name="kelas_id" class="form-select" required>
                                                @foreach($kelas as $k)
                                                    <option value="{{ $k->id }}" {{ $j->kelas_id == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label fw-bold small">Guru Pengampu</label>
                                            <select name="guru_id" id="select-guru-edit{{ $j->id }}" class="form-select" required>
                                                <option value="{{ $j->guru_id }}">{{ $j->guru->nama }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-warning px-4 fw-bold">Update Jadwal</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @empty
                <tr><td colspan="8" class="text-center py-5 text-muted">Data jadwal ujian tidak ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{-- FIX SINKRONISASI PAGINATION --}}
        {{ $jadwalUjian->appends(request()->input())->links('pagination::bootstrap-5') }}
    </div>
</div>

{{-- MODAL TAMBAH JADWAL BARU --}}
<div class="modal fade" id="modalJadwal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('admin.jadwal.store') }}" method="POST">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Buat Jadwal Pelajaran Ujian Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Tipe Jadwal</label>
                            <select name="tipe" class="form-select" required>
                                <option value="uts">UTS</option>
                                <option value="uas">UAS</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Semester</label>
                            <select name="semester" class="form-select" required>
                                <option value="1">1 (Ganjil)</option>
                                <option value="2">2 (Genap)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Tahun Pelajaran</label>
                            <select name="tahun_pelajaran" class="form-select" required>
                                @foreach(['2025/2026', '2026/2027', '2027/2028', '2028/2029', '2029/2030'] as $tp_opt)
                                    <option value="{{ $tp_opt }}">{{ $tp_opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Hari</label>
                            <select name="hari" class="form-select" required>
                                <option value="Senin">Senin</option>
                                <option value="Selasa">Selasa</option>
                                <option value="Rabu">Rabu</option>
                                <option value="Kamis">Kamis</option>
                                <option value="Jumat">Jumat</option>
                                <option value="Sabtu">Sabtu</option>
                                <option value="Minggu">Minggu</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Jam Mulai</label>
                            <input type="time" name="jam_mulai" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Jam Selesai</label>
                            <input type="time" name="jam_akhir" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Kelas</label>
                            <select name="kelas_id" class="form-select" required>
                                <option value="">-- Pilih Kelas --</option>
                                @foreach($kelas as $k)
                                    <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Mata Pelajaran</label>
                            <select name="mapel_id" id="select-mapel" class="form-select" required>
                                <option value="">-- Pilih Mapel --</option>
                                @foreach($mapel as $m)
                                    <option value="{{ $m->id }}">{{ $m->nama_mapel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold small">Guru Pengampu</label>
                            <select name="guru_id" id="select-guru" class="form-select" required disabled>
                                <option value="">-- Pilih Mata Pelajaran Dulu --</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary px-4">Simpan Jadwal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function fetchGuru(mapelId, targetSelectId) {
    const guruSelect = document.getElementById(targetSelectId);
    if(!mapelId) {
        guruSelect.innerHTML = '<option value="">-- Pilih Mapel Dulu --</option>';
        guruSelect.disabled = true;
        return;
    }
    guruSelect.innerHTML = '<option value="">Sedang memuat...</option>';
    guruSelect.disabled = true;
    fetch(`/admin/get-guru-by-mapel/${mapelId}`)
        .then(response => response.json())
        .then(data => {
            guruSelect.innerHTML = '<option value="">-- Pilih Guru --</option>';
            if(data.length > 0) {
                data.forEach(guru => {
                    guruSelect.innerHTML += `<option value="${guru.id}">${guru.nama}</option>`;
                });
                guruSelect.disabled = false;
            } else {
                guruSelect.innerHTML = '<option value="">Tidak ada guru untuk mapel ini</option>';
            }
        });
}

document.getElementById('select-mapel').addEventListener('change', function() {
    fetchGuru(this.value, 'select-guru');
});

document.querySelectorAll('.select-mapel-edit').forEach(select => {
    select.addEventListener('change', function() {
        const targetId = this.getAttribute('data-target');
        fetchGuru(this.value, targetId);
    });
});

document.querySelectorAll('.select-mapel-copy').forEach(select => {
    select.addEventListener('change', function() {
        const targetId = this.getAttribute('data-target');
        fetchGuru(this.value, targetId);
    });
});
</script>
@endsection