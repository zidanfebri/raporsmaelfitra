@extends('layouts.main')

@section('content')
<div class="row mb-4">
    <div class="col">
        <h4 class="fw-bold"><i class="bi bi-journal-text me-2 text-primary"></i>Riwayat Nilai Pelajaran</h4>
        <p class="text-muted">Pilih mata pelajaran untuk melihat detail kumpulan rekap nilai siswa.</p>
    </div>
</div>

{{-- PANEL FILTER INTERAKTIF BARU DENGAN DROPDOWN TAHUN AJARAN DINAMIS --}}
<div class="card border-0 shadow-sm mb-4 bg-light">
    <div class="card-body p-3">
        <form action="{{ route('wali.daftar_nilai') }}" method="GET" id="form-filter-riwayat" class="row g-2">
            <div class="col-6 col-md-3">
                <label class="small fw-bold text-muted">Semester</label>
                <select name="semester" id="filter_semester" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="1" {{ request('semester') == '1' ? 'selected' : '' }}>Semester 1 (Ganjil)</option>
                    <option value="2" {{ request('semester') == '2' ? 'selected' : '' }}>Semester 2 (Genap)</option>
                </select>
            </div>
            <div class="col-6 col-md-4">
                <label class="small fw-bold text-muted">Tahun Pelajaran</label>
                <select name="tahun_pelajaran" id="filter_tp" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Tahun Pelajaran</option>
                    @foreach(['2025/2026', '2026/2027', '2027/2028', '2028/2029', '2029/2030'] as $tp_opt)
                        <option value="{{ $tp_opt }}" {{ request('tahun_pelajaran') == $tp_opt ? 'selected' : '' }}>{{ $tp_opt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex align-items-end mt-2 mt-md-0">
                <a href="{{ route('wali.daftar_nilai') }}" class="btn btn-sm btn-outline-secondary w-100">Reset Filter</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white pt-3 border-bottom">
        <ul class="nav nav-tabs card-header-tabs border-bottom-0" id="riwayatTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold px-3 px-md-4" id="harian-tab" data-bs-toggle="tab" data-bs-target="#harian" type="button" role="tab">
                    <i class="bi bi-calendar-week me-1"></i> Riwayat Nilai Harian
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold px-3 px-md-4" id="ujian-tab" data-bs-toggle="tab" data-bs-target="#ujian" type="button" role="tab">
                    <i class="bi bi-file-earmark-text me-1"></i> Riwayat UTS & UAS
                </button>
            </li>
        </ul>
    </div>
    
    <div class="card-body mt-3">
        <div class="tab-content" id="riwayatTabContent">
            
            {{-- TAB HARIAN --}}
            <div class="tab-pane fade show active" id="harian" role="tabpanel">
                <div class="row">
                    @forelse($jadwalMapel as $mapelId => $items)
                        @php 
                            $mapel = $items->first()->mapel; 
                            $jumlahKelas = $items->unique('kelas_id')->count();
                        @endphp
                        <div class="col-sm-6 col-md-4 mb-4">
                            <div class="card border-0 shadow-sm h-100 border-top border-primary border-3">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div>
                                        <h6 class="fw-bold mb-1" style="font-size: 0.95rem;">{{ $mapel->nama_mapel }}</h6>
                                        <small class="text-muted d-block mb-3">{{ $jumlahKelas }} Kelas Tersedia</small>
                                    </div>
                                    <div class="d-grid">
                                        @if($jumlahKelas > 1)
                                            <button class="btn btn-outline-primary btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPilihKelasHarian{{ $mapelId }}">
                                                <i class="bi bi-list-ul me-1"></i> Pilih Kelas
                                            </button>
                                        @else
                                            @php $j = $items->first(); @endphp
                                            <div class="d-grid gap-1">
                                                <button class="btn btn-outline-primary btn-sm fw-bold shadow-sm mb-1" onclick="showRiwayat({{ $j->mapel_id }}, {{ $j->kelas_id }}, 'harian')">
                                                    <i class="bi bi-eye me-1"></i> Riwayat Nilai Harian
                                                </button>
                                                <a href="{{ route('nilai.rekap_akhir_mapel', [$j->mapel_id, $j->kelas_id]) }}?semester={{ request('semester','1') }}&tahun_pelajaran={{ request('tahun_pelajaran','2025/2026') }}" class="btn btn-primary btn-sm fw-bold shadow-sm">
                                                    <i class="bi bi-calculator me-1"></i> Rincian Nilai Akhir
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Modal Pilih Kelas Harian --}}
                        @if($jumlahKelas > 1)
                        <div class="modal fade" id="modalPilihKelasHarian{{ $mapelId }}" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered modal-sm">
                                <div class="modal-content border-0 shadow">
                                    <div class="modal-header border-0 pb-0">
                                        <h6 class="modal-title fw-bold">Pilih Kelas</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-3">
                                        @foreach($items->unique('kelas_id') as $item)
                                            <div class="d-grid gap-1 mb-2 border-bottom pb-2">
                                                <span class="fw-bold small text-dark mb-1"><i class="bi bi-door-open me-1 text-muted"></i>{{ $item->kelas->nama_kelas }}</span>
                                                <button onclick="showRiwayat({{ $item->mapel_id }}, {{ $item->kelas_id }}, 'harian')" class="btn btn-light btn-sm text-start fw-bold py-1.5" style="font-size: 0.85rem;">
                                                    <i class="bi bi-eye text-primary me-1"></i> Riwayat Nilai Harian
                                                </button>
                                                <a href="{{ route('nilai.rekap_akhir_mapel', [$item->mapel_id, $item->kelas_id]) }}?semester={{ request('semester','1') }}&tahun_pelajaran={{ request('tahun_pelajaran','2025/2026') }}" class="btn btn-primary btn-sm text-start fw-bold py-1.5" style="font-size: 0.85rem;">
                                                    <i class="bi bi-calculator me-1"></i> Rincian Nilai Akhir
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    @empty
                        <div class="col-12 text-center py-5"><p class="text-muted">Tidak ada data riwayat nilai harian.</p></div>
                    @endforelse
                </div>
            </div>

            {{-- TAB UTS & UAS --}}
            <div class="tab-pane fade" id="ujian" role="tabpanel">
                <div class="row">
                    @forelse($jadwalMapel as $mapelId => $items)
                        @php 
                            $mapel = $items->first()->mapel; 
                            $jumlahKelas = $items->unique('kelas_id')->count();
                        @endphp
                        <div class="col-sm-6 col-md-4 mb-4">
                            <div class="card border-0 shadow-sm h-100 border-top border-success border-3">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div>
                                        <h6 class="fw-bold mb-1" style="font-size: 0.95rem;">{{ $mapel->nama_mapel }}</h6>
                                        <small class="text-muted d-block mb-3">{{ $jumlahKelas }} Kelas Tersedia</small>
                                    </div>
                                    <div class="d-grid">
                                        @if($jumlahKelas > 1)
                                            <button class="btn btn-outline-success btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPilihKelasUjian{{ $mapelId }}">
                                                <i class="bi bi-list-ul me-1"></i> Pilih Kelas
                                            </button>
                                        @else
                                            @php $j = $items->first(); @endphp
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-outline-primary btn-sm w-50 fw-bold" onclick="showRiwayat({{ $j->mapel_id }}, {{ $j->kelas_id }}, 'uts')">UTS</button>
                                                <button class="btn btn-outline-success btn-sm w-50 fw-bold" onclick="showRiwayat({{ $j->mapel_id }}, {{ $j->kelas_id }}, 'uas')">UAS</button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Modal Pilih Kelas Ujian --}}
                        @if($jumlahKelas > 1)
                        <div class="modal fade" id="modalPilihKelasUjian{{ $mapelId }}" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered modal-sm">
                                <div class="modal-content border-0 shadow">
                                    <div class="modal-header border-0 pb-0">
                                        <h6 class="modal-title fw-bold">Pilih Kelas</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-3">
                                        @foreach($items->unique('kelas_id') as $item)
                                            <div class="mb-3 p-2 bg-light rounded shadow-sm">
                                                <small class="fw-bold d-block mb-2 text-dark">{{ $item->kelas->nama_kelas }}</small>
                                                <div class="d-flex gap-2">
                                                    <button onclick="showRiwayat({{ $item->mapel_id }}, {{ $item->kelas_id }}, 'uts')" class="btn btn-primary btn-sm w-50">UTS</button>
                                                    <button onclick="showRiwayat({{ $item->mapel_id }}, {{ $item->kelas_id }}, 'uas')" class="btn btn-success btn-sm w-50">UAS</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    @empty
                        <div class="col-12 text-center py-5"><p class="text-muted">Tidak ada data riwayat ujian.</p></div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>

{{-- MODAL RIWAYAT NILAI SEBUTAN BARU (RESPONSIVE FIX VIEW) --}}
<div class="modal fade" id="modalRiwayat" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-fullscreen-lg-down">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white py-2">
                <h5 class="modal-title" style="font-size: 15px;">Riwayat <span id="text-jenis"></span>: <span id="text-mapel"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="bersihkanSisaBackdrop()"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive-mobile">
                    <table class="table table-bordered align-middle mb-0">
                        <thead id="head-riwayat" class="table-light text-center small fw-bold"></thead>
                        <tbody id="body-riwayat"></tbody>
                        <tfoot id="foot-riwayat" class="table-secondary fw-bold text-center small"></tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function getWarnaKelompok(nilai) {
    if (nilai === '-') return '';
    let num = parseInt(nilai);
    if (num >= 90 && num <= 100) return 'bg-primary text-white';   
    if (num >= 80 && num <= 89)  return 'bg-success text-white';   
    if (num >= 70 && num <= 79)  return 'bg-warning text-dark';    
    if (num >= 50 && num <= 69)  return 'bg-secondary text-white'; 
    if (num >= 0  && num <= 49)  return 'bg-danger text-white';    
    return '';
}

function bersihkanSisaBackdrop() {
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.style.overflow = 'auto';
    document.body.style.paddingRight = '0px';
    document.body.classList.remove('modal-open');
}

function showRiwayat(mapelId, kelasId, jenis) {
    // Sembunyikan modal pilih kelas yang sedang terbuka jika ada
    const modalAktif = document.querySelector('.modal.show');
    if (modalAktif) {
        let instance = bootstrap.Modal.getInstance(modalAktif);
        if (instance) instance.hide();
    }
    
    bersihkanSisaBackdrop();

    const head = document.getElementById('head-riwayat');
    const body = document.getElementById('body-riwayat');
    const foot = document.getElementById('foot-riwayat');
    
    head.innerHTML = `<tr><td class="text-center p-4">Memuat data...</td></tr>`;
    body.innerHTML = '';
    foot.innerHTML = '';

    const sem = document.getElementById('filter_semester').value;
    const tp = document.getElementById('filter_tp').value;

    // AMBIL DATA NILAI ASINKRONUS MURNI BERDASARKAN FILTER SEKARANG
    fetch(`/wali/get-riwayat-nilai/${mapelId}/${kelasId}/${jenis}?semester=${sem}&tahun_pelajaran=${tp}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('text-mapel').innerText = `${data.mapel} (${data.kelas})`;
            document.getElementById('text-jenis').innerText = data.jenis.toUpperCase();

            let isHarian = (data.jenis.toLowerCase() === 'harian');
            let headHtml = `<tr><th class="bg-white align-middle text-center sticky-col-nama" style="min-width:180px; border-right:2px solid #dee2e6;">Nama Siswa</th>`;

            if (data.header_data && data.header_data.length > 0) {
                data.header_data.forEach(p => {
                    if (!isHarian) {
                        headHtml += `<th class="text-center bg-light px-3" style="min-width:110px; font-size:0.8rem;">Tanggal: ${p.tanggal}</th>`;
                    } else {
                        // SINKRONISASI HEADER BERDASARKAN ID JADWAL/TANGGAL AGAR JADWAL TERPISAH
                        headHtml += `
                            <th class="text-center bg-light px-2" style="min-width:160px">
                                <span class="text-muted d-block small mb-1" style="font-size: 0.7rem;"><i class="bi bi-calendar3"></i> ${p.tanggal}</span>
                                <span class="text-dark d-block text-uppercase fw-bold" style="font-size: 0.75rem;">BAB ${p.nomor_bab}</span>
                                <span class="badge bg-primary-subtle text-primary mt-1" style="font-size: 0.6rem; font-weight:normal;">${p.model}</span>
                            </th>`;
                    }
                });
            } else {
                headHtml += `<th class="text-muted p-3">Belum ada data untuk kombinasi filter ini</th>`;
            }

            if (isHarian) {
                headHtml += `<th class="text-center bg-dark text-white px-2 align-middle" style="min-width:100px; font-size:0.8rem;">Rata-Rata</th>`;
            }

            headHtml += '</tr>';
            head.innerHTML = headHtml;

            let totalKolomMateri = data.header_data ? data.header_data.length : 0;
            let nilaiPerKolom = Array.from({ length: totalKolomMateri }, () => []);

            let bodyHtml = '';
            if (data.siswa && data.siswa.length > 0) {
                data.siswa.forEach(s => {
                    let sumSiswa = 0;
                    let countSiswa = 0;
                    let barisNilaiHtml = '';

                    s.nilai_array.forEach((val, idx) => {
                        let kelasWarna = '';
                        if (!isHarian) {
                            kelasWarna = getWarnaKelompok(val);
                        } else {
                            kelasWarna = (val != '-' ? 'text-primary' : 'text-muted');
                        }

                        barisNilaiHtml += `<td class="text-center fw-bold ${kelasWarna}" style="font-size: 0.95rem;">${val}</td>`;
                        
                        if (val !== '-') {
                            let num = parseInt(val);
                            if(nilaiPerKolom[idx]) nilaiPerKolom[idx].push(num);
                            sumSiswa += num;
                            countSiswa++;
                        }
                    });

                    bodyHtml += `<tr><td class="fw-bold small bg-white sticky-col-nama" style="border-right:2px solid #dee2e6;">${s.nama}</td>`;
                    bodyHtml += barisNilaiHtml;

                    if (isHarian) {
                        let rataSiswa = countSiswa > 0 ? Math.round(sumSiswa / countSiswa) : '-';
                        bodyHtml += `<td class="text-center fw-bold text-danger bg-light" style="font-size: 0.95rem;">${rataSiswa}</td>`;
                    }

                    bodyHtml += '</tr>';
                });
            }
            body.innerHTML = bodyHtml;

            // RENDER REKAP FOOTER STATISTIK (JUMLAH, MAX, MIN)
            let footerHtml = '';
            if (totalKolomMateri > 0) {
                let rowRata  = `<tr><td class="text-start ps-2 fw-bold bg-light sticky-col-nama" style="border-right:2px solid #dee2e6;">Rata-Rata</td>`;
                let rowMax   = `<tr><td class="text-start ps-2 fw-bold bg-light sticky-col-nama" style="border-right:2px solid #dee2e6;">Terbesar</td>`;
                let rowMin   = `<tr><td class="text-start ps-2 fw-bold bg-light sticky-col-nama" style="border-right:2px solid #dee2e6;">Terkecil</td>`;

                for (let i = 0; i < totalKolomMateri; i++) {
                    let arr = nilaiPerKolom[i];
                    if (arr && arr.length > 0) {
                        let sum = arr.reduce((a, b) => a + b, 0);
                        let avg = Math.round(sum / arr.length);
                        let max = Math.max(...arr);
                        let min = Math.min(...arr);

                        rowRata  += `<td class="text-center text-dark">${avg}</td>`;
                        rowMax   += `<td class="text-center text-success">${max}</td>`;
                        rowMin   += `<td class="text-center text-danger">${min}</td>`;
                    } else {
                        rowRata  += `<td class="text-center text-muted">-</td>`;
                        rowMax   += `<td class="text-center text-muted">-</td>`;
                        rowMin   += `<td class="text-center text-muted">-</td>`;
                    }
                }

                if (isHarian) {
                    rowRata += `<td class="bg-light"></td>`;
                    rowMax  += `<td class="bg-light"></td>`;
                    rowMin  += `<td class="bg-light"></td>`;
                }

                rowRata  += `</tr>`;
                rowMax   += `</tr>`;
                rowMin   += `</tr>`;

                footerHtml = rowRata + rowMax + rowMin;
            }
            foot.innerHTML = footerHtml;

            const modalTarget = document.getElementById('modalRiwayat');
            const instanceModalRiwayat = bootstrap.Modal.getOrCreateInstance(modalTarget);
            instanceModalRiwayat.show();
        })
        .catch(err => {
            console.error(err);
            head.innerHTML = `<tr><td class="text-center p-4 text-danger">Gagal memuat data riwayat nilai.</td></tr>`;
        });
}

document.addEventListener('DOMContentLoaded', function() {
    const modalElement = document.getElementById('modalRiwayat');
    if(modalElement) {
        modalElement.addEventListener('hidden.bs.modal', bersihkanSisaBackdrop);
    }
});
</script>

<style>
    .bg-primary-subtle { background-color: #e7f0ff; color: #0d6efd; }
    .nav-tabs .nav-link { color: #6c757d !important; border: 1px solid transparent; border-radius: 8px 8px 0 0; transition: all 0.3s ease; }
    .nav-tabs .nav-link.active { color: #4e73df !important; background-color: #fff !important; border-top: 3px solid #4e73df !important; border-left: 1px solid #dee2e6 !important; border-right: 1px solid #dee2e6 !important; border-bottom: 1px solid #fff !important; }
    
    .bg-primary { background-color: #0d6efd !important; }
    .bg-success { background-color: #198754 !important; }
    .bg-warning { background-color: #ffc107 !important; color: #000 !important; }
    .bg-secondary { background-color: #6c757d !important; }
    .bg-danger { background-color: #dc3545 !important; }

    /* CORE CSS STICKY COLUMN UNTUK RIWAYAT POPUP DI HP */
    .table-responsive-mobile {
        position: relative;
        overflow-x: auto;
    }
    @media (max-width: 992px) {
        .sticky-col-nama {
            position: -webkit-sticky;
            position: sticky;
            left: 0;
            background-color: #fff !important;
            z-index: 2;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        th.sticky-col-nama {
            z-index: 3;
            background-color: #f8f9fc !important;
        }
    }
</style>
@endsection