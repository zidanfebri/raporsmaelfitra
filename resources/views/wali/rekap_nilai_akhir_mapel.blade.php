@extends('layouts.main')

@section('content')
<style>
    /* ================= ATURAN LAYOUT MONITOR UTAMA ================= */
    .table-responsive-fix {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* ================= ATURAN PATEN MESIN CETAK (A4 PORTRAIT) ================= */
    @media print {
        /* Netralkan sisa space layout aplikasi */
        html, body {
            background-color: #fff !important;
            color: #000 !important;
            font-family: 'Segoe UI', Arial, sans-serif !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        .main-content {
            margin-left: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
        }

        /* Sembunyikan elemen non-cetak */
        .d-print-none, .sidebar, .navbar-custom, footer, .btn, .alert, .nav, .badge-primary {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
            background: transparent !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .table-responsive, .table-responsive-fix {
            overflow: visible !important;
            overflow-x: visible !important;
            white-space: normal !important;
        }

        @page {
            size: A4 portrait;
            margin: 10mm 8mm 10mm 8mm;
        }

        /* KUNCI UTAMA: Memaksa tabel menggunakan tata letak tetap (Fixed Layout) agar tidak bergeser */
        .table-matrix-print {
            width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
            word-wrap: break-word !important;
        }

        .table-matrix-print th, .table-matrix-print td {
            border: 1px solid #000 !important;
            padding: 5px 2px !important;
            vertical-align: middle !important;
            text-align: center !important;
            line-height: 1.2 !important;
        }

        /* Desain Header Cetak */
        .table-matrix-print thead tr th {
            background-color: #f8f9fc !important;
            color: #000 !important;
            font-size: 8.5px !important;
            font-weight: bold !important;
        }

        .table-matrix-print tbody tr td {
            background-color: #fff !important;
            color: #000 !important;
            font-size: 9px !important;
        }

        /* Alokasi Lebar Kolom Secara Presisi & Mutlak (Total 100%) */
        .col-print-no { width: 4% !important; }
        .col-print-nama { width: 22% !important; text-align: left !important; padding-left: 5px !important; }
        .col-print-nisn { width: 10% !important; }
        .col-print-bab { font-size: 8.5px !important; } /* Kolom dinamis sisa space */
        .col-print-rata { width: 7% !important; background-color: #f8f9fc !important; }
        .col-print-porsi-h { width: 8% !important; }
        .col-print-uas { width: 7% !important; }
        .col-print-porsi-u { width: 8% !important; }
        .col-print-akhir { width: 8% !important; background-color: #eee !important; font-weight: bold !important; }
        .col-print-rangking { width: 6% !important; }

        /* Paksa teks nama memotong ke bawah jika terlalu panjang, bukan melebar ke samping */
        .text-nama-wrap {
            text-align: left !important;
            white-space: normal !important;
            word-break: break-word !important;
            display: block;
        }
    }
</style>

<div id="kop-cetak-pdf" class="d-none text-center mb-4" style="font-family: 'Segoe UI', Arial, sans-serif;">
    <h3 style="margin: 0; text-transform: uppercase; font-weight: bold; font-size: 15px; letter-spacing: 0.5px; color:#000;">REKAPITULASI TRANSKRIP NILAI MURNI MATA PELAJARAN</h3>
    <h4 style="margin: 3px 0; font-size: 12px; font-weight: bold; color:#000;">SMA EL FITRA KOTA BANDUNG</h4>
    <small style="font-size: 10.5px; color: #333;">Mata Pelajaran: <strong>{{ $mapelInfo->nama_mapel }}</strong> | Kelas: <strong>{{ $kelasInfo->nama_kelas }}</strong> | Tahun Ajaran: <strong>{{ $tp }}</strong> | Semester: <strong>{{ $semester }}</strong></small>
    <hr style="border: 1px solid #000; margin-top: 10px; margin-bottom: 15px; opacity: 1;">
</div>

<div class="row mb-3 d-print-none">
    <div class="col">
        <h4 class="fw-bold"><i class="bi bi-calculator-fill me-2 text-primary"></i>Rincian Nilai Akhir Mata Pelajaran (Dinamis Per-Bab & Model Nilai)</h4>
        <p class="text-muted">Kelas: <strong>{{ $kelasInfo->nama_kelas }}</strong> | Mapel: <strong>{{ $mapelInfo->nama_mapel }}</strong></p>
    </div>
</div>

<div class="card border-0 shadow-sm p-3 mb-4 bg-light d-flex justify-content-between align-items-center flex-row d-print-none">
    <div>
        <span class="badge bg-dark px-3 py-1.5 rounded text-white" style="color:#fff !important;">Semester: {{ $semester }}</span>
        <span class="badge bg-secondary px-3 py-1.5 rounded text-white" style="color:#fff !important;">Tahun Pelajaran: {{ $tp }}</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}" class="btn btn-sm btn-success px-3 fw-bold">
            <i class="bi bi-file-earmark-excel me-1"></i> Ekstrak ke Excel
        </a>
        <button onclick="prosesCetakPdfLayarUtama()" class="btn btn-sm btn-danger px-3 fw-bold">
            <i class="bi bi-file-earmark-pdf me-1"></i> Ekstrak ke PDF
        </button>
        <a href="{{ route('wali.daftar_nilai') }}" class="btn btn-sm btn-outline-secondary px-3 fw-bold">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm p-0 bg-transparent">
    <div class="table-responsive-fix">
        <table class="table table-bordered table-hover align-middle mb-0 text-center table-matrix-print" style="font-size:12px;" border="1">
            <thead class="table-light align-middle fw-bold text-uppercase">
                <tr>
                    <th class="col-print-no" rowspan="2">No</th>
                    <th class="col-print-nama" rowspan="2" class="text-start ps-3">Nama Lengkap Siswa</th>
                    <th class="col-print-nisn" rowspan="2">NISN</th>
                    <th colspan="{{ max(count($listBabTerinput), 1) + 2 }}" class="bg-primary bg-opacity-10 text-primary border-bottom border-primary border-2"> Komponen Nilai Harian Murni (Bobot 70%)</th>
                    <th colspan="2" class="bg-success bg-opacity-10 text-success border-bottom border-success border-2">Ujian Akhir (Bobot 30%)</th>
                    <th class="col-print-akhir" rowspan="2">Nilai Akhir Rapot</th>
                    <th class="col-print-rangking" rowspan="2">Rangking</th>
                </tr>
                <tr class="small fw-bold text-muted" style="font-size:10.5px; background-color: #fcfcfc;">
                    @forelse($listBabTerinput as $bab)
                        <th class="px-1 py-1 align-top col-print-bab" title="{{ $bab['nama_bab'] }}">
                            <span class="d-block text-dark fw-bold">BAB {{ $bab['id_bab'] }}</span>
                            <span class="badge bg-primary-subtle text-primary text-uppercase px-1 d-inline-block text-truncate" style="max-width: 80px; font-size: 8.5px; line-height: 1;">{{ $bab['model_nilai'] }}</span>
                            @if(!empty($bab['tanggal']))
                                <small class="d-block text-muted mt-0.5" style="font-size: 9px; font-weight: normal;"><i class="bi bi-calendar3" style="font-size: 8px;"></i> {{ $bab['tanggal'] }}</small>
                            @endif
                        </th>
                    @empty
                        <th class="text-danger py-2 col-print-bab">Belum Ada Bab Terinput</th>
                    @endforelse
                    <th class="bg-primary bg-opacity-25 text-primary col-print-rata">Rata Harian</th>
                    <th class="bg-primary text-white col-print-porsi-h">Harian x 70%</th>
                    <th class="bg-success bg-opacity-25 text-success col-print-uas">Skor UAS</th>
                    <th class="bg-success text-white col-print-porsi-u">UAS x 30%</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dataRekap as $r)
                <tr>
                    <td class="col-print-no">{{ $loop->iteration }}</td>
                    <td class="text-start fw-bold text-uppercase text-dark ps-3 col-print-nama">
                        <span class="text-nama-wrap">{{ $r['nama'] }}</span>
                    </td>
                    <td class="col-print-nisn">{{ $s->nisn ?? $r['nisn'] }}</td>
                    
                    @forelse($listBabTerinput as $bab)
                        @php 
                            $uniqueKey = $bab['jadwal_id'] . '_' . str_replace(' ', '_', $bab['model_nilai']);
                            $currentScore = $r['nilai_bab_list'][$uniqueKey] ?? '-';
                        @endphp
                        <td class="fw-bold col-print-bab {{ $currentScore !== '-' ? 'text-dark' : 'text-muted' }}">{{ $currentScore }}</td>
                    @empty
                        <td class="text-muted text-center bg-light bg-opacity-50 col-print-bab">-</td>
                    @endforelse
                    
                    <td class="fw-bold bg-light text-dark col-print-rata">{{ $r['avg_harian'] }}</td>
                    <td class="fw-bold bg-primary bg-opacity-10 text-primary col-print-porsi-h">{{ $r['porsi_harian'] }}</td>
                    <td class="fw-bold text-dark col-print-uas">{{ $r['uas'] > 0 ? $r['uas'] : '-' }}</td>
                    <td class="fw-bold bg-success bg-opacity-10 text-success col-print-porsi-u">{{ $r['porsi_uas'] }}</td>
                    <td class="fw-bold bg-dark text-white fs-6 border-start border-2 border-dark col-print-akhir">{{ $r['nilai_akhir'] == 0 ? '-' : $r['nilai_akhir'] }}</td>
                    <td class="fw-bold bg-danger bg-opacity-10 text-danger col-print-rangking">{{ $r['rangking'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ max(count($listBabTerinput), 1) + 8 }}" class="text-center py-4 text-muted italic">Tidak ada data siswa ditemukan di kelas ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    // Membuka pengunci sinkronisasi KOP cetak PDF
    window.addEventListener('beforeprint', function () {
        document.getElementById('kop-cetak-pdf').classList.remove('d-none');
    });

    window.addEventListener('afterprint', function () {
        document.getElementById('kop-cetak-pdf').classList.add('d-none');
    });

    function prosesCetakPdfLayarUtama() {
        window.print();
    }
</script>
@endsection