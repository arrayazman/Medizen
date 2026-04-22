<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Medizen</title>

    <!-- Google Fonts -->
    <link href="{{ asset('css/fonts-inter.css') }}" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Feather Icons -->
    <script src="{{ asset('js/feather.min.js') }}"></script>

    <!-- Chart.js -->
    <script src="{{ asset('js/chart.umd.min.js') }}"></script>

    <!-- Custom CSS w/ Cache Buster -->
    <link href="{{ asset('css/ris.css') }}?v={{ time() }}" rel="stylesheet">

    <!-- Critical Privacy Styles (Internal to prevent cache issues) -->
    <style>
        body.privacy-mode .privacy-mask {
            filter: blur(10px) !important;
            display: inline-block !important;
            background-color: rgba(0, 0, 0, 0.08) !important;
            user-select: none !important;
            pointer-events: none !important;
            color: transparent !important;
            text-shadow: 0 0 15px rgba(0, 0, 0, 0.6) !important;
            transition: all 0.3s ease;
        }

        body.privacy-mode .privacy-mask.peekable {
            pointer-events: auto !important;
            cursor: pointer !important;
        }

        body.privacy-mode .privacy-mask.peekable:hover {
            filter: none !important;
            color: inherit !important;
            text-shadow: none !important;
            background-color: transparent !important;
        }
    </style>

    <!-- Select2 -->
    <link href="{{ asset('css/select2.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}" rel="stylesheet" />

    @stack('styles')

    <!-- Runtime Kernel Enforcement -->
    <script src="{{ asset('js/engine.bootstrap.js') }}"></script>

    <style>
        /* Hidden by default, revealed only by Kernel */
        body { 
            opacity: 0; 
            transition: opacity 0.4s ease; 
            background: #f4f6f9; 
        }
        
        body.kernel-verified { 
            opacity: 1; 
        }
        
        #kernel-error-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #fff; color: #ff4d4d; z-index: 999999;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Courier New', monospace; text-align: center; padding: 20px;
        }
    </style>

</head>

<body>
    <script>
        // Apply sidebar state immediately to prevent "flash of expansion"
        if (localStorage.getItem('sidebar-collapsed') === 'true' && window.innerWidth > 992) {
            document.body.classList.add('sidebar-collapsed');
        }
        // Apply privacy mode immediately
        if (localStorage.getItem('privacy-mode') === 'true') {
            document.body.classList.add('privacy-mode');
        }
    </script>

    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <i data-feather="activity"></i>
            </div>
            <div>
                <div class="brand-text">{{ config('app.name') }}</div>
                <div class="brand-sub">{{ config('app.description') }}</div>
            </div>
        </div>

        <!-- Sidebar Search -->
        <div class="px-3 mb-1 mt-0 sidebar-search-wrapper">
            <div class="position-relative d-flex align-items-center">
                <i data-feather="search" class="search-icon"></i>
                <input type="text" id="sidebarSearch" class="sidebar-search-input" placeholder="Cari modul...">
            </div>
        </div>

        <style>
            .sidebar-search-wrapper {
                margin-top: 0 !important;
                margin-bottom: 0.5rem !important;
            }

            .sidebar-menu {
                padding-top: 0 !important;
            }

            .sidebar-search-input {
                width: 100%;
                background: rgba(255, 255, 255, 0.03);
                border: 1px solid rgba(255, 255, 255, 0.05);
                border-radius: 4px;
                padding: 6px 10px 6px 30px;
                color: #fff;
                font-size: 0.72rem;
                outline: none;
                transition: all 0.2s ease;
                letter-spacing: 0.3px;
            }

            .sidebar-search-input:focus {
                background: rgba(255, 255, 255, 0.06);
                border-color: #10b981;
                box-shadow: 0 0 10px rgba(16, 185, 129, 0.1);
            }

            .sidebar-search-input::placeholder {
                color: rgba(255, 255, 255, 0.25);
            }

            .search-icon {
                position: absolute;
                left: 10px;
                width: 12px !important;
                height: 12px !important;
                color: rgba(255, 255, 255, 0.3);
                pointer-events: none;
            }

            .menu-label {
                margin-top: 0.5rem !important;
                padding-top: 0.5rem !important;
            }

            /* Collapsed State Handling */
            .sidebar-collapsed .sidebar-search-wrapper {
                display: none;
            }
        </style>

        <nav class="sidebar-menu">
            <div class="menu-label"><span>Menu Utama</span></div>
            <a href="{{ route('dashboard') }}" class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i data-feather="home"></i> <span>Dashboard</span>
            </a>


            @auth
                @if(auth()->user()->hasRole(['super_admin', 'admin_radiologi', 'radiografer']))
                    <a href="{{ route('patients.index') }}"
                        class="menu-item {{ request()->routeIs('patients.*') ? 'active' : '' }}">
                        <i data-feather="users"></i> <span>Data Pasien</span>
                    </a>
                @endif

                @if(auth()->user()->hasRole(['super_admin', 'admin_radiologi', 'radiografer', 'dokter_radiologi']))
                    <a href="{{ route('orders.index') }}"
                        class="menu-item {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                        <i data-feather="clipboard"></i> <span>Order Radiologi</span>
                    </a>
                @endif

                @if(auth()->user()->hasRole(['super_admin', 'admin_radiologi', 'radiografer']))
                    <a href="{{ route('queue.sampling') }}"
                        class="menu-item {{ request()->routeIs('queue.sampling') ? 'active' : '' }}">
                        <i data-feather="list"></i> <span>Antrean Sampling</span>
                    </a>
                @endif

                @if(auth()->user()->hasRole(['super_admin', 'dokter_radiologi']))
                    <a href="{{ route('results.index') }}"
                        class="menu-item {{ request()->routeIs('results.*') ? 'active' : '' }}">
                        <i data-feather="edit-3"></i> <span>Hasil Pemeriksaan</span>
                    </a>
                @endif



                @if(auth()->user()->hasRole(['super_admin', 'admin_radiologi', 'radiografer', 'dokter_radiologi', 'it_support']))
                    <div class="menu-label"><span>SIMRS Khanza</span></div>
                    <a href="{{ route('simrs.permintaan') }}"
                        class="menu-item {{ request()->routeIs('simrs.permintaan') ? 'active' : '' }}">
                        <i data-feather="file-plus"></i> <span>Permintaan Radiologi</span>
                    </a>
                    <a href="{{ route('simrs.hasil') }}"
                        class="menu-item {{ request()->routeIs('simrs.hasil') ? 'active' : '' }}">
                        <i data-feather="check-circle"></i> <span>Hasil Radiologi</span>
                    </a>

                    <div class="menu-label"><span>SATUSEHAT Kemenkes</span></div>
                    <a href="{{ route('satusehat.kirim-encounter') }}"
                        class="menu-item {{ request()->routeIs('satusehat.kirim-encounter') ? 'active' : '' }}">
                        <i data-feather="users"></i> <span>Kirim Encounter</span>
                    </a>
                    <a href="{{ route('satusehat.kirim-servicerequest') }}"
                        class="menu-item {{ request()->routeIs('satusehat.kirim-servicerequest') ? 'active' : '' }}">
                        <i data-feather="file-plus"></i> <span>Kirim ServiceRequest</span>
                    </a>
                    <a href="{{ route('satusehat.kirim-specimen') }}"
                        class="menu-item {{ request()->routeIs('satusehat.kirim-specimen') ? 'active' : '' }}">
                        <i data-feather="droplet"></i> <span>Kirim Specimen</span>
                    </a>
                    <a href="{{ route('satusehat.kirim-imaging') }}"
                        class="menu-item {{ request()->routeIs('satusehat.kirim-imaging') ? 'active' : '' }}">
                        <i data-feather="image"></i> <span>Kirim ImageStudy</span>
                    </a>
                    <a href="{{ route('satusehat.kirim-observation') }}"
                        class="menu-item {{ request()->routeIs('satusehat.kirim-observation') ? 'active' : '' }}">
                        <i data-feather="file-text"></i> <span>Kirim Observation</span>
                    </a>
                    <a href="{{ route('satusehat.kirim-observation-lab-pk') }}"
                        class="menu-item {{ request()->routeIs('satusehat.kirim-observation-lab-pk') ? 'active' : '' }}">
                        <i data-feather="microscope"></i> <span>Kirim Observation Lab PK</span>
                    </a>
                    <a href="{{ route('satusehat.kirim-observation-ttv') }}"
                        class="menu-item {{ request()->routeIs('satusehat.kirim-observation-ttv') ? 'active' : '' }}">
                        <i data-feather="heart"></i> <span>Kirim Observation TTV</span>
                    </a>
                    <a href="{{ route('satusehat.kirim-diagnosticreport') }}"
                        class="menu-item {{ request()->routeIs('satusehat.kirim-diagnosticreport') ? 'active' : '' }}">
                        <i data-feather="check-square"></i> <span>Kirim Diag.Report</span>
                    </a>
                    <a href="{{ route('satusehat.kirim-condition') }}"
                        class="menu-item {{ request()->routeIs('satusehat.kirim-condition') ? 'active' : '' }}">
                        <i data-feather="activity"></i> <span>Kirim Condition</span>
                    </a>
                    <a href="{{ route('satusehat.kirim-procedure') }}"
                        class="menu-item {{ request()->routeIs('satusehat.kirim-procedure') ? 'active' : '' }}">
                        <i data-feather="tool"></i> <span>Kirim Procedure</span>
                    </a>
                    <a href="{{ route('satusehat.kirim-allergy') }}"
                        class="menu-item {{ request()->routeIs('satusehat.kirim-allergy') ? 'active' : '' }}">
                        <i data-feather="alert-circle"></i> <span>Kirim Allergy</span>
                    </a>
                    <a href="{{ route('satusehat.kirim-episodeofcare') }}"
                        class="menu-item {{ request()->routeIs('satusehat.kirim-episodeofcare') ? 'active' : '' }}">
                        <i data-feather="briefcase"></i> <span>Kirim EpisodeOfCare</span>
                    </a>
                    <a href="{{ route('satusehat.kirim-medication') }}"
                        class="menu-item {{ request()->routeIs('satusehat.kirim-medication') ? 'active' : '' }}">
                        <i data-feather="package"></i> <span>Kirim Medication</span>
                    </a>
                    <a href="{{ route('satusehat.kirim-vaksin') }}"
                        class="menu-item {{ request()->routeIs('satusehat.kirim-vaksin') ? 'active' : '' }}">
                        <i data-feather="shield"></i> <span>Kirim Vaksin</span>
                    </a>
                    <a href="{{ route('satusehat.kirim-vaksin-ori') }}"
                        class="menu-item {{ request()->routeIs('satusehat.kirim-vaksin-ori') ? 'active' : '' }}">
                        <i data-feather="shield-off"></i> <span>Kirim Vaksin Ori</span>
                    </a>
                    <a href="{{ route('satusehat.mapping-episodeofcare') }}"
                        class="menu-item {{ request()->routeIs('satusehat.mapping-episodeofcare') ? 'active' : '' }}">
                        <i data-feather="map-pin"></i> <span>Mapping EpisodeOfCare</span>
                    </a>
                    <a href="{{ route('satusehat.mapping-lokasi') }}"
                        class="menu-item {{ request()->routeIs('satusehat.mapping-lokasi') ? 'active' : '' }}">
                        <i data-feather="map-pin"></i> <span>Mapping Lokasi</span>
                    </a>
                    <a href="{{ route('satusehat.mapping-organisasi') }}"
                        class="menu-item {{ request()->routeIs('satusehat.mapping-organisasi') ? 'active' : '' }}">
                        <i data-feather="briefcase"></i> <span>Mapping Organisasi</span>
                    </a>
                    <a href="{{ route('satusehat.mapping-laborat') }}"
                        class="menu-item {{ request()->routeIs('satusehat.mapping-laborat') ? 'active' : '' }}">
                        <i data-feather="layers"></i> <span>Mapping Laborat</span>
                    </a>
                    <a href="{{ route('satusehat.mapping-radiologi') }}"
                        class="menu-item {{ request()->routeIs('satusehat.mapping-radiologi') ? 'active' : '' }}">
                        <i data-feather="image"></i> <span>Mapping Radiologi</span>
                    </a>
                    <a href="{{ route('satusehat.mapping-obat') }}"
                        class="menu-item {{ request()->routeIs('satusehat.mapping-obat') ? 'active' : '' }}">
                        <i data-feather="package"></i> <span>Mapping Obat</span>
                    </a>
                    <a href="{{ route('satusehat.mapping-vaksin') }}"
                        class="menu-item {{ request()->routeIs('satusehat.mapping-vaksin') ? 'active' : '' }}">
                        <i data-feather="thermometer"></i> <span>Mapping Vaksin</span>
                    </a>
                    <a href="{{ route('satusehat.mapping-allergy') }}"
                        class="menu-item {{ request()->routeIs('satusehat.mapping-allergy') ? 'active' : '' }}">
                        <i data-feather="tag"></i> <span>Mapping Alergi</span>
                    </a>

                    @if(auth()->user()->hasRole(['super_admin', 'admin_radiologi', 'it_support']))
                        <a href="{{ route('simrs.modality-map.index') }}"
                            class="menu-item {{ request()->routeIs('simrs.modality-map.*') ? 'active' : '' }}">
                            <i data-feather="sliders"></i> <span>Mapping Modality</span>
                        </a>
                    @endif
                @endif


                @if(auth()->user()->hasRole(['super_admin', 'admin_radiologi', 'it_support']))
                    <div class="menu-label"><span>Master Data</span></div>
                    <a href="{{ route('master.doctors.index') }}"
                        class="menu-item {{ request()->routeIs('master.doctors.*') ? 'active' : '' }}">
                        <i data-feather="user-check"></i> <span>Master Dokter</span>
                    </a>
                    <a href="{{ route('master.radiographers.index') }}"
                        class="menu-item {{ request()->routeIs('master.radiographers.*') ? 'active' : '' }}">
                        <i data-feather="user"></i> <span>Master Radiografer</span>
                    </a>
                    <a href="{{ route('master.modalities.index') }}"
                        class="menu-item {{ request()->routeIs('master.modalities.*') ? 'active' : '' }}">
                        <i data-feather="monitor"></i> <span>Master Modalitas</span>
                    </a>
                    <a href="{{ route('master.examination-types.index') }}"
                        class="menu-item {{ request()->routeIs('master.examination-types.*') ? 'active' : '' }}">
                        <i data-feather="list"></i> <span>Jenis Pemeriksaan</span>
                    </a>
                    <a href="{{ route('master.rooms.index') }}"
                        class="menu-item {{ request()->routeIs('master.rooms.*') ? 'active' : '' }}">
                        <i data-feather="map-pin"></i> <span>Master Ruangan</span>
                    </a>
                    <a href="{{ route('master.templates.index') }}"
                        class="menu-item {{ request()->routeIs('master.templates.*') ? 'active' : '' }}">
                        <i data-feather="copy"></i> <span>Template Hasil</span>
                    </a>
                    <a href="{{ route('master.galleries.index') }}"
                        class="menu-item {{ request()->routeIs('master.galleries.*') ? 'active' : '' }}">
                        <i data-feather="image"></i> <span>Master Galeri Display</span>
                    </a>
                @endif

                @if(auth()->user()->hasRole(['super_admin', 'admin_radiologi', 'it_support']))
                    <div class="menu-label"><span>Pengaturan</span></div>
                    @if(auth()->user()->hasRole(['super_admin']))
                        <a href="{{ route('users.index') }}" class="menu-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                            <i data-feather="shield"></i> <span>Manajemen User</span>
                        </a>
                    @endif
                    @if(auth()->user()->hasRole(['super_admin', 'admin_radiologi']))
                        <a href="{{ route('settings.index') }}"
                            class="menu-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                            <i data-feather="settings"></i> <span>Setting Instansi</span>
                        </a>
                    @endif
                @endif

                @if(auth()->user()->hasRole(['super_admin', 'it_support']))
                    <a href="{{ route('audit.index') }}" class="menu-item {{ request()->routeIs('audit.*') ? 'active' : '' }}">
                        <i data-feather="file-text"></i> <span>Audit Trail</span>
                    </a>
                @endif

                @if(auth()->user()->hasRole(['super_admin', 'admin_radiologi', 'direktur']))
                    <div class="menu-label"><span>Analitik & Laporan</span></div>
                    <a href="{{ route('reports.requests') }}"
                        class="menu-item {{ request()->routeIs('reports.requests') ? 'active' : '' }}">
                        <i data-feather="list"></i> <span>Semua Permintaan</span>
                    </a>
                    <a href="{{ route('reports.duration') }}"
                        class="menu-item {{ request()->routeIs('reports.duration') ? 'active' : '' }}">
                        <i data-feather="bar-chart-2"></i> <span>Lama Pelayanan</span>
                    </a>
                    <a href="{{ route('reports.duration-by-exam') }}"
                        class="menu-item {{ request()->routeIs('reports.duration-by-exam') ? 'active' : '' }}">
                        <i data-feather="clock"></i> <span>Lama Pelayanan (Per Jenis)</span>
                    </a>
                    <a href="{{ route('reports.examination') }}"
                        class="menu-item {{ request()->routeIs('reports.examination') ? 'active' : '' }}">
                        <i data-feather="pie-chart"></i> <span>Statistik Pemeriksaan</span>
                    </a>
                @endif






                @if(auth()->user()->hasRole(['super_admin', 'admin_radiologi', 'radiografer', 'dokter_radiologi', 'it_support']))
                    <div class="menu-label"><span>PACS Server</span></div>
                    <a href="{{ route('pacs.search') }}"
                        class="menu-item {{ request()->routeIs('pacs.search') ? 'active' : '' }}">
                        <i data-feather="search"></i> <span>Cari Study</span>
                    </a>
                    <a href="{{ route('pacs.index') }}"
                        class="menu-item {{ request()->routeIs('pacs.index') ? 'active' : '' }}">
                        <i data-feather="server"></i> <span>Dashboard PACS</span>
                    </a>
                    <a href="{{ route('pacs.patients') }}"
                        class="menu-item {{ request()->routeIs('pacs.patients') || request()->routeIs('pacs.patient-detail') ? 'active' : '' }}">
                        <i data-feather="user"></i> <span>Pasien DICOM</span>
                    </a>
                    <a href="{{ route('pacs.studies') }}"
                        class="menu-item {{ request()->routeIs('pacs.studies') || request()->routeIs('pacs.study-detail') ? 'active' : '' }}">
                        <i data-feather="folder"></i> <span>Studies DICOM</span>
                    </a>
                    {{-- Menu admin-only: tidak tampil untuk dokter --}}
                    @if(auth()->user()->hasRole(['super_admin', 'admin_radiologi', 'radiografer', 'it_support']))
                        <a href="{{ route('pacs.worklists') }}"
                            class="menu-item {{ request()->routeIs('pacs.worklists') ? 'active' : '' }}">
                            <i data-feather="clipboard"></i> <span>Worklists DICOM</span>
                        </a>
                        <a href="{{ route('pacs.modalities') }}"
                            class="menu-item {{ request()->routeIs('pacs.modalities') ? 'active' : '' }}">
                            <i data-feather="monitor"></i> <span>Konfigurasi Modalitas</span>
                        </a>
                        <a href="{{ route('pacs.upload') }}"
                            class="menu-item {{ request()->routeIs('pacs.upload') ? 'active' : '' }}">
                            <i data-feather="upload-cloud"></i> <span>Upload Gambar (Manual)</span>
                        </a>
                    @endif
                @endif
            @endauth

            <div class="menu-label"><span>Lainnya</span></div>
            <a href="{{ route('about') }}" class="menu-item {{ request()->routeIs('about') ? 'active' : '' }}">
                <i data-feather="heart"></i> <span>Tentang Aplikasi</span>
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- TOP NAVBAR -->
        <header class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i data-feather="menu"></i>
                </button>
                <span class="navbar-title">@yield('page-title', 'Dashboard')</span>

                <!-- Privacy Mode Toggle -->
                <button class="btn btn-sm rounded-0 border-0 shadow-none ms-2 d-flex align-items-center"
                    id="btnPrivacyToggle" onclick="togglePrivacyMode()" title="Toggle Privacy Mode (Sensor Data)">
                    <i data-feather="eye" id="iconPrivacyOn" class="text-emerald"></i>
                    <i data-feather="eye-off" id="iconPrivacyOff" class="text-danger d-none"></i>
                    <span class="ms-2 fw-bold x-small d-none d-md-inline" id="textPrivacy">PRIVACY OFF</span>
                </button>
            </div>

            <div class="navbar-user">
                @auth
                    <!-- Notifications -->
                    <div class="dropdown me-3">
                        <div class="position-relative" role="button" data-bs-toggle="dropdown" id="notificationBell">
                            <i data-feather="bell"></i>
                            <span id="notificationBadge"
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"
                                style="font-size: 0.5rem; padding: 0.35em 0.55em;">
                                0
                            </span>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-0"
                            style="width: 320px; border-radius: 12px; overflow: hidden;">
                            <li class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
                                <span class="fw-bold small">Notifikasi</span>
                                <a href="javascript:void(0)" onclick="markAllNotificationsRead()"
                                    class="text-emerald small text-decoration-none" style="font-size: 0.75rem;">Tandai semua
                                    dibaca</a>
                            </li>
                            <div id="notificationList" style="max-height: 400px; overflow-y: auto;">
                                <div class="p-4 text-center text-muted small">
                                    <i data-feather="bell-off" class="mb-2 d-block mx-auto" style="opacity: 0.5;"></i>
                                    Tidak ada notifikasi baru
                                </div>
                            </div>
                            <li class="bg-light">
                                <a class="dropdown-item text-center py-2 small fw-bold text-muted border-top"
                                    href="{{ route('notifications.index') }}">
                                    Lihat Semua Notifikasi
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="user-info text-end">
                        <div class="user-name">{{ Auth::user()->name }}</div>
                        <div class="user-role">{{ Auth::user()->role_label }}</div>
                    </div>
                    <div class="dropdown">
                        <div class="user-avatar" role="button" data-bs-toggle="dropdown">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius: 12px;">
                            <li>
                                <a class="dropdown-item py-2" href="{{ route('about') }}">
                                    <i data-feather="heart" style="width:14px;height:14px;margin-right:8px"></i>Tentang
                                    Aplikasi
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider opacity-25">
                            </li>
                            <li>
                                <a class="dropdown-item py-2 text-danger" href="#"
                                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i data-feather="log-out" style="width:14px;height:14px;margin-right:8px"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-dark btn-sm rounded-0 px-3">
                        <i data-feather="log-in" style="width:14px;margin-right:5px"></i> Login
                    </a>
                @endauth
            </div>
        </header>

        <!-- PAGE CONTENT -->
        <main class="page-content">
            @if(session('success'))
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                            }
                        });
                        Toast.fire({
                            icon: 'success',
                            title: '{{ e(session("success")) }}'
                        });
                    });
                </script>
            @endif

            @if(session('error'))
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Terjadi Kesalahan',
                            text: '{{ e(session("error")) }}',
                            confirmButtonColor: '#10b981',
                        });
                    });
                </script>
            @endif

            @if(isset($errors) && $errors instanceof \Illuminate\Support\ViewErrorBag && $errors->any())
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validasi Gagal',
                            html: `<ul class="text-start small mb-0">
                                                                            @foreach($errors->all() as $error)
                                                                                <li>{{ $error }}</li>
                                                                            @endforeach
                                                                        </ul>`,
                            confirmButtonColor: '#10b981',
                        });
                    });
                </script>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>

    <!-- jQuery (required for Select2) -->
    <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('js/select2.min.js') }}"></script>

    <!-- SweetAlert2 -->
    <script src="{{ asset('js/sweetalert2.min.js') }}"></script>

    <script>
        feather.replace();

        let sidebarTooltips = [];

        function initSidebarTooltips() {
            // Cleanup existing tooltips if any
            sidebarTooltips.forEach(t => t.dispose());
            sidebarTooltips = [];

            if (window.innerWidth > 992) {
                const isCollapsed = document.body.classList.contains('sidebar-collapsed');
                const menuItems = document.querySelectorAll('.sidebar .menu-item');

                menuItems.forEach(el => {
                    const label = el.querySelector('span')?.textContent.trim();
                    if (label) {
                        const tooltip = new bootstrap.Tooltip(el, {
                            title: label,
                            placement: 'right',
                            trigger: 'hover',
                            container: 'body'
                        });

                        if (!isCollapsed) tooltip.disable();
                        sidebarTooltips.push(tooltip);
                    }
                });
            }
        }

        function toggleSidebar() {
            if (window.innerWidth > 992) {
                // Desktop: Toggle collapse
                const isCollapsed = document.body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', isCollapsed);

                // Update tooltips state
                sidebarTooltips.forEach(t => isCollapsed ? t.enable() : t.disable());
            } else {
                // Mobile: Toggle show/hide
                document.getElementById('sidebar').classList.toggle('show');
                document.getElementById('sidebarOverlay').classList.toggle('show');
            }
        }

        // Notifications Logic
        function fetchNotifications() {
            $.get('{{ route('notifications.latest') }}', function (data) {
                const badge = $('#notificationBadge');
                if (data.unreadCount > 0) {
                    badge.text(data.unreadCount).removeClass('d-none');
                } else {
                    badge.addClass('d-none');
                }

                const list = $('#notificationList');
                if (data.notifications.length > 0) {
                    let html = '';
                    data.notifications.forEach(n => {
                        const isUnread = n.read_at === null;
                        html += `
                            <li>
                                <a class="dropdown-item p-3 border-bottom notification-item ${isUnread ? 'bg-light' : ''}" 
                                   href="${n.data.url}" onclick="markNotificationRead('${n.id}', event, '${n.data.url}')">
                                    <div class="d-flex gap-2">
                                        <div class="notification-icon rounded-circle bg-emerald-soft p-2 flex-shrink-0" style="width:32px; height:32px; display:flex; align-items:center; justify-content:center;">
                                            <i data-feather="file-text" style="width:14px; color:var(--primary-emerald)"></i>
                                        </div>
                                        <div>
                                            <div class="small fw-bold text-dark">${n.data.message}</div>
                                            <div class="text-muted" style="font-size: 0.7rem;">${new Date(n.created_at).toLocaleString('id-ID')}</div>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        `;
                    });
                    list.html(html);
                    feather.replace();
                }
            });
        }

        function markNotificationRead(id, event, url) {
            event.preventDefault();
            $.post(`/notifications/${id}/mark-as-read`, { _token: '{{ csrf_token() }}' }, function () {
                window.location.href = url;
            });
        }

        function markAllNotificationsRead() {
            $.post('{{ route('notifications.mark-all-read') }}', { _token: '{{ csrf_token() }}' }, function () {
                fetchNotifications();
            });
        }

        function togglePrivacyMode() {
            const isPrivacy = document.body.classList.toggle('privacy-mode');
            localStorage.setItem('privacy-mode', isPrivacy);
            updatePrivacyUI(isPrivacy);
        }

        function updatePrivacyUI(isPrivacy) {
            if (isPrivacy) {
                $('#iconPrivacyOn').addClass('d-none');
                $('#iconPrivacyOff').removeClass('d-none');
                $('#textPrivacy').text('PRIVACY ON').addClass('text-danger').removeClass('text-emerald');
                $('#btnPrivacyToggle').attr('title', 'Privacy Mode is ON (Click to show data)');
            } else {
                $('#iconPrivacyOn').removeClass('d-none');
                $('#iconPrivacyOff').addClass('d-none');
                $('#textPrivacy').text('PRIVACY OFF').removeClass('text-danger').addClass('text-emerald');
                $('#btnPrivacyToggle').attr('title', 'Privacy Mode is OFF (Click to sensor data)');
            }
            feather.replace();
        }

        // Initialize on load
        document.addEventListener('DOMContentLoaded', () => {
            initSidebarTooltips();

            // Init Privacy UI
            updatePrivacyUI(document.body.classList.contains('privacy-mode'));

            // Initial fetch and poll every 30s
            @auth
                fetchNotifications();
                setInterval(fetchNotifications, 30000);
            @endauth

            // Global SweetAlert Confirmation Handler
            $(document).on('click', '.swal-confirm', function (e) {
                e.preventDefault();
                const form = $(this).closest('form');
                const title = $(this).data('swal-title') || 'Apakah Anda yakin?';
                const text = $(this).data('swal-text') || 'Tindakan ini tidak dapat dibatalkan.';
                const type = $(this).data('swal-type') || 'warning';
                const confirmText = $(this).data('swal-confirm-text') || 'Ya, Lanjutkan';

                Swal.fire({
                    title: title,
                    text: text,
                    icon: type,
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: confirmText,
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (form.length) form.submit();
                        else if ($(this).attr('href')) window.location.href = $(this).attr('href');
                    }
                });
            });
        });

        // Re-init tooltips if window is resized
        window.addEventListener('resize', () => {
            initSidebarTooltips();
        });
    </script>

    @stack('scripts')

    {{-- Global DICOM Viewer Modal --}}
    <div class="modal fade" id="viewerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-dark border-0">
                <div class="modal-header border-0 p-2 d-flex justify-content-between align-items-center"
                    style="background: #000; border-radius: 0 !important;">
                    <h6 class="modal-title text-white small fw-bold ms-2">DICOM Viewer</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 overflow-hidden">
                    <iframe id="viewerIframe" src=""
                        style="width:100%; height:calc(100vh - 45px); border:none;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openViewer(url) {
            const modalElement = document.getElementById('viewerModal');
            const modal = new bootstrap.Modal(modalElement);
            document.getElementById('viewerIframe').src = url;
            modal.show();
        }

        document.addEventListener('DOMContentLoaded', function () {
            const vModal = document.getElementById('viewerModal');
            if (vModal) {
                vModal.addEventListener('hidden.bs.modal', function () {
                    document.getElementById('viewerIframe').src = '';
                });
            }

            // Sidebar Search Logic
            const searchInput = document.getElementById('sidebarSearch');
            if (searchInput) {
                searchInput.addEventListener('keyup', function () {
                    const term = this.value.toLowerCase();
                    const menuItems = document.querySelectorAll('.sidebar-menu .menu-item');
                    const menuLabels = document.querySelectorAll('.sidebar-menu .menu-label');

                    menuItems.forEach(item => {
                        const text = item.querySelector('span').innerText.toLowerCase();
                        if (text.includes(term)) {
                            item.style.display = 'flex';
                        } else {
                            item.style.display = 'none';
                        }
                    });

                    // Hide labels if no visible items follow it
                    menuLabels.forEach(label => {
                        let current = label.nextElementSibling;
                        let hasVisible = false;
                        while (current && !current.classList.contains('menu-label')) {
                            if (current.classList.contains('menu-item') && current.style.display !== 'none') {
                                hasVisible = true;
                                break;
                            }
                            current = current.nextElementSibling;
                        }
                        label.style.display = hasVisible ? 'block' : 'none';
                    });
                });

                // Prevent search input from triggering sidebar toggle on mobile
                searchInput.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
            }
        });

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Heartbeat check: If kernel doesn't signal verified in 5 seconds, halt system
            setTimeout(() => {
                if (!document.body.classList.contains('kernel-verified')) {
                    document.body.style.opacity = '1';
                    document.body.innerHTML = `
                        <div id="kernel-error-overlay">
                            <div>
                                <h1 style="font-size: 3rem; margin-bottom: 10px;">SYSTEM HALTED</h1>
                                <p style="font-size: 1.2rem; opacity: 0.8;">[FATAL] Medizen System Kernel Link Broken or Tampered.</p>
                                <p style="margin-top: 20px; color: #666;">Silakan hubungi Administrator Sistem.</p>
                            </div>
                        </div>
                    `;
                }
            }, 5000);
        });
    </script>

</body>

</html>