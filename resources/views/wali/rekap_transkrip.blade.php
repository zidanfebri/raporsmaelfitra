@extends('layouts.main')

@section('content')
<style>
    /* Styling khusus untuk menjaga simetri matriks transkrip */
    .table-matrix {
        border-collapse: collapse;
        width: 100%;
        font-size: 12px;
    }
    .table-matrix th {
        vertical-align: middle !important;
        text-align: center;
        font-weight: 600;
        padding: 10px 6px;
    }
    .table-matrix td {
        padding: 8px 6px;
        vertical-align: middle;
        text-align: center;
    }
    /* Pembatas internal untuk multi-bab dalam satu kolom mapel */
    .bab-split-container {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        width: 100%;
    }
    .bab-node-item {
        flex: 1;
        min-width: 70px;
        padding: 2px;
    }
    .bab-divider-line {
        width: 1px;
        background-color: #dee2e6;
        height: 28px;
        align-self: center;
    }
    .text-truncate-mapel {
        max-width: 150px;
        display: inline-block;
        text-truncate: ellipsis;
        white-space: nowrap;
        overflow: hidden;
    }
</style>

<div class="row mb-4">
    <div class="col">
        <h4 class="fw-bold"><i class="bi bi-journal-text me-2 text-warning"></i>Matriks Rekap Transkrip Bab & Model Harian</h4>
        <p class="text-muted">Visualisasi tabulasi detail nilai harian seluruh mata pelajaran berlandaskan filter model kompetensi dan bab.</p>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4 bg-light">
    <div class="card-body p-3">
        <form action="{{ route('wali.rekap_transkrip') }}" method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="small fw-bold text-muted mb-1">Filter Model Nilai</label>
                <select name="model_filter" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="tugas" {{ $model_filter == 'tugas' ? 'selected' : '' }}>Tugas</option>
                    <option value="postest harian" {{ $model_filter == 'postest harian' ? 'selected' : '' }}>Postest Harian</option>
                    <option value="postest bulanan" {{ $model_filter == 'postest bulanan' ? 'selected' : '' }}>Postest Bulanan</option>
                    <option value="ulangan harian" {{ $model_filter == 'ulangan harian' ? 'selected' : '' }}>Ulangan Harian</option>
                    <option value="praktikum" {{ $model_filter == 'praktikum' ? 'selected' : '' }}>Praktikum</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small fw-bold text-muted mb-1">Semester</label>
                <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="1" {{ request('semester') == '1' ? 'selected' : '' }}>Semester 1</option>
                    <option value="2" {{ request('semester') == '2' ? 'selected' : '' }}>Semester 2</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="small fw-bold text-muted mb-1">Tahun Pelajaran</label>
                <select name="tahun_pelajaran" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach(['2025/2026', '2026/2027', '2027/2028', '2028/2029', '2029/2030'] as $tp_opt)
                        <option value="{{ $tp_opt }}" {{ request('tahun_pelajaran','2025/2026') == $tp_opt ? 'selected' : '' }}>{{ $tp_opt }}</option>
                    @endforeach
                </select>
            </div>
            @if(Auth::user()->role == 'admin')
            <div class="col-md-3">
                <label class="small fw-bold text-muted mb-1">Pilih Unit Kelas</label>
                <select name="kelas_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Pilih Kelas --</option>
                    @foreach($kelas as $k)
                        <option value="{{ $k->id }}" {{ $kelas_id == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-2">
                <a href="{{ route('wali.rekap_transkrip') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

@if($kelas_id && count($siswaLeger) > 0)
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white pt-3 border-0">
        <h6 class="fw-bold m-0 text-dark"><i class="bi bi-grid me-1"></i> Matriks Transkrip Kompetensi ({{ strtoupper($model_filter) }}) Kelas: {{ $infoKelas->nama_kelas }}</h6>
    </div>
    <div class="card-body p-0 mt-2">
        <div class="table-responsive">
            <table class="table table-bordered table-matrix align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th width="3%">No</th>
                        <th width="15%" class="text-start ps-3">Nama Lengkap Siswa</th>
                        <th width="8%">NISN</th>
                        {{-- Render Kolom Tunggal Mapel Tetap Berisi Sub-Header Bab Internal --}}
                        @foreach($mapelHeaders as $mId => $data)
                            <th>
                                <span class="text-uppercase text-truncate-mapel d-block fw-bold mb-1" title="{{ $data['info']->nama_mapel }}">
                                    {{ $data['info']->nama_mapel }}
                                </span>
                                <div class="bab-split-container border-top pt-1 mt-1">
                                    @forelse($data['bab_list'] as $idx => $bab)
                                        @if($idx > 0) <div class="bab-divider-line" style="height:15px;"></div> @endif
                                        <div class="bab-node-item" style="font-size: 9px; font-weight: normal;" title="{{ $bab->nama_bab }}">
                                            Bab {{ $bab->id_bab }}<br><span class="text-info" style="font-size:8px;">{{ Str::limit($bab->nama_bab, 9) }}</span>
                                        </div>
                                    @empty
                                        <div class="text-muted opacity-50 font-italic" style="font-size: 10px; font-weight: normal;">Kosong</div>
                                    @endforelse
                                </div>
                            </th>
                        @endforeach
                        <th class="bg-primary text-white" width="6%">Jumlah</th>
                        <th class="bg-info text-dark" width="6%">Rata-Rata</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($siswaLeger as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="text-start fw-bold text-uppercase text-dark ps-3">{{ $row['nama'] }}</td>
                        <td>{{ $row['nisn'] }}</td>
                        
                        {{-- Render Data Nilai Siswa Sesuai Pembagian Blok Kolom Mapel --}}
                        @foreach($mapelHeaders as $mId => $data)
                            <td>
                                <div class="bab-split-container">
                                    @forelse($data['bab_list'] as $idx => $bab)
                                        @if($idx > 0) <div class="bab-divider-line"></div> @endif
                                        @php $scoreVal = $row['matrix'][$mId . '_' . $bab->id_bab]; @endphp
                                        <div class="bab-node-item fw-bold {{ $scoreVal === null ? 'text-muted opacity-25' : 'text-dark' }}">
                                            {{ $scoreVal !== null ? $scoreVal : '-' }}
                                        </div>
                                    @empty
                                        <div class="text-muted opacity-25">-</div>
                                    @endforelse
                                </div>
                            </td>
                        @endforeach
                        <td class="fw-bold bg-primary bg-opacity-10 text-primary">{{ $row['total'] }}</td>
                        <td class="fw-bold bg-info bg-opacity-10 text-info">{{ $row['rata_rata'] }}</td>
                    </tr>
                    @endforeach

                    {{-- STATISTIK BARIS BAWAH: JUMLAH NILAI MAPEL --}}
                    <tr class="table-light border-top border-dark fw-bold">
                        <td colspan="3" class="text-end bg-light text-uppercase" style="font-size:11px;">Jumlah Nilai</td>
                        @foreach($mapelHeaders as $mId => $data)
                            <td>
                                <div class="bab-split-container">
                                    @forelse($data['bab_list'] as $idx => $bab)
                                        @if($idx > 0) <div class="bab-divider-line" style="height:15px;"></div> @endif
                                        <div class="bab-node-item text-dark">
                                            {{ $statMatrix[$mId . '_' . $bab->id_bab]['jumlah'] ?? 0 }}
                                        </div>
                                    @empty
                                        <div class="text-muted opacity-50">0</div>
                                    @endforelse
                                </div>
                            </td>
                        @endforeach
                        <td colspan="2" class="bg-light"></td>
                    </tr>

                    {{-- STATISTIK BARIS BAWAH: RATA-RATA MAPEL --}}
                    <tr class="table-light fw-bold">
                        <td colspan="3" class="text-end bg-light text-uppercase" style="font-size:11px;">Rata-Rata Mapel</td>
                        @foreach($mapelHeaders as $mId => $data)
                            <td>
                                <div class="bab-split-container">
                                    @forelse($data['bab_list'] as $idx => $bab)
                                        @if($idx > 0) <div class="bab-divider-line" style="height:15px;"></div> @endif
                                        <div class="bab-node-item text-primary">
                                            {{ $statMatrix[$mId . '_' . $bab->id_bab]['rata_rata'] ?? 0 }}
                                        </div>
                                    @empty
                                        <div class="text-muted opacity-50">0</div>
                                    @endforelse
                                </div>
                            </td>
                        @endforeach
                        <td colspan="2" class="bg-light"></td>
                    </tr>

                    {{-- STATISTIK BARIS BAWAH: NILAI TERTINGGI MAPEL --}}
                    <tr class="table-light fw-bold">
                        <td colspan="3" class="text-end bg-light text-uppercase" style="font-size:11px;">Nilai Tertinggi</td>
                        @foreach($mapelHeaders as $mId => $data)
                            <td>
                                <div class="bab-split-container">
                                    @forelse($data['bab_list'] as $idx => $bab)
                                        @if($idx > 0) <div class="bab-divider-line" style="height:15px;"></div> @endif
                                        <div class="bab-node-item text-success">
                                            {{ $statMatrix[$mId . '_' . $bab->id_bab]['terbesar'] ?? 0 }}
                                        </div>
                                    @empty
                                        <div class="text-muted opacity-50">0</div>
                                    @endforelse
                                </div>
                            </td>
                        @endforeach
                        <td colspan="2" class="bg-light"></td>
                    </tr>

                    {{-- STATISTIK BARIS BAWAH: NILAI TERENDAH MAPEL --}}
                    <tr class="table-light fw-bold">
                        <td colspan="3" class="text-end bg-light text-uppercase" style="font-size:11px;">Nilai Terendah</td>
                        @foreach($mapelHeaders as $mId => $data)
                            <td>
                                <div class="bab-split-container">
                                    @forelse($data['bab_list'] as $idx => $bab)
                                        @if($idx > 0) <div class="bab-divider-line" style="height:15px;"></div> @endif
                                        <div class="bab-node-item text-danger">
                                            {{ $statMatrix[$mId . '_' . $bab->id_bab]['terkecil'] ?? 0 }}
                                        </div>
                                    @empty
                                        <div class="text-muted opacity-50">0</div>
                                    @endforelse
                                </div>
                            </td>
                        @endforeach
                        <td colspan="2" class="bg-light"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@elseif($kelas_id)
    <div class="alert alert-warning text-center">Belum terbentuk rincian instalan bab harian pada model kriteria filter ini.</div>
@else
    <div class="alert alert-info text-center">Silakan tentukan filter kelas dan parameter tahun ajaran untuk memetakan transkrip horizontal seluruh bab.</div>
@endif
@endsection