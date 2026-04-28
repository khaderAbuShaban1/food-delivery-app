<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - لوحة التحكم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF6B2C;
            --primary-light: #FF8F66;
            --primary-muted: #FFE8DC;
            --primary-hover: #E55A1C;
            --bg-page: #F5F6FA;
            --white: #FFFFFF;
            --text-dark: #1A1A1A;
            --text-muted: #6B7280;
            --border: #E5E7EB;
            --shadow: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
            --radius: 24px;
        }

        * { font-family: 'Cairo', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--bg-page);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container { width: 100%; max-width: 420px; padding: 1rem; }

        .login-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            padding: 2.5rem;
        }

        .login-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .login-icon i { font-size: 2rem; color: #fff; }

        .form-control {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 0.875rem 1rem;
            font-family: 'Cairo', sans-serif;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-muted);
        }

        .form-label {
            color: var(--text-muted);
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: block;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            border: none;
            padding: 0.875rem;
            border-radius: 12px;
            font-weight: 700;
            width: 100%;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-hover), var(--primary));
        }

        .alert-error {
            background: #FEE2E2;
            color: #DC2626;
            border: none;
            border-radius: 12px;
            padding: 1rem;
        }

        .login-title { font-size: 1.5rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.5rem; }
        .login-subtitle { color: var(--text-muted); margin-bottom: 2rem; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="text-center">
                <div class="login-icon">
                    <i class="fas fa-th-large"></i>
                </div>
                <h1 class="login-title">لوحة التحكم</h1>
                <p class="login-subtitle">سجل دخولك للمتابعة</p>
            </div>

            @if(session('error'))
                <div class="alert-error d-flex align-items-center mb-4">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.submit') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email" name="email" class="form-control" required placeholder="admin@example.com">
                </div>
                <div class="mb-4">
                    <label class="form-label">كلمة المرور</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-primary text-white">
                    <i class="fas fa-sign-in-alt me-2"></i>تسجيل دخول
                </button>
            </form>
        </div>
    </div>
</body>
</html>