<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pasien - Portal Radiologi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: url('/portal_login_bg_1772616978585.png') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .login-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 0.6));
            backdrop-filter: blur(4px);
            z-index: 1;
        }

        .login-card {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .brand-logo {
            width: 48px;
            height: 48px;
            background: #10b981;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin: 0 auto 20px;
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.4);
        }

        .btn-emerald {
            background: #10b981;
            border: none;
            color: white;
            font-weight: 700;
            border-radius: 10px;
            padding: 12px;
            transition: all 0.3s;
        }

        .btn-emerald:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
            color: white;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #0f172a;
            transition: all 0.2s;
        }

        .form-control:focus {
            background: white;
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        }

        .form-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .footer-text {
            text-align: center;
            margin-top: 30px;
            font-size: 0.75rem;
            color: #94a3b8;
        }
    </style>
</head>

<body>
    <div class="login-overlay"></div>

    <div class="login-card">
        <div class="brand-logo">
            <i data-feather="activity" style="width: 24px; height: 24px;"></i>
        </div>
        <h4 class="text-center fw-800 mb-1" style="letter-spacing: -1px;">Portal Pasien</h4>
        <p class="text-center text-muted small mb-4">Silakan masuk untuk melihat riwayat rontgen Anda.</p>

        <form action="{{ route('portal.login') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label">Nomor Rekam Medis (RM)</label>
                <input type="text" name="no_rm" class="form-control" placeholder="X-XX-XX-XX" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label">NIK (Nomor Induk Kependudukan)</label>
                <input type="text" name="nik" class="form-control" placeholder="32xxxxxxxxxxxxxx" required>
            </div>

            <button type="submit" class="btn btn-emerald w-100 mb-3">
                MASUK KE PORTAL
            </button>
        </form>

        <div class="footer-text">
            &copy; {{ date('Y') }} Sistem Informasi Radiologi.<br>
            Akses aman dan terlindungi.
        </div>
    </div>

    @if(session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal Masuk',
                text: '{{ session('error') }}',
                confirmButtonColor: '#10b981',
            });
        </script>
    @endif

    <script>
        feather.replace();
    </script>
</body>

</html>