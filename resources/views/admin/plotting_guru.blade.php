@extends('layouts.main')
@section('content')
<div class="card border-0 shadow-sm p-4">
    <h5 class="fw-bold mb-4"><i class="bi bi-person-check me-2 text-primary"></i>Plotting Keahlian Guru</h5>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Nama Guru</th>
                    <th>Mata Pelajaran yang Diampu</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($gurus as $g)
                <tr>
                    <td>
                        <div class="fw-bold">{{ $g->nama }}</div>
                        <small class="text-muted">{{ $g->username }}</small>
                    </td>
                    <td>
                        @forelse($g->mapels as $m)
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $m->nama_mapel }}</span>
                        @empty
                            <span class="text-danger small italic">Belum ada mapel</span>
                        @endforelse
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $g->id }}">
                            <i class="bi bi-pencil-square me-1"></i> Atur Mapel
                        </button>
                    </td>
                </tr>

                <div class="modal fade" id="modalEdit{{ $g->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form action="{{ route('admin.plotting.guru.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $g->id }}">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-dark text-white">
                                    <h5 class="modal-title">Plotting: {{ $g->nama }}</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="small text-muted mb-3">Pilih satu atau lebih mata pelajaran yang dikuasai guru ini:</p>
                                    <div class="row">
                                        @foreach($mapels as $mapel)
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="mapel_ids[]" value="{{ $mapel->id }}" id="m{{ $g->id }}{{ $mapel->id }}" 
                                                {{ $g->mapels->contains($mapel->id) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="m{{ $g->id }}{{ $mapel->id }}">
                                                    {{ $mapel->nama_mapel }}
                                                </label>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Simpan Plotting</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection