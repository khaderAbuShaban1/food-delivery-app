<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Dashboard - Food Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #F8FAFC;
            --bg-sidebar: #FFFFFF;
            --bg-card: #FFFFFF;
            --bg-card-hover: #F8FAFC;
            --border-subtle: #E2E8F0;
            --border-light: #CBD5E1;
            --accent-primary: #F97316;
            --accent-hover: #EA580C;
            --accent-light: #FDBA74;
            --accent-muted: #C2410C;
            --text-primary: #111827;
            --text-secondary: #6B7280;
            --text-muted: #9CA3AF;
            --radius-sm: 12px;
            --radius-md: 16px;
            --radius-lg: 24px;
            --sidebar-width: 260px;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        * { font-family: 'Cairo', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }
        body { background: var(--bg-main); color: var(--text-primary); }

        .modal { z-index: 1060; }
        .modal-backdrop { z-index: 1050; }
        .modal-dialog { z-index: 1061; }
        [dir="rtl"] .modal-dialog { margin-right: auto; margin-left: auto; }

        .dashboard-layout {
            display: grid;
            grid-template-columns: var(--sidebar-width) 1fr;
            grid-template-rows: 1fr;
            min-height: 100vh;
        }

        .sidebar {
            grid-column: 1;
            grid-row: 1;
            background: var(--bg-sidebar);
            border-left: 1px solid var(--border-subtle);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 50;
            box-shadow: var(--shadow-sm);
        }

        .sidebar-brand { padding: 1.5rem; border-bottom: 1px solid var(--border-subtle); }
        .sidebar-nav { flex: 1; padding: 1rem 0; }
        .nav-section-title {
            padding: 0.5rem 1.5rem 0.5rem;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1.25rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--radius-md);
            margin: 0.25rem 0.75rem;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .sidebar-link:hover { background: var(--bg-card-hover); color: var(--text-primary); }
        .sidebar-link.active {
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.1), rgba(249, 115, 22, 0.05));
            color: var(--accent-primary);
            border: 1px solid rgba(249, 115, 22, 0.2);
        }
        .sidebar-link i { font-size: 1.25rem; width: 1.5rem; text-align: center; }
        .sidebar-footer { padding: 1rem 1.25rem; border-top: 1px solid var(--border-subtle); }

        .main-content {
            grid-column: 2;
            grid-row: 1;
            padding: 2rem;
            overflow-y: auto;
            min-height: 100vh;
        }

        .glass-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }
        .glass-card:hover { background: var(--bg-card-hover); border-color: var(--border-light); transform: translateY(-2px); box-shadow: var(--shadow-lg); }

        .btn-orange {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-muted));
            color: #FFFFFF;
            border: none;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            transition: all 0.3s ease;
        }
        .btn-orange:hover { background: linear-gradient(135deg, var(--accent-hover), var(--accent-primary)); transform: translateY(-2px); box-shadow: 0 10px 30px rgba(249, 115, 22, 0.3); color: #FFFFFF; }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border-light);
            color: var(--text-secondary);
        }
        .btn-outline:hover { background: var(--bg-card-hover); border-color: var(--accent-primary); color: var(--accent-primary); }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .menu-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-subtle);
        }
        .menu-card:hover { transform: translateY(-4px) scale(1.02); box-shadow: var(--shadow-xl); border-color: rgba(249, 115, 22, 0.3); }
        .menu-price { color: var(--accent-primary); font-weight: 700; font-size: 1.25rem; }

        .form-control, .form-select {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            color: var(--text-primary);
            border-radius: var(--radius-md);
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            background: var(--bg-card);
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }
        .form-label { color: var(--text-secondary); font-weight: 500; font-size: 0.875rem; margin-bottom: 0.5rem; display: block; }

        .modal-content {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
        }

        .badge {
            padding: 0.375rem 0.75rem;
            border-radius: var(--radius-sm);
            font-weight: 500;
            font-size: 0.75rem;
        }
        .badge-open { background: rgba(34, 197, 94, 0.1); color: #16A34A; }
        .badge-closed { background: rgba(239, 68, 68, 0.1); color: #DC2626; }
        .badge-pending { background: rgba(249, 115, 22, 0.1); color: var(--accent-primary); }
        .badge-accepted { background: rgba(59, 130, 246, 0.1); color: #2563EB; }
        .badge-preparing { background: rgba(234, 88, 12, 0.1); color: #EA580C; }
        .badge-delivering { background: rgba(168, 85, 247, 0.1); color: #9333EA; }
        .badge-completed { background: rgba(34, 197, 94, 0.1); color: #16A34A; }
        .badge-cancelled { background: rgba(239, 68, 68, 0.1); color: #DC2626; }

        .page-title { font-size: 1.75rem; font-weight: 700; color: var(--text-primary); }
        .page-subtitle { color: var(--text-secondary); font-size: 0.9rem; }

        .alert { padding: 1rem 1.25rem; border-radius: var(--radius-md); }
        .alert-success { background: rgba(34, 197, 94, 0.1); color: #16A34A; border: 1px solid rgba(34, 197, 94, 0.2); }
        .alert-danger { background: rgba(239, 68, 68, 0.1); color: #DC2626; border: 1px solid rgba(239, 68, 68, 0.2); }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeInUp 0.5s ease forwards; }

        @media (max-width: 992px) {
            .dashboard-layout { grid-template-columns: 80px 1fr; }
            .sidebar-brand h5, .sidebar-brand .badge, .sidebar-link span, .sidebar-footer span { display: none; }
            .sidebar-link { justify-content: center; padding: 0.875rem; }
            .sidebar-link i { width: auto; }
        }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg-main); }
        ::-webkit-scrollbar-thumb { background: var(--border-light); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--accent-primary); }
    </style>
    @yield('styles')
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 42px; height: 42px; background: linear-gradient(135deg, var(--accent-primary), var(--accent-muted)); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="bi bi-shop" style="font-size: 1.25rem; color: #FFFFFF;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold" style="font-size: 0.95rem; white-space: nowrap;">{{ $restaurant->name ?? 'Restaurant' }}</h5>
                        <span id="sidebarRestaurantStatusBadge" class="badge {{ $restaurant && $restaurant->is_open ? 'badge-open' : 'badge-closed' }}" style="font-size: 0.65rem;">
                            {{ $restaurant && $restaurant->is_open ? 'مفتوح' : 'مغلق' }}
                        </span>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section-title">الرئيسية</div>
                <a href="{{ route('restaurant.dashboard') }}" class="sidebar-link {{ request()->routeIs('restaurant.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>لوحة التحكم</span>
                </a>
                
                <div class="nav-section-title mt-3">الإدارة</div>
                <a href="{{ route('restaurant.menu') }}" class="sidebar-link {{ request()->routeIs('restaurant.menu') ? 'active' : '' }}">
                    <i class="bi bi-card-list"></i>
                    <span>إدارة القائمة</span>
                </a>
                
                <a href="{{ route('restaurant.orders') }}" class="sidebar-link {{ request()->routeIs('restaurant.orders') ? 'active' : '' }}">
                    <i class="bi bi-bag-check"></i>
                    <span>الطلبات</span>
                </a>

                <a href="{{ route('restaurant.settings') }}" class="sidebar-link {{ request()->routeIs('restaurant.settings*') ? 'active' : '' }}">
                    <i class="bi bi-gear"></i>
                    <span>الإعدادات</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <form action="{{ route('restaurant.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline w-100 d-flex align-items-center justify-content-center gap-2" style="border-radius: var(--radius-md); padding: 0.625rem;">
                        <i class="bi bi-box-arrow-left"></i>
                        <span>تسجيل خروج</span>
                    </button>
                </form>
            </div>
        </aside>

        <main class="main-content">
            @if(session('success'))
                <div class="alert alert-success d-flex align-items-center mb-4 animate-fade-in">
                    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger d-flex align-items-center mb-4 animate-fade-in">
                    <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.modal').forEach(function(modal) {
                modal.addEventListener('hidden.bs.modal', function() {
                    document.body.classList.remove('modal-open');
                    setTimeout(function() {
                        document.querySelectorAll('.modal-backdrop').forEach(function(b) { b.remove(); });
                    }, 200);
                });
            });
        });
    </script>
</body>
</html>