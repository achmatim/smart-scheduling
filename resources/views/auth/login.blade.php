<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Penjadwalan SMP Manggala</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            color: #f8fafc;
        }

        .login-container {
            width: 420px;
            max-width: 90%;
            background: rgba(30, 41, 59, 0.45);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--border-radius-lg);
            padding: 40px;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.3), 0 8px 10px -6px rgb(0 0 0 / 0.3);
            text-align: center;
        }

        .login-logo {
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 8px;
            color: #ffffff;
        }

        .login-logo span {
            color: var(--primary);
        }

        .login-subtitle {
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 30px;
        }

        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }

        .form-label {
            color: #cbd5e1;
            font-size: 13px;
        }

        .form-control {
            background-color: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #ffffff;
            padding: 12px 16px;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
        }

        .login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 24px;
            color: #94a3b8;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }

        .remember-checkbox {
            accent-color: var(--primary);
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            font-size: 15px;
            border-radius: var(--border-radius-sm);
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.25);
            margin-bottom: 20px;
            border-radius: var(--border-radius-sm);
            font-size: 13px;
            text-align: left;
            padding: 12px 16px;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-logo">
            <span>SMP</span> Manggala
        </div>
        <div class="login-subtitle">Sistem Penjadwalan Pelajaran Otomatis</div>

        <!-- Success notification (e.g. after logout) -->
        @if(session('success'))
            <div class="alert alert-success" style="background-color: rgba(16, 185, 129, 0.15); color: #a7f3d0; border: 1px solid rgba(16, 185, 129, 0.25); font-size:13px; text-align:left; padding:12px; margin-bottom:20px; border-radius:8px;">
                {{ session('success') }}
            </div>
        @endif

        <!-- Error notification -->
        @if($errors->any())
            <div class="alert-error">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label" for="email">Username / Email</label>
                <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="admin@manggala.sch.id" required autofocus>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Kata Sandi</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan kata sandi" required>
            </div>

            <div class="login-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember" class="remember-checkbox">
                    Ingat Saya
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-login">Masuk ke Sistem</button>
        </form>
    </div>

</body>
</html>
