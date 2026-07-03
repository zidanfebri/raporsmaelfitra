<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIAKAD - Sistem Informasi Akademik</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo elfit.png') }}">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 260px;
            --primary-color: #4e73df;
            --dark-bg: #1a1c23;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fc;
            margin: 0;
            overflow-x: hidden;
        }

        /* Sidebar Style */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: var(--dark-bg);
            color: #fff;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1050;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 1.5rem;
            font-weight: 700;
            font-size: 1.25rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .nav-link {
            color: rgba(255,255,255,0.6);
            padding: 12px 20px;
            margin: 4px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            transition: 0.2s;
            text-decoration: none;
        }

        .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            color: #fff;
        }

        .nav-link.active {
            background-color: var(--primary-color);
            color: #fff;
            box-shadow: 0 4px 12px rgba(78, 115, 223, 0.2);
        }

        .nav-link i {
            margin-right: 12px;
            font-size: 1.1rem;
        }

        /* Main Content Style */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .navbar-custom {
            background: #fff;
            padding: 0.75rem 1.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.05);
            margin-bottom: 2rem;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
            text-decoration: none;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        /* Overlay Backdrop untuk HP */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1040;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }

        /* Responsive Layouting */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            #sidebarToggleMobile {
                cursor: pointer;
            }
        }

        /* CSS Print Logic */
        @page { size: auto; margin: 0mm; }
        @media print {
            .d-print-none { display: none !important; }
            body { background-color: #fff; }
            .main-content { margin-left: 0 !important; padding: 0; }
        }
    </style>
</head>
<body>

    <div class="sidebar-overlay d-print-none" id="sidebarOverlay"></div>

    <div class="sidebar d-print-none" id="sidebarLayout">
        <div class="sidebar-brand">
            <div><span class="text-primary">E</span> RAPOR</div>
            <button type="button" class="btn-close btn-close-white d-lg-none position-absolute end-0 me-3" id="sidebarCloseMobile" aria-label="Close"></button>
        </div>
        
        <div class="nav flex-column mt-4">
            <a href="{{ route('dashboard') }}" class="nav-link {{ Request::is('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            @if(Auth::user()->role == 'admin')
                <div class="px-4 mt-3 mb-2 small text-uppercase text-white" style="font-size: 0.7rem; letter-spacing: 1px; opacity: 0.5;">Master Data</div>
                
                <a href="{{ route('admin.guru') }}" class="nav-link {{ Request::is('admin/guru*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge"></i> Daftar Guru
                </a>
                
                <a href="{{ route('admin.siswa') }}" class="nav-link {{ Request::is('admin/siswa*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> Daftar Siswa
                </a>
                
                <a href="{{ route('admin.kelas') }}" class="nav-link {{ Request::is('admin/kelas*') ? 'active' : '' }}">
                    <i class="bi bi-door-open"></i> Daftar Kelas
                </a>
                
                <a href="{{ route('admin.mapel') }}" class="nav-link {{ Request::is('admin/mapel*') ? 'active' : '' }}">
                    <i class="bi bi-book"></i> Mata Pelajaran
                </a>
                
                <div class="px-4 mt-4 mb-2 small text-uppercase text-white" style="font-size: 0.7rem; letter-spacing: 1px; opacity: 0.5;">Pengaturan Plotting</div>

                <a href="{{ route('admin.plotting.walikelas') }}" class="nav-link {{ Request::is('admin/plotting-walikelas*') ? 'active' : '' }}">
                    <i class="bi bi-person-check"></i> Plotting Wali Kelas
                </a>

                <a href="{{ route('admin.plotting.guru') }}" class="nav-link {{ Request::is('admin/plotting-guru*') ? 'active' : '' }}">
                    <i class="bi bi-person-gear"></i> Plotting Guru
                </a>
                
                <a href="{{ route('admin.plotting') }}" class="nav-link {{ Request::is('admin/plotting*') ? 'active' : '' }}">
                    <i class="bi bi-diagram-3"></i> Plotting Siswa
                </a>
                
                <a href="{{ route('admin.jadwal') }}" class="nav-link {{ Request::is('admin/jadwal*') ? 'active' : '' }}">
                    <i class="bi bi-calendar-check"></i> Jadwal Pelajaran
                </a>

                <a href="{{ route('admin.jadwal.ujian') }}" class="nav-link {{ Request::is('admin/jadwal-ujian*') ? 'active' : '' }}">
                    <i class="bi bi-calendar2-range"></i> Jadwal UTS & UAS
                </a>
                
                <a href="{{ route('admin.laporan') }}" class="nav-link {{ Request::is('admin/laporan*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-bar-graph"></i> Cetak Rapot
                </a>
        
                <a href="{{ route('wali.leger_ujian') }}" class="nav-link {{ Request::is('admin/leger-ujian*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-spreadsheet-fill"></i> Leger Ujian Murni
                </a>
                
                <a href="{{ route('wali.rekap_transkrip') }}" class="nav-link {{ Request::is('admin/rekap-transkrip*') ? 'active' : '' }}">
                    <i class="bi bi-journal-text"></i> Matriks Rekap Bab Harian
                </a>
            @endif

            @if(Auth::user()->role == 'guru' || Auth::user()->role == 'walikelas')
                <div class="px-4 mt-3 mb-2 small text-uppercase text-muted" style="font-size: 0.7rem; letter-spacing: 1px;">Akademik</div>
                <a href="{{ route('dashboard') }}" class="nav-link {{ Request::is('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-pencil-square"></i> Input Nilai
                </a>
                <a href="{{ route('guru.kkm.index') }}" class="nav-link {{ Request::is('guru/kkm*') ? 'active' : '' }}">
                    <i class="bi bi-sliders"></i> Batas KKM Mapel
                </a>
                <a href="{{ route('wali.daftar_nilai') }}" class="nav-link {{ Request::is('wali/daftar-nilai*') ? 'active' : '' }}">
                    <i class="bi bi-journal-text"></i> Daftar Nilai Semua
                </a>
            @endif

            @if(Auth::user()->role == 'walikelas' || Auth::user()->role == 'admin')
                <a href="{{ route('wali.leger') }}" class="nav-link {{ Request::is('wali/leger*') ? 'active' : '' }}">
                    <i class="bi bi-grid-3x3-gap-fill"></i> Buku Leger Rangking
                </a>
            @endif

            @if(Auth::user()->role == 'walikelas')
                <a href="{{ route('admin.laporan') }}" class="nav-link {{ Request::is('admin/laporan*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-pdf"></i> Cetak Rapot
                </a>
            @endif

            <div class="mt-auto p-4 w-100">
                <a href="{{ route('logout') }}" class="nav-link text-danger border border-danger border-opacity-25 justify-content-center m-0">
                    <i class="bi bi-box-arrow-left"></i> Keluar
                </a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <nav class="navbar-custom d-print-none">
            <div class="fw-bold text-dark fs-5 d-flex align-items-center">
                <i class="bi bi-list d-inline-block d-lg-none me-2 shadow-sm p-2 rounded bg-light" id="sidebarToggleMobile"></i>
                <span>Sistem Informasi Akademik</span>
            </div>
            
            <div class="dropdown">
                <a class="user-profile dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="text-end d-none d-sm-block">
                        <div class="fw-bold" style="font-size: 0.9rem; line-height: 1;">{{ Auth::user()->nama }}</div>
                        <small class="text-muted" style="font-size: 0.75rem;">{{ ucfirst(Auth::user()->role) }}</small>
                    </div>
                    
                    @if(Auth::user()->foto)
                        <img src="{{ asset('storage/profil/'.Auth::user()->foto) }}" alt="profile" class="rounded-circle shadow-sm border" width="40" height="40" style="object-fit: cover;">
                    @else
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->nama) }}&background=4e73df&color=fff" alt="profile" class="rounded-circle shadow-sm" width="40" height="40">
                    @endif
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><h6 class="dropdown-header">Opsi Pengguna</h6></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="{{ route('user.profil') }}">
                            <i class="bi bi-person me-2"></i> Profil Saya
                        </a>
                    </td>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger d-flex align-items-center" href="{{ route('logout') }}">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </td>
                </ul>
            </div>
        </nav>

        <div class="container-fluid p-0">
            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show d-print-none" role="alert">
                    <i class="bi bi-check-circle me-2"></i> {!! session('success') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show d-print-none" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <footer class="text-center py-4 text-muted small d-print-none">
        &copy; 2026 E Rapor. SMA El Fitra
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebarLayout');
            const toggleBtn = document.getElementById('sidebarToggleMobile');
            const closeBtn = document.getElementById('sidebarCloseMobile');
            const overlay = document.getElementById('sidebarOverlay');

            function openSidebar() {
                sidebar.classList.add('active');
                overlay.classList.add('show');
                document.body.style.overflow = 'hidden'; // Kunci scroll layar utama saat menu terbuka
            }

            function closeSidebar() {
                sidebar.classList.remove('active');
                overlay.classList.remove('show');
                document.body.style.overflow = ''; // Aktifkan kembali scroll
            }

            if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
            if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
            if (overlay) overlay.addEventListener('click', closeSidebar);
        });
    </script>
</body>
</html>