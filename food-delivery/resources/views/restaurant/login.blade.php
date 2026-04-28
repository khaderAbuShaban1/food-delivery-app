<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - لوحة تحكم المطعم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #F8FAFC;
            --bg-card: #FFFFFF;
            --border-subtle: #E2E8F0;
            --accent-primary: #F97316;
            --accent-hover: #EA580C;
            --text-primary: #111827;
            --text-secondary: #6B7280;
            --radius-lg: 24px;
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        * {
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--bg-main);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 1rem;
        }

        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            padding: 2.5rem;
        }

        .login-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, var(--accent-primary), #C2410C);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .login-icon i {
            font-size: 2rem;
            color: #FFFFFF;
        }

        .form-control {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            color: var(--text-primary);
            border-radius: 14px;
            padding: 0.875rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: var(--bg-card);
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15);
            color: var(--text-primary);
        }

        .form-control::placeholder {
            color: #9CA3AF;
        }

        .form-label {
            color: var(--text-secondary);
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: block;
        }

        .btn-orange {
            background: linear-gradient(135deg, var(--accent-primary), #C2410C);
            color: #FFFFFF;
            border: none;
            font-weight: 700;
            padding: 0.875rem;
            border-radius: 14px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-orange:hover {
            background: linear-gradient(135deg, var(--accent-hover), var(--accent-primary));
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(249, 115, 22, 0.35);
            color: #FFFFFF;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #DC2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 14px;
            padding: 0.875rem 1rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="text-center">
                <div class="login-icon">
                    <i class="bi bi-shop"></i>
                </div>
                <h1 class="login-title">لوحة تحكم المطعم</h1>
                <p class="login-subtitle">سجل دخولك للمتابعة</p>
            </div>

            @if(session('error'))
                <div class="alert-danger d-flex align-items-center mb-4">
                    <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('restaurant.login.submit') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email" name="email" class="form-control" required placeholder="restaurant@example.com">
                </div>
                <div class="form-group">
                    <label class="form-label">كلمة المرور</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn-orange">
                    <i class="bi bi-box-arrow-in-left me-2"></i>تسجيل دخول
                </button>
            </form>
        </div>
    </div>
</body>
</html>