@extends('layouts.main')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold mb-4"><i class="bi bi-person-circle me-2 text-primary"></i>Profil Pengguna</h5>

            <form action="{{ route('user.profil.update') }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                
                <div class="text-center mb-4">
                    <div class="position-relative d-inline-block">
                        @if($user->foto)
                            {{-- Jalur asset('storage/profil/...') adalah jalur publik --}}
                            <img src="{{ asset('storage/profil/'.$user->foto) }}" class="rounded-circle shadow-sm border" width="120" height="120" style="object-fit: cover;">
                        @else
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->nama) }}&background=4e73df&color=fff" class="rounded-circle shadow-sm" width="120" height="120">
                        @endif
                    </div>
                    <div class="mt-3 text-start">
                        <label class="form-label small fw-bold text-muted">Ganti Foto Profil</label>
                        <input type="file" name="foto" class="form-control form-control-sm @error('foto') is-invalid @enderror" accept=".jpg,.jpeg,.png">
                        @error('foto')
                            <div class="invalid-feedback small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Username</label>
                    <input type="text" class="form-control bg-light" value="{{ $user->username }}" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Lengkap</label>
                    <input type="text" class="form-control bg-light" value="{{ $user->nama }}" disabled>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-primary">Ganti Password Baru</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Kosongkan jika tidak ingin mengganti">
                    @error('password')
                        <div class="invalid-feedback small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary fw-bold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection