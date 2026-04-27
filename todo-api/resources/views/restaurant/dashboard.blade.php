@extends('layouts.restaurant')

@section('content')
<div class="row">
    <div class="col-md-12">
        <h2>Dashboard</h2>
        <p class="text-muted">Welcome back, {{ auth()->user()->name }}!</p>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <h5><i class="bi bi-bag"></i> Total Orders</h5>
                <h2>{{ $ordersCount }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card bg-warning">
            <div class="card-body">
                <h5><i class="bi bi-hourglass-split"></i> Pending</h5>
                <h2>{{ $pendingOrders }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card {{ $restaurant->is_open ? 'bg-success' : 'bg-danger' }} text-white">
            <div class="card-body">
                <h5><i class="bi bi-toggle-on"></i> Status</h5>
                <h2>{{ $restaurant->is_open ? 'Open' : 'Closed' }}</h2>
                <form action="{{ route('restaurant.dashboard.status', $restaurant->id) }}" method="POST" class="mt-2">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="is_open" value="{{ $restaurant->is_open ? 0 : 1 }}">
                    <button type="submit" class="btn btn-sm btn-light">
                        {{ $restaurant->is_open ? 'Close Restaurant' : 'Open Restaurant' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection