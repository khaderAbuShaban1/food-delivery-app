@extends('layouts.admin')

@section('title', 'إدارة الطلبات')

@section('content')
<div class="header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">إدارة الطلبات</h1>
        <p class="page-subtitle">عرض ومتابعة جميع الطلبات</p>
    </div>
</div>

<div class="filter-tabs mb-4">
    <a href="{{ route('admin.orders') }}" class="filter-tab {{ !request()->get('status') || request()->get('status') == 'all' ? 'active' : '' }}">الكل</a>
    <a href="{{ route('admin.orders', ['status' => 'pending']) }}" class="filter-tab {{ request()->get('status') == 'pending' ? 'active' : '' }}">قيد الانتظار</a>
    <a href="{{ route('admin.orders', ['status' => 'accepted']) }}" class="filter-tab {{ request()->get('status') == 'accepted' ? 'active' : '' }}">مقبول</a>
    <a href="{{ route('admin.orders', ['status' => 'preparing']) }}" class="filter-tab {{ request()->get('status') == 'preparing' ? 'active' : '' }}">قيد التجهيز</a>
    <a href="{{ route('admin.orders', ['status' => 'delivering']) }}" class="filter-tab {{ request()->get('status') == 'delivering' ? 'active' : '' }}">في الطريق</a>
    <a href="{{ route('admin.orders', ['status' => 'completed']) }}" class="filter-tab {{ request()->get('status') == 'completed' ? 'active' : '' }}">مكتمل</a>
</div>

<style>
.filter-tabs {
    display: flex;
    gap: 0.5rem;
    padding: 0.5rem;
    background: var(--white);
    border-radius: 12px;
    flex-wrap: wrap;
    box-shadow: var(--shadow-sm);
}
.filter-tab {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.85rem;
    color: var(--text-muted);
    text-decoration: none;
    font-weight: 500;
}
.filter-tab:hover, .filter-tab.active {
    background: var(--primary-muted);
    color: var(--primary);
}
</style>

<div class="data-card">
    <div class="data-card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>المطعم</th>
                    <th>السعر</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->restaurant?->name ?? 'غير معروف' }}</td>
                    <td>₪{{ $order->total_price }}</td>
                    <td>
                        <span class="badge-status badge-{{ $order->status }}">
                            @switch($order->status)
                                @case('pending')قيد الانتظار@break
                                @case('accepted')مقبول@break
                                @case('preparing')قيد التجهيز@break
                                @case('delivering')في الطريق@break
                                @case('completed')مكتمل@break
                            @endswitch
                        </span>
                    </td>
                    <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted p-4">لا توجد طلبات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($orders->hasPages())
<div class="mt-4 d-flex justify-content-center">
    {!! $orders->links() !!}
</div>
@endif

<style>
.data-card { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
.data-card-body { padding: 0; }
.table { margin: 0; }
.table th { background: var(--bg页面); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); padding: 0.75rem 1rem; border: none; }
.table td { padding: 0.75rem 1rem; border-color: var(--border); vertical-align: middle; font-size: 0.9rem; }
.table tr:hover { background: var(--bg页面); }
.badge-status { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
.badge-pending { background: #FEF3C7; color: #D97706; }
.badge-accepted { background: #DBEAFE; color: #2563EB; }
.badge-preparing { background: #EDE9FE; color: #7C3AED; }
.badge-delivering { background: #CFFAFE; color: #0891B2; }
.badge-completed { background: #D1FAE5; color: #059669; }
</style>
@endsection