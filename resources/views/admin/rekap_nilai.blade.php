@extends('layouts.main')
@section('content')
<div class="card border-0 shadow-sm p-4">
    <h5 class="fw-bold mb-4">Daftar Seluruh Nilai Siswa (Kelas: {{ Auth::user()->kelas->nama_kelas ?? '-' }})</h5>
    
    @foreach($siswa as $s)
    <div class="mb-5">
        <h6 class="bg-light p-2 fw-bold text-primary">{{ $s->nama }} ({{ $s->username }})</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered small">
                <thead>
                    <tr class="bg-dark text-white text-center">
                        <th>Mata Pelajaran</th>
                        @for($i=1; $i<=10; $i++) <th>P{{ $i }}</th> @endfor
                        <th class="bg-primary">UTS</th>
                        <th class="bg-primary">UAS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($s->rekap_nilai as $jadwal_id => $nilais)
                    <tr>
                        <td class="fw-bold">{{ $nilais[0]->jadwal->mapel->nama_mapel }}</td>
                        @for($i=1; $i<=10; $i++)
                        <td class="text-center">
                            {{ $nilais->where('pertemuan_ke', $i)->first()->skor ?? '-' }}
                        </td>
                        @endfor
                        <td class="text-center fw-bold">{{ $nilais->where('jenis', 'uts')->first()->skor ?? '-' }}</td>
                        <td class="text-center fw-bold">{{ $nilais->where('jenis', 'uas')->first()->skor ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
</div>
@endsection