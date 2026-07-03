@extends('layouts.main')
@section('content')
<div class="card border-0 shadow-sm p-4">
    <h5 class="fw-bold mb-4"><i class="bi bi-diagram-3 me-2"></i>Plotting Siswa ke Kelas</h5>
    <div class="alert alert-warning small border-0"><i class="bi bi-info-circle me-2"></i>Pilih siswa dan tentukan kelas tujuannya.</div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="bg-light">
                <tr>
                    <th>Username</th>
                    <th>Nama Siswa</th>
                    <th>Pilih Kelas</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($siswa as $s)
                <tr>
                    <td>{{ $s->username }}</td>
                    <td>{{ $s->nama }}</td>
                    <form action="{{ route('admin.plotting') }}" method="POST">
                        @csrf
                        <input type="hidden" name="siswa_id" value="{{ $s->id }}">
                        <td>
                            <select name="kelas_id" class="form-select form-select-sm" style="max-width: 200px;">
                                <option value="">-- Pilih Kelas --</option>
                                @foreach($kelas as $k)
                                <option value="{{ $k->id }}" {{ $s->kelas_id == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><button type="submit" class="btn btn-sm btn-primary">Update Plot</button></td>
                    </form>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection