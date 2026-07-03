@extends('layouts.main')

@section('content')
{{-- Bagian Dashboard Utama --}}
<div class="card border-0 shadow-sm p-4 d-print-none">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h5 class="fw-bold m-0"><i class="bi bi-people me-2 text-primary"></i>Daftar Master Siswa</h5>
            <p class="text-muted small m-0">Kelola data siswa dan cetak daftar berdasarkan kelas.</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            {{-- UNDUH TEMPLATE --}}
            <button onclick="downloadTemplateExcel()" class="btn btn-outline-success btn-sm px-3 shadow-sm">
                <i class="bi bi-file-earmark-excel me-1"></i> Unduh Template
            </button>
            {{-- IMPOR DATA SISWA --}}
            <button class="btn btn-success btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalImporSiswa">
                <i class="bi bi-box-arrow-in-down-left me-1"></i> Impor Data Siswa
            </button>
            {{-- EKSPOR --}}
            <button onclick="eksporDataSiswa()" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                <i class="bi bi-box-arrow-up-right me-1"></i> Ekspor Data
            </button>
            <button class="btn btn-outline-danger btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCetakSiswa">
                <i class="bi bi-printer me-1"></i> Cetak Daftar
            </button>
            <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahSiswa">
                <i class="bi bi-plus-lg"></i> Tambah Manual
            </button>
            {{-- TOMBOL BARU: KOSONGKAN/HAPUS MASAL SEMUA DATA SISWA --}}
            <form action="{{ route('admin.siswa.delete_all') }}" method="POST" class="d-inline" onsubmit="return confirm('PERINGATAN KERAS!\n\nApakah Anda yakin ingin menghapus massal SELURUH data siswa dalam database? Tindakan ini tidak dapat dibatalkan.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm px-3 shadow-sm">
                    <i class="bi bi-exclamation-octagon me-1"></i> Hapus Semua Siswa
                </button>
            </form>
        </div>
    </div>

    <form action="{{ route('admin.siswa') }}" method="GET" class="row g-2 mb-4 border-bottom pb-3">
        <div class="col-md-3">
            <label class="small fw-bold text-muted">Cari Nama/NIS</label>
            <div class="input-group input-group-sm">
                <input type="text" name="search" class="form-control" placeholder="Ketik lalu enter..." value="{{ request('search') }}">
                <button class="btn btn-secondary" type="submit"><i class="bi bi-search"></i></button>
            </div>
        </div>
        <div class="col-md-3">
            <label class="small fw-bold text-muted">Filter Kelas</label>
            <select name="kelas_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                @foreach($kelas as $k)
                    <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <a href="{{ route('admin.siswa') }}" class="btn btn-sm btn-outline-secondary w-100">Reset Filter</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle" id="table-master-siswa">
            <thead class="table-light border-top">
                <tr>
                    <th width="120">NIS</th>
                    <th>Nama Lengkap</th>
                    <th>Kelas</th>
                    <th width="150" class="text-center">Status Aktif</th>
                    <th width="120" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($siswa as $s)
                <tr>
                    <td><span class="text-muted small fw-bold data-nisn">{{ $s->nisn ?? '-' }}</span></td>
                    <td class="fw-semibold text-dark data-nama">{{ $s->nama }}</td>
                    <td>
                        @if($s->kelas)
                            <span class="badge bg-info text-dark data-kelas">{{ $s->kelas->nama_kelas }}</span>
                        @else
                            <span class="text-muted small italic data-kelas">Belum di-plot</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <form action="{{ route('admin.user.update', $s->id) }}" method="POST" id="status-form-{{ $s->id }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="nama" value="{{ $s->nama }}">
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input custom-slider" type="checkbox" name="status" value="1" 
                                    role="switch" id="switch{{ $s->id }}" {{ $s->status ? 'checked' : '' }} 
                                    onchange="document.getElementById('status-form-{{ $s->id }}').submit()">
                                <label class="form-check-label small {{ $s->status ? 'text-success' : 'text-danger' }}" for="switch{{ $s->id }}">
                                    {{ $s->status ? 'Aktif' : 'Non-Aktif' }}
                                </label>
                            </div>
                        </form>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditSiswa{{ $s->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form action="{{ route('admin.user.delete', $s->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus siswa ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-5 text-muted italic">Data siswa tidak ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $siswa->links('pagination::bootstrap-5') }}
    </div>
</div>

{{-- MODAL IMPOR FILE --}}
<div class="modal fade" id="modalImporSiswa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-box-arrow-in-down-left me-2"></i>Impor Data Siswa via Excel</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.siswa.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="type_input" value="file_excel">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Pilih File Hasil Pengisian Template (.csv / .xls)<span class="text-danger">*</span></label>
                        <input type="file" name="file_siswa" class="form-control" accept=".csv, .txt, .xls, .xlsx" required>
                        <div class="form-text text-muted mt-2" style="font-size: 11px;">
                            Pastikan format file sesuai template (Kolom A: <code>nisn</code>, Kolom B: <code>nama</code>, Kolom C: <code>kelas_id</code>). NISN boleh menggunakan tanda titik.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4">Proses Impor</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH MANUAL --}}
<div class="modal fade" id="modalTambahSiswa" tabindex="-1" aria-labelledby="modalTambahSiswaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Tambah Siswa Manual</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.siswa.store') }}" method="POST">
                @csrf
                <input type="hidden" name="type_input" value="manual">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">NISN</label>
                        <input type="text" name="nisn" class="form-control" placeholder="Masukkan NISN siswa" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Lengkap Siswa</label>
                        <input type="text" name="nama" class="form-control" placeholder="Masukkan nama lengkap" required>
                    </div>
                    <div class="alert alert-info border-0 small">
                        <i class="bi bi-info-circle me-2"></i> Username dan password default akan diatur sistem otomatis.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($siswa as $s)
<div class="modal fade" id="modalEditSiswa{{ $s->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold text-dark">Edit Data Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.user.update', $s->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">NISN</label>
                        <input type="text" name="nisn" class="form-control" value="{{ $s->nisn }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Lengkap Siswa</label>
                        <input type="text" name="nama" class="form-control" value="{{ $s->nama }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning fw-bold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

<div class="modal fade" id="modalCetakSiswa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-printer me-2"></i>Cetak Daftar Siswa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold small">Pilih Kelas yang Ingin Dicetak</label>
                    <select id="select-cetak-kelas" class="form-select">
                        <option value="all" data-name="Semua Kelas">Semua Siswa (Halaman Ini)</option>
                        @foreach($kelas as $k)
                            <option value="kelas-{{ $k->id }}" data-name="Kelas {{ $k->nama_kelas }}">{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" onclick="prosesCetak()" class="btn btn-danger px-4">Mulai Cetak</button>
            </div>
        </div>
    </div>
</div>

<div id="print-area" class="d-none d-print-block">
    <div class="print-page p-5" style="color: black; background: white; font-family: 'Times New Roman', serif;">
        <div class="text-center mb-4 border-bottom border-3 border-dark pb-2">
            <h4 class="fw-bold mb-0 text-uppercase">Daftar Master Peserta Didik</h4>
            <h5 class="fw-bold">SMA EL FITRA</h5>
            <p class="small mb-0">Jl. Soekarno Hatta No. 04, Riung Bandung, Kota Bandung Jawa Barat 40292</p>
        </div>

        <table class="table table-bordered border-dark align-middle">
            <thead class="text-center small fw-bold" style="background-color: #f2f2f2 !important;">
                <tr>
                    <th width="5%">No</th>
                    <th width="15%">NISN</th>
                    <th>Nama Siswa</th>
                    <th width="10%">Kelas</th>
                    <th width="15%">Username</th>
                    <th width="15%">Password</th>
                </tr>
            </thead>
            <tbody class="small">
                @foreach($siswa as $s)
                <tr class="print-row" data-class="kelas-{{ $s->kelas_id ?? 'none' }}">
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-center">{{ $s->nisn }}</td>
                    <td class="fw-bold">{{ $s->nama }}</td>
                    <td class="text-center">{{ $s->kelas->nama_kelas ?? '-' }}</td>
                    <td class="text-center"><code>{{ $s->username }}</code></td>
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

<script>
    function downloadTemplateExcel() {
        const csvContent = "nisn,nama,kelas_id,,,REFERENSI ID KELAS (DILIHAT SAAT ISI DATA)\n2425.07.095,zidan febrian,5,,,ID_KELAS,NAMA_KELAS";
        let csvLines = [csvContent];
        
        @php $idxLoop = 0; @endphp
        @foreach($kelas as $k)
            @if($idxLoop > 0)
                csvLines.push(",,,,,,{{ $k->id }},{{ $k->nama_kelas }}");
            @endif
            @php $idxLoop++; @endphp
        @endforeach

        const csvString = csvLines.join("\n");
        const blob = new Blob([new Uint8Array([0xEF, 0xBB, 0xBF]), csvString], { type: "text/csv;charset=utf-8;" });
        const link = document.createElement("a");
        
        link.href = URL.createObjectURL(blob);
        link.setAttribute("download", "Template_Impor_Siswa_Dan_Kelas.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function eksporDataSiswa() {
        let csvRows = [];
        csvRows.push("nisn,nama,kelas_id,nama_kelas");

        const rows = document.querySelectorAll("#table-master-siswa tbody tr");
        rows.forEach(row => {
            const nisnEl = row.querySelector(".data-nisn");
            const namaEl = row.querySelector(".data-nama");
            const kelasEl = row.querySelector(".data-kelas");

            if (nisnEl && namaEl && kelasEl) {
                csvRows.push(`"${nisnEl.innerText.trim()}",${namaEl.innerText.trim()},,${kelasEl.innerText.trim()}`);
            }
        });

        if(csvRows.length <= 1) {
            alert("Tidak ada data.");
            return;
        }

        const csvString = csvRows.join("\n");
        const blob = new Blob([new Uint8Array([0xEF, 0xBB, 0xBF]), csvString], { type: "text/csv;charset=utf-8;" });
        const link = document.createElement("a");
        
        link.href = URL.createObjectURL(blob);
        link.setAttribute("download", "Data_Master_Siswa.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function prosesCetak() {
        const select = document.getElementById('select-cetak-kelas');
        const filterKelas = select.value;
        const namaKelas = select.options[select.selectedIndex].getAttribute('data-name');
        const rows = document.querySelectorAll('.print-row');
        
        rows.forEach(row => {
            if (filterKelas === 'all' || row.getAttribute('data-class') === filterKelas) {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        });

        const originalTitle = document.title;
        document.title = "Daftar Siswa " + namaKelas;

        const modalElement = document.getElementById('modalCetakSiswa');
        const modal = bootstrap.Modal.getInstance(modalElement);
        if(modal) modal.hide();

        setTimeout(() => {
            window.print();
            document.title = originalTitle;
        }, 500);
    }

    // Auto-hide alert dalam 3 detik
    document.addEventListener("DOMContentLoaded", function () {
        const alertElements = document.querySelectorAll('.alert');
        alertElements.forEach(function (alert) {
            setTimeout(function () {
                let bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 3000);
        });
    });
</script>
@endsection