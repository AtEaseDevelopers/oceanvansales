<!DOCTYPE html>
<html lang="en">
<head>
    <base href="./">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Login | {{ 'Ocean Van Sales' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            display: flex;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* ── Left Panel ── */
        .left-panel {
            width: 42%;
            min-height: 100vh;
            background: linear-gradient(160deg, #e2f4fb 0%, #b8e2f2 35%, #85cce8 65%, #55b0d8 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 35px 100px;
            position: relative;
            overflow: hidden;
        }

        .left-panel::before {
            content: '';
            position: absolute;
            bottom: -60px;
            left: -80px;
            width: 420px;
            height: 280px;
            background: rgba(255,255,255,0.22);
            border-radius: 50%;
        }

        .left-panel::after {
            content: '';
            position: absolute;
            top: -90px;
            right: -90px;
            width: 320px;
            height: 320px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
        }

        .iceberg {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 55%;
            height: 42%;
            background: linear-gradient(170deg, rgba(255,255,255,0.45) 0%, rgba(200,235,248,0.6) 100%);
            clip-path: polygon(30% 100%, 0% 30%, 20% 0%, 60% 15%, 100% 0%, 100% 100%);
        }

        .left-logo {
            width: 150px;
            height: 150px;
            object-fit: contain;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .left-company-name {
            font-size: 2.1rem;
            font-weight: 800;
            color: #143d5c;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .left-tagline {
            font-size: 0.92rem;
            color: #245870;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            z-index: 1;
        }

        .left-tagline::before,
        .left-tagline::after {
            content: '';
            display: inline-block;
            width: 28px;
            height: 1px;
            background: #245870;
        }

        .left-system-name {
            color: #0a8a9a;
            font-size: 0.97rem;
            font-weight: 600;
            margin-top: 14px;
            margin-bottom: 44px;
            position: relative;
            z-index: 1;
        }

        .feature-cards {
            display: flex;
            gap: 12px;
            position: relative;
            z-index: 1;
        }

        .feature-card {
            background: rgba(255,255,255,0.65);
            backdrop-filter: blur(8px);
            border-radius: 14px;
            padding: 18px 14px 14px;
            text-align: center;
            flex: 1;
            min-width: 88px;
        }

        .feature-card i {
            font-size: 1.5rem;
            color: #0a8a9a;
            margin-bottom: 10px;
            display: block;
        }

        .feature-card span {
            font-size: 0.72rem;
            color: #143d5c;
            font-weight: 600;
            display: block;
            line-height: 1.3;
        }

        /* ── Right Panel ── */
        .right-panel {
            width: 58%;
            min-height: 100vh;
            background: #f4fafd;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 80px;
            position: relative;
        }

        .right-inner {
            width: 100%;
            max-width: 460px;
        }

        .avatar-wrap {
            width: 72px;
            height: 72px;
            background: #d8f2f5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 26px;
        }

        .avatar-wrap i {
            font-size: 1.9rem;
            color: #0a8a9a;
        }

        .welcome-title {
            font-size: 2rem;
            font-weight: 800;
            color: #0d2f4a;
            text-align: center;
            margin-bottom: 8px;
        }

        .welcome-sub {
            color: #6b7e8f;
            text-align: center;
            font-size: 0.93rem;
            margin-bottom: 36px;
        }

        .field-wrap {
            position: relative;
            margin-bottom: 16px;
        }

        .field-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #0a8a9a;
            font-size: 1rem;
            pointer-events: none;
        }

        .field-wrap input {
            width: 100%;
            height: 52px;
            padding: 0 44px;
            border-radius: 12px;
            border: 1.5px solid #cce6f0;
            background: #eef7fc;
            font-size: 0.95rem;
            color: #1a3a5a;
            outline: none;
            transition: border-color 0.2s, background 0.2s;
        }

        .field-wrap input:focus {
            border-color: #0a8a9a;
            background: #fff;
        }

        .field-wrap input.is-invalid {
            border-color: #dc3545;
        }

        .invalid-feedback {
            color: #dc3545;
            font-size: 0.82rem;
            margin-top: 4px;
            padding-left: 4px;
        }

        .eye-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7e8f;
            cursor: pointer;
            font-size: 1rem;
            padding: 0;
        }

        .row-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 4px;
            margin-bottom: 26px;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #4a5e6e;
            cursor: pointer;
        }

        .remember-label input[type="checkbox"] {
            accent-color: #0a8a9a;
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .forgot-link {
            font-size: 0.9rem;
            color: #0a8a9a;
            text-decoration: underline;
            font-weight: 500;
        }

        .forgot-link:hover { color: #087888; }

        .btn-login {
            width: 100%;
            height: 52px;
            background: #0a8a9a;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-login:hover { background: #087888; }

        .divider {
            text-align: center;
            color: #9aabb8;
            font-size: 0.85rem;
            margin: 20px 0;
            position: relative;
        }

        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 43%;
            height: 1px;
            background: #d0e5ef;
        }

        .divider::before { left: 0; }
        .divider::after  { right: 0; }

        .secure-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            color: #6b7e8f;
            font-size: 0.85rem;
        }

        .secure-note i { color: #0a8a9a; }

        .copyright {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            color: #9aabb8;
            font-size: 0.78rem;
        }

        @media (max-width: 768px) {
            .left-panel { display: none; }
            .right-panel { width: 100%; padding: 40px 24px; }
        }
    </style>
</head>
<body>

    <!-- ── Left Panel ── -->
    <div class="left-panel">
        <div class="iceberg"></div>

        <img src="{{ asset('logo.png') }}" alt="{{ 'Ocean Van Sales' }}" class="left-logo">

        <div class="left-company-name">{{ 'Ocean Van Sales' }}</div>

        <div class="left-tagline">Cool Solutions. Reliable Vans.</div>

        <div class="left-system-name">Van Sales &amp; Business Management System</div>

        <div class="feature-cards">
            <div class="feature-card">
                <i class="fa fa-shield"></i>
                <span>Secure Access</span>
            </div>
            <div class="feature-card">
                <i class="fa fa-bar-chart"></i>
                <span>Business Insights</span>
            </div>
            <div class="feature-card">
                <i class="fa fa-users"></i>
                <span>Built for Growth</span>
            </div>
        </div>
    </div>

    <!-- ── Right Panel ── -->
    <div class="right-panel">
        <div class="right-inner">

            <div class="avatar-wrap">
                <i class="fa fa-user"></i>
            </div>

            <h1 class="welcome-title">Welcome Back</h1>
            <p class="welcome-sub">Sign in to access your {{ 'Ocean Van Sales' }} dashboard</p>

            <form method="post" action="{{ url('/login') }}">
                @csrf

                <div class="field-wrap">
                    <i class="fa fa-envelope field-icon"></i>
                    <input type="email" name="email" value="{{ old('email') }}"
                           placeholder="Email address"
                           class="{{ $errors->has('email') ? 'is-invalid' : '' }}">
                    @if ($errors->has('email'))
                        <div class="invalid-feedback">{{ $errors->first('email') }}</div>
                    @endif
                </div>

                <div class="field-wrap">
                    <i class="fa fa-lock field-icon"></i>
                    <input type="password" name="password" id="passwordInput"
                           placeholder="Password"
                           class="{{ $errors->has('password') ? 'is-invalid' : '' }}">
                    <button type="button" class="eye-toggle" onclick="togglePassword()">
                        <i class="fa fa-eye" id="eyeIcon"></i>
                    </button>
                    @if ($errors->has('password'))
                        <div class="invalid-feedback">{{ $errors->first('password') }}</div>
                    @endif
                </div>
                <button type="submit" class="btn-login">Login</button>
            </form>
        </div>

        <div class="copyright">
            &copy; {{ date('Y') }} {{ 'Ocean Van Sales' }}. All rights reserved.
        </div>
    </div>

    <script>
        function togglePassword() {
            var input = document.getElementById('passwordInput');
            var icon  = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>
