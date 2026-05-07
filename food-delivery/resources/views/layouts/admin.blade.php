<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'لوحة المؤشرات')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
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
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --info: #3B82F6;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
            --radius: 16px;
            --radius-sm: 12px;
            --sidebar-width: 280px;
        }

        * { font-family: 'Cairo', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }

        body { background: var(--bg-page); min-height: 100vh; }

        .admin-layout { display: flex; min-height: 100vh; }

        .sidebar {
            width: var(--sidebar-width);
            background: var(--white);
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            right: 0;
            height: 100vh;
            z-index: 100;
        }

        .sidebar-header { padding: 1.5rem; border-bottom: 1px solid var(--border); }

        .sidebar-logo { display: flex; align-items: center; gap: 0.75rem; }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), #FF8F66);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }

        .logo-text { font-size: 1.1rem; font-weight: 700; color: var(--text-dark); }

        .sidebar-nav { flex: 1; padding: 1rem; overflow-y: auto; }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            border-radius: 12px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.25rem;
            transition: all 0.2s;
        }

        .nav-item:hover { background: var(--bg-page); color: var(--text-dark); }

        .nav-item.active {
            background: var(--primary-muted);
            color: var(--primary);
        }

        .nav-item i { font-size: 1.1rem; }

        .nav-section-title {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1rem 1rem 0.5rem;
        }

        .sidebar-footer { padding: 1rem; border-top: 1px solid var(--border); }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            border-radius: 12px;
            color: var(--danger);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            background: none;
            border: none;
            width: 100%;
            cursor: pointer;
            transition: all 0.2s;
        }

        .logout-btn:hover { background: #FEE2E2; }

        .main-content {
            flex: 1;
            margin-right: var(--sidebar-width);
            padding: 1.5rem 2rem;
        }

        .header { margin-bottom: 1.5rem; }

        .page-title { font-size: 1.5rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.25rem; }

        .page-subtitle { color: var(--text-muted); font-size: 0.9rem; }

        .stat-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: all 0.3s;
            height: 100%;
        }

        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }

        .stat-icon.primary { background: var(--primary-muted); color: var(--primary); }
        .stat-icon.success { background: #D1FAE5; color: var(--success); }
        .stat-icon.info { background: #DBEAFE; color: var(--info); }
        .stat-icon.warning { background: #FEF3C7; color: var(--warning); }

        .stat-value { font-size: 1.75rem; font-weight: 700; color: var(--text-dark); }

        .stat-label { font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem; }

        .section-title { font-size: 1rem; font-weight: 600; color: var(--text-dark); margin-bottom: 1rem; }

        .action-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 1.25rem;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            transition: all 0.3s;
        }

        .action-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }

        .action-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--primary-muted);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .action-text { font-size: 0.9rem; font-weight: 600; color: var(--text-dark); }

        .status-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }

        .status-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }

        .status-label { font-size: 0.9rem; font-weight: 600; color: var(--text-dark); }

        .status-count { font-size: 1.25rem; font-weight: 700; color: var(--text-dark); }

        .progress { height: 8px; border-radius: 4px; background: var(--border); overflow: hidden; }

        .progress-bar { border-radius: 4px; height: 100%; }
        .progress-bar.primary { background: var(--primary); }
        .progress-bar.info { background: var(--info); }
        .progress-bar.success { background: var(--success); }

        .alert-success { background: #D1FAE5; color: #059669; border: none; border-radius: 12px; padding: 1rem; }

        .alert-error { background: #FEE2E2; color: #DC2626; border: none; border-radius: 12px; padding: 1rem; }

        @media (max-width: 992px) {
            .sidebar { display: none; }
            .main-content { margin-right: 0; }
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="logo-icon"><i class="fas fa-shop"></i></div>
                    <span class="logo-text">{{ config('app.name', 'Food Delivery') }}</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-th-large"></i>
                    <span>لوحة المؤشرات</span>
                </a>
                <div class="nav-section-title">الإدارة</div>
                <a href="{{ route('admin.orders') }}" class="nav-item {{ request()->routeIs('admin.orders') ? 'active' : '' }}">
                    <i class="fas fa-shopping-bag"></i>
                    <span>إدارة الطلبات</span>
                </a>
                <a href="{{ route('admin.restaurants') }}" class="nav-item {{ request()->routeIs('admin.restaurants') ? 'active' : '' }}">
                    <i class="fas fa-store"></i>
                    <span>إدارة المطاعم</span>
                </a>
                <a href="{{ route('admin.menu') }}" class="nav-item {{ request()->routeIs('admin.menu') ? 'active' : '' }}">
                    <i class="fas fa-utensils"></i>
                    <span>إدارة الأصناف</span>
                </a>
                <a href="{{ route('admin.offers') }}" class="nav-item {{ request()->routeIs('admin.offers') ? 'active' : '' }}">
                    <i class="fas fa-gift"></i>
                    <span>إدارة العروض</span>
                </a>
                <div class="nav-section-title">الحسابات</div>
                <a href="{{ route('admin.users') }}" class="nav-item {{ request()->routeIs('admin.users') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span>إدارة العملاء</span>
                </a>
                <a href="{{ route('admin.drivers.index') }}" class="nav-item {{ request()->routeIs('admin.drivers.*') ? 'active' : '' }}">
                    <i class="fas fa-id-card"></i>
                    <span>طلبات السائقين</span>
                </a>
                <a href="{{ route('admin.payment-methods.index') }}" class="nav-item {{ request()->routeIs('admin.payment-methods.*') ? 'active' : '' }}">
                    <i class="fas fa-credit-card"></i>
                    <span>طرق تحويل المدفوعات</span>
                </a>
                <a href="{{ route('admin.settings') }}" class="nav-item {{ request()->routeIs('admin.settings*') ? 'active' : '' }}">
                    <i class="fas fa-cogs"></i>
                    <span>الإعدادات المتقدمة</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>تسجيل الخروج</span>
                    </button>
                </form>
            </div>
        </aside>
        <main class="main-content">
            @if(session('success'))
                <div class="alert-success d-flex align-items-center mb-4">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert-error d-flex align-items-center mb-4">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                </div>
            @endif
            @yield('content')
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        toastr.options = { "positionClass": "toast-top-left", "closeButton": true, "debug": false, "newestOnTop": true, "progressBar": true, "preventDuplicates": true, "timeOut": "3000" };
    </script>
    @yield('scripts')
</body>
</html>
