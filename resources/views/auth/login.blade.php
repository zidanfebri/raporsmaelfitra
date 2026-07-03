<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E RAPOR - Login</title>
    
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo elfit.png') }}">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #ffffff;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            border: 1px solid #f0f0f0;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.03);
        }
        .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #eee;
            background-color: #fafafa;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #000;
            background-color: #fff;
        }
        .btn-login {
            background-color: #000;
            color: #fff;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-login:hover {
            background-color: #333;
            color: #fff;
        }
        .toggle-password {
            cursor: pointer;
            color: #999;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <h4 class="fw-bold">E RAPOR</h4>
        <p class="text-muted small">Sistem Penilaian Akademik Sekolah</p>
    </div>

    @if(session('error'))
        <div class="alert alert-danger border-0 small py-2">{{ session('error') }}</div>
    @endif

    <form action="{{ route('login.post') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Username</label>
            <input type="text" name="username" class="form-control" placeholder="Contoh: ssw001" required autofocus>
        </div>

        <div class="mb-4">
            <label class="form-label small fw-semibold text-secondary">Password</label>
            <div class="input-group">
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                <span class="input-group-text bg-light border-start-0" style="border: 1px solid #eee;">
                    <i class="bi bi-eye-slash toggle-password" id="eyeIcon"></i>
                </span>
            </div>
        </div>

        <button type="submit" class="btn btn-login w-100 mb-3">Masuk ke Dashboard</button>
        
        <div class="text-center">
            <span class="text-muted small">Lupa password? Hubungi Admin Sekolah.</span>
        </div>
    </form>
</div>

<script>
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    eyeIcon.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Toggle ikon
        this.classList.toggle('bi-eye');
        this.classList.toggle('bi-eye-slash');
    });
</script>

</body>
</html>