<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penyiapan Sistem - Medizen RIS</title>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --emerald: #10b981;
            --emerald-dark: #059669;
            --bg-gray: #f9fafb;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-gray);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .install-card {
            background: #ffffff;
            width: 100%;
            max-width: 500px;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border-top: 5px solid var(--emerald);
        }

        .brand-logo {
            color: var(--emerald);
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .step-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 8px;
        }

        .step-desc {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .btn-install {
            background-color: var(--emerald);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            width: 100%;
            transition: all 0.2s;
        }

        .btn-install:hover {
            background-color: var(--emerald-dark);
            transform: translateY(-1px);
        }

        .btn-install:disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
        }

        .progress-container {
            display: none;
            margin-top: 20px;
        }

        .progress {
            height: 8px;
            border-radius: 10px;
            background-color: #e5e7eb;
        }

        .progress-bar {
            background-color: var(--emerald);
        }

        .status-text {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 10px;
            text-align: center;
        }

        .log-container {
            display: none;
            margin-top: 20px;
            background: #111827;
            color: #10b981;
            font-family: 'Courier New', Courier, monospace;
            padding: 15px;
            font-size: 0.75rem;
            border-radius: 4px;
            max-height: 150px;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <div class="install-card">
        <div class="brand-logo">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
            </svg>
            MEDIZEN
        </div>

        <!-- Step 1: Configuration -->
        <div id="step-config">
            <h2 class="step-title">Konfigurasi Sistem (.env)</h2>
            <p class="step-desc">Tinjau kembali seluruh pengaturan sistem Medizen Anda. Pastikan setiap variabel sesuai
                dengan lingkungan server Anda. Fokus pada database Medizen, SIK, PACS, dan Satu Sehat</p>

            <form id="env-form">
                <div style="max-height: 50vh; overflow-y: auto; padding-right: 10px; margin-bottom: 20px;">
                    @foreach($groupedData as $groupName => $variables)
                        @if(!empty($variables))
                            <div class="mb-4">
                                <h6 class="fw-bold text-emerald border-bottom pb-1 mb-3"
                                    style="font-size: 0.75rem; letter-spacing: 1px;">
                                    {{ strtoupper($groupName) }}
                                </h6>
                                @foreach($variables as $key => $value)
                                    <div class="mb-2">
                                        <label class="small fw-bold text-muted mb-1"
                                            style="font-size: 9px; letter-spacing: 0.5px;">{{ $key }}</label>
                                        <input
                                            type="{{ str_contains($key, 'PASS') || str_contains($key, 'SECRET') ? 'password' : 'text' }}"
                                            name="{{ $key }}"
                                            class="form-control form-control-sm border-light bg-light py-2 shadow-none fw-bold"
                                            value="{{ $value }}">
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                </div>
                <button type="submit" class="btn-install" id="btn-save-env">
                    Simpan & Verifikasi Konfigurasi
                </button>
            </form>
        </div>

        <!-- Step 2: Installation -->
        <div id="step-install" style="display: none;">
            <h2 class="step-title">Instalasi Database</h2>
            <p class="step-desc">Konfigurasi berhasil disimpan. Sekarang kita akan membuat tabel dan data awal untuk
                aplikasi Medizen Anda.</p>

            <div class="alert bg-success-soft text-success small py-2 mb-4 d-flex align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                    <polyline points="22 4 12 14.01 9 11.01" />
                </svg>
                Koneksi Database Terverifikasi
            </div>

            <button class="btn-install" id="btn-run-install">
                Hubungkan & Siapkan Database
            </button>
        </div>

        <div class="progress-container" id="loading-view">
            <h2 class="step-title">Sedang Menyiapkan...</h2>
            <p class="step-desc">Mohon jangan menutup halaman ini hingga proses selesai. Kami sedang menjalankan tabel
                sistem dan data pendukung.</p>

            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                    style="width: 100%"></div>
            </div>

            <div class="status-text" id="status-message">Menjalankan migrasi database...</div>

            <div class="log-container" id="install-logs"></div>
        </div>
    </div>

    <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            // Handle Step 1: Save ENV
            $('#env-form').submit(function (e) {
                e.preventDefault();
                const btn = $('#btn-save-env');
                btn.prop('disabled', true).text('Menyimpan...');

                $.ajax({
                    url: '{{ route("install.update-env") }}',
                    method: 'POST',
                    data: $(this).serialize() + '&_token={{ csrf_token() }}',
                    success: function (response) {
                        $('#step-config').hide();
                        $('#step-install').show();
                    },
                    error: function (xhr) {
                        btn.prop('disabled', false).text('Simpan & Verifikasi Koneksi');
                        alert('Gagal menyimpan konfigurasi. Periksa izin akses file .env');
                    }
                });
            });

            // Handle Step 2: Run Migration
            $('#btn-run-install').click(function () {
                $('#step-install').hide();
                $('#loading-view').show();

                $.ajax({
                    url: '{{ route("install.run") }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        $('#status-message').text('Inisialisasi berhasil!');
                        $('#install-logs').show().html('<pre>' + response.details + '</pre>');
                        setTimeout(function () {
                            window.location.href = response.redirect_to || '{{ route('login') }}';
                        }, 2000);
                    },
                    error: function (xhr) {
                        $('#loading-view').hide();
                        $('#step-install').show();
                        const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan sistem.';
                        alert('Gagal: ' + errorMsg);
                    }
                });
            });
        });
    </script>
</body>

</html>