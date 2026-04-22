<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MEDIZEN RIS</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Feather Icons -->
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <style>
        :root {
            --primary-emerald: #10b981;
            --primary-dark: #064e3b;
            --accent-green: #059669;
            --bg-neutral: #f8fafc;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        * { box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            /* Premium Green Gradient */
            background: linear-gradient(135deg, #064e3b 0%, #065f46 50%, #10b981 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
            position: relative;
        }

        /* Subtle Medical/Geometric Pattern */
        body::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            z-index: 1;
        }

        /* Ambient Glow */
        body::before {
            content: '';
            position: absolute;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.15) 0%, rgba(6, 78, 59, 0) 70%);
            top: -200px;
            left: -200px;
            z-index: 2;
        }

        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
            padding: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.94); /* Very high opacity for professional white look */
            backdrop-filter: blur(10px); /* Subtle glassmorphism */
            border-radius: 4px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.2);
            padding: 3.5rem 2.75rem;
            transition: transform 0.3s ease;
        }

        .login-brand {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .brand-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            background-color: #f1f5f9;
            color: var(--primary-emerald);
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .brand-logo svg {
            width: 24px;
            height: 24px;
        }

        .login-brand h4 {
            font-weight: 700;
            letter-spacing: -0.5px;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
            font-size: 1.5rem;
        }

        .login-brand p {
            color: var(--text-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
            margin: 0;
        }

        .form-label {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--text-dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border-radius: 2px;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            font-size: 0.9rem;
            color: var(--text-dark);
            background-color: #fcfdfe;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            background-color: #fff;
            border-color: var(--primary-emerald);
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
            outline: none;
        }

        .input-group-text {
            background-color: transparent;
            border-color: #e2e8f0;
            color: var(--text-muted);
            border-radius: 2px;
        }

        .btn-login {
            background-color: #1e293b; /* Premium Dark */
            border: none;
            color: #fff;
            font-weight: 600;
            padding: 0.85rem;
            border-radius: 2px;
            font-size: 0.85rem;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 1rem;
            transition: all 0.2s ease;
        }

        .btn-login:hover {
            background-color: #0f172a;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .remember-me {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .form-check-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: var(--primary-emerald);
            border-color: var(--primary-emerald);
        }

        .alert {
            border-radius: 2px;
            font-size: 0.8rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            border: none;
            border-left: 4px solid #ef4444;
            background-color: #fef2f2;
            color: #991b1b;
        }

        .footer-text {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* Animation */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="login-wrapper fade-in">
        <div class="login-card">
            <div class="login-brand">
                <div class="brand-logo">
                    <i data-feather="activity"></i>
                </div>
                <h4>MEDIZEN</h4>
                <p>Radiology Information System</p>
            </div>

            @if(session('error'))
                <div class="alert alert-danger d-flex align-items-center">
                    <i data-feather="alert-circle" class="me-2" style="width:16px; height:16px;"></i>
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-4">
                    <label class="form-label">Email Access</label>
                    <div class="input-group">
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}" placeholder="name@email.com" required autofocus>
                    </div>
                    @error('email')
                        <div class="invalid-feedback d-block mt-1" style="font-size: 0.75rem;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label">Security Password</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                        placeholder="••••••••" required>
                    @error('password')
                        <div class="invalid-feedback d-block mt-1" style="font-size: 0.75rem;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="remember-me">
                    <div class="form-check">
                        <input type="checkbox" name="remember" class="form-check-input" id="remember"
                            {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">Tetap masuk</label>
                    </div>
                    <a href="#" class="text-decoration-none" style="font-size: 0.8rem; color: var(--primary-emerald);">Bantuan?</a>
                </div>

                <button type="submit" class="btn btn-login">
                    Authenticate
                </button>
            </form>
        </div>
        <div class="footer-text" style="color: #fff;">
            &copy; {{ date('Y') }} MEDIZEN. Build with Excellence.
            <div class="mt-3">
                <a href="{{ route('queue.display') }}" target="_blank" class="text-decoration-none fw-bold" style="color: var(--primary-emerald); font-size: 0.8rem;">
                    <i data-feather="monitor" class="me-1" style="width:14px; height:14px;"></i> BUKA DISPLAY ANTREAN
                </a>
            </div>
        </div>
    </div>

    <script>
        feather.replace();
    </script>
</body>
</html>
