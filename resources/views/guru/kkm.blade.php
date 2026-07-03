@extends('layouts.main')

@section('content')
<div class="row mb-4">
    <div class="col">
        <h4 class="fw-bold"><i class="bi bi-sliders me-2 text-warning"></i>Pengaturan Batas Nilai KKM Mata Pelajaran</h4>
        <p class="text-muted">Tentukan standar Kriteria Ketercapaian Tujuan Pembelajaran (KKM) murni default per mata pelajaran yang Anda ampu.</p>
    </div>
</div>

<div class="row">
    <div class="col-xl-6 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white pt-3 border-0">
                <h6 class="fw-bold text-dark m-0"><i class="bi bi-shield-check me-1 text-success"></i> Konfigurasi KKM Default Guru Pengampu</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info border-0 small mb-4">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    Nilai KKM yang Anda tetapkan di bawah ini akan otomatis berlaku sebagai <strong>standar tuntas acuan baku</strong> di seluruh kelas tempat Anda mengajar, serta menjadi poros kalkulasi otomatis untuk kolom capaian deskripsi kompetensi rapot siswa.
                </div>

                <form action="{{ route('guru.kkm.store') }}" method="POST">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle small text-center mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th width="8%">No</th>
                                    <th class="text-start ps-3">Nama Mata Pelajaran Terplot</th>
                                    <th width="35%">Standar KKM Default</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mapelDiampu as $mp)
                                    @php
                                        $pivotData = $mp->gurus->first();
                                        $currentKkm = $pivotData ? ($pivotData->pivot->kkm ?? 75) : 75;
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="text-start fw-bold text-secondary ps-3">
                                            {{ $mp->nama_mapel }}
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm justify-content-center px-2">
                                                <input type="number" 
                                                       name="kkm[{{ $mp->id }}]" 
                                                       class="form-control text-center text-dark border-secondary bg-white input-kkm-validator" 
                                                       value="{{ $currentKkm }}" 
                                                       min="0" 
                                                       max="100" 
                                                       placeholder="75" 
                                                       required 
                                                       style="font-weight: 800; font-size: 13px; max-width: 90px; border-width: 1.5px;">
                                                <span class="input-group-text border-secondary bg-light text-dark font-weight-bold fw-bold" style="font-size:11px; border-width: 1.5px; border-left: 0;">Skor KKM</span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted italic small">Anda belum di-plotting untuk mengampu mata pelajaran apa pun. Silakan hubungi tim Admin SIAKAD.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(count($mapelDiampu) > 0)
                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-sm btn-primary px-4 shadow-sm fw-bold">
                                <i class="bi bi-check-circle me-1"></i> Simpan Standar KKM
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll('.input-kkm-validator').forEach(input => {
            input.addEventListener('input', function() {
                let val = parseFloat(this.value);
                if (val > 100) this.value = 100;
                if (val < 0) this.value = 0;
            });
        });
    });
</script>
@endsection