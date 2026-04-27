<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Dashboard - Food Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .sidebar { min-height: 100vh; background: #343a40; }
        .sidebar a { color: #fff; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover, .sidebar a.active { background: #495057; }
        .stat-card { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar p-0">
                <div class="p-3 text-white border-bottom border-secondary">
                    <h5><i class="bi bi-shop"></i> {{ $restaurant->name }}</h5>
                    <span class="badge {{ $restaurant->is_open ? 'bg-success' : 'bg-danger' }}">
                        {{ $restaurant->is_open ? 'Open' : 'Closed' }}
                    </span>
                </div>
                <nav class="nav flex-column">
                    <a href="{{ route('restaurant.dashboard') }}" class="{{ request()->routeIs('restaurant.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a href="{{ route('restaurant.menu') }}" class="{{ request()->routeIs('restaurant.menu') ? 'active' : '' }}">
                        <i class="bi bi-menu-button-wide"></i> Menu
                    </a>
                    <a href="{{ route('restaurant.orders') }}" class="{{ request()->routeIs('restaurant.orders') ? 'active' : '' }}">
                        <i class="bi bi-bag"></i> Orders
                        @if(isset($pendingOrders) && $pendingOrders > 0)
                            <span class="badge bg-warning text-dark">{{ $pendingOrders }}</span>
                        @endif
                    </a>
                    <form action="{{ route('restaurant.logout') }}" method="POST" class="p-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-light btn-sm w-100">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </nav>
            </div>
            <div class="col-md-10 p-4">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
                @yield('content')
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>