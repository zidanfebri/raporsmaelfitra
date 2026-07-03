@extends('layouts.main')
@section('content')
<div class="card border-0 shadow-sm p-4 d-print-none">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <h5 class="fw-bold m-0"><i class="bi bi-person-badge me-2 text-primary"></i>Daftar Guru & Wali Kelas</h5>
        
        <div class="d-flex gap-2">
            <button class="btn btn-outline-danger btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCetakGuru">
                <i class="bi bi-printer me-1"></i> Cetak Daftar Guru
            </button>
            <form action="{{ route('admin.guru') }}" method="GET" class="input-group input-group-sm shadow-sm" style="width: 250px;">
                <input type="text" name="search" class="form-control" placeholder="Cari nama guru..." value="{{ request('search') }}">
                <button class="btn btn-secondary" type="submit"><i class="bi bi-search"></i></button>
            </form>
            <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahGuru">
                <i class="bi bi-plus-lg"></i> Tambah Baru
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="bg-light border-top">
                <tr>
                    <th>Username</th>
                    <th>Nama Lengkap</th>
                    <th>Role</th>
                    <th width="150" class="text-center">Status Aktif</th>
                    <th width="120" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($guru as $g)
                <tr>
                    <td><code class="fw-bold text-primary">{{ $g->username }}</code></td>
                    <td class="fw-semibold text-dark">{{ $g->nama }}</td>
                    <td>
                        @if($g->role == 'walikelas')
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3">Wali Kelas</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3">Guru</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <form action="{{ route('admin.user.update', $g->id) }}" method="POST" id="status-form-{{ $g->id }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="nama" value="{{ $g->nama }}">
                            <input type="hidden" name="role" value="{{ $g->role }}">
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input custom-slider" type="checkbox" name="status" value="1" 
                                    role="switch" id="switch{{ $g->id }}" {{ $g->status ? 'checked' : '' }} 
                                    onchange="document.getElementById('status-form-{{ $g->id }}').submit()">
                                <label class="form-check-label small {{ $g->status ? 'text-success' : 'text-danger' }}" for="switch{{ $g->id }}">
                                    {{ $g->status ? 'Aktif' : 'Non-Aktif' }}
                                </label>
                            </div>
                        </form>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditGuru{{ $g->id }}"><i class="bi bi-pencil"></i></button>
                        <form action="{{ route('admin.user.delete', $g->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus guru ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-5 text-muted italic">Data guru tidak ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $guru->appends(request()->input())->links('pagination::bootstrap-5') }}
    </div>
</div>

@foreach($guru as $g)
<div class="modal fade" id="modalEditGuru{{ $g->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.user.update', $g->id) }}" method="POST">
            @csrf @method('PUT')
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold">Edit Data Guru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" value="{{ $g->nama }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Role</label>
                        <select name="role" class="form-select">
                            <option value="guru" {{ $g->role == 'guru' ? 'selected' : '' }}>Guru</option>
                            <option value="walikelas" {{ $g->role == 'walikelas' ? 'selected' : '' }}>Wali Kelas</option>
                        </select>
                    </div>
                    <input type="hidden" name="status" value="{{ $g->status }}">
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning fw-bold text-dark px-4">Update Data</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endforeach

<div class="modal fade" id="modalTambahGuru" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.guru.store') }}" method="POST">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Tambah Guru/Wali Kelas</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" placeholder="Nama Lengkap" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Role</label>
                        <select name="role" class="form-select">
                            <option value="guru">Guru</option>
                            <option value="walikelas">Wali Kelas</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary px-4">Simpan Data</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalCetakGuru" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-printer me-2"></i>Cetak Daftar Guru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold small">Pilih Kategori Role</label>
                    <select id="select-cetak-role" class="form-select">
                        <option value="all" data-name="Semua Guru">Semua Guru (Seluruh Halaman)</option>
                        <option value="guru" data-name="Daftar Guru">Guru Saja</option>
                        <option value="walikelas" data-name="Daftar Wali Kelas">Wali Kelas Saja</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" onclick="prosesCetakGuru()" class="btn btn-danger px-4">Mulai Cetak</button>
            </div>
        </div>
    </div>
</div>

{{-- AREA PRINT OUT SEBELUMNYA KITA MODIFIKASI MENGGUNAKAN VARIABEL $SEMUAGURU --}}
<div id="print-area" class="d-none d-print-block">
    <div class="print-page p-5" style="color: black; background: white; font-family: 'Times New Roman', serif;">
        <div class="text-center mb-4 border-bottom border-3 border-dark pb-2">
            <h4 class="fw-bold mb-0 text-uppercase">Daftar Tenaga Pendidik</h4>
            <h5 class="fw-bold">SMA EL FITRA</h5>
            <p class="small mb-0">Jl. Soekarno Hatta No. 04, Riung Bandung, Kota Bandung Jawa Barat 40292</p>
        </div>

        <table class="table table-bordered border-dark align-middle">
            <thead class="text-center small fw-bold" style="background-color: #f2f2f2 !important;">
                <tr>
                    <th width="5%">No</th>
                    <th>Nama Lengkap</th>
                    <th width="20%">Role</th>
                    <th width="20%">Username</th>
                    <th width="20%">Password</th>
                </tr>
            </thead>
            <tbody class="small">
                {{-- AMBIL DATA DARI VARIABEL KUMPULAN SEMUA DATA TANPA PAGINATION --}}
                @foreach($semuaGuru as $g)
                <tr class="print-row-guru" data-role="{{ $g->role }}">
                    {{-- Class index-number-render digunakan untuk reset urutan nomor via JS --}}
                    <td class="text-center index-number-render"></td>
                    <td class="fw-bold text-dark">{{ $g->nama }}</td>
                    <td class="text-center">{{ $g->role == 'walikelas' ? 'Wali Kelas' : 'Guru' }}</td>
                    <td class="text-center"><code>{{ $g->username }}</code></td>
                    <td class="text-center">password</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="row mt-5 small">
            <div class="col-8"></div>
            <div class="col-4 text-center">
                <p>Bandung, {{ date('d F Y') }}<br>Administrator,</p>
                <div style="height: 70px;"></div>
                <p class="fw-bold" style="text-decoration: underline;">{{ Auth::user()->nama }}</p>
            </div>
        </div>
    </div>
</div>

<style>
    @page { size: auto; margin: 0mm; }
    
    @media print {
        body * { visibility: hidden; }
        #print-area, #print-area * { visibility: visible; }
        #print-area { position: absolute; left: 0; top: 0; width: 100%; }
        .print-page { min-height: 100vh; padding: 2cm !important; box-sizing: border-box; background: white !important; }
        table { border-collapse: collapse !important; width: 100% !important; }
        .table-bordered th, .table-bordered td { border: 1px solid black !important; }
        .d-print-none { display: none !important; }
        code { font-family: monospace; color: black !important; background: none !important; padding: 0 !important; }
    }

    .custom-slider { cursor: pointer; width: 3em !important; height: 1.5em !important; }
</style>

<script>
    function prosesCetakGuru() {
        const select = document.getElementById('select-cetak-role');
        const filterRole = select.value;
        const namaDokumen = select.options[select.selectedIndex].getAttribute('data-name');
        const rows = document.querySelectorAll('.print-row-guru');
        
        let nomorUrutBaru = 1;

        // Saring baris berdasarkan role dan atur ulang nomor urutnya secara dinamis
        rows.forEach(row => {
            if (filterRole === 'all' || row.getAttribute('data-role') === filterRole) {
                row.style.display = 'table-row';
                // Masukkan nomor urut baru yang urut murni ke kolom No
                row.querySelector('.index-number-render').innerText = nomorUrutBaru;
                nomorUrutBaru++;
            } else {
                row.style.display = 'none';
            }
        });

        // Ubah Judul Dokumen agar saat Save PDF namanya sesuai
        const originalTitle = document.title;
        document.title = namaDokumen;

        // Tutup modal
        const modalElement = document.getElementById('modalCetakGuru');
        const modal = bootstrap.Modal.getInstance(modalElement);
        if(modal) modal.hide();

        setTimeout(() => {
            window.print();
            document.title = originalTitle;
        }, 500);
    }
</script>
@endsection