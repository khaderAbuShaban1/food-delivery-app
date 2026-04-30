@extends('layouts.admin')

@section('title', 'لوحة المؤشرات')

@section('styles')
<style>
.dashboard-grid { display:grid; grid-template-columns:repeat(12,minmax(0,1fr)); gap:1rem; }
.kpi-card { grid-column:span 3; background:var(--white); border-radius:18px; padding:1.1rem 1.2rem; box-shadow:var(--shadow); display:flex; flex-direction:column; gap:.55rem; min-height:128px; }
.kpi-head { display:flex; justify-content:space-between; align-items:center; }
.kpi-title { color:var(--text-muted); font-size:.82rem; font-weight:600; }
.kpi-icon { width:36px; height:36px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; }
.kpi-icon.primary { background:var(--primary-muted); color:var(--primary); }
.kpi-icon.success { background:#D1FAE5; color:#059669; }
.kpi-icon.info { background:#DBEAFE; color:#2563EB; }
.kpi-icon.warning { background:#FEF3C7; color:#D97706; }
.kpi-value { font-size:1.6rem; font-weight:800; color:var(--text-dark); line-height:1.2; }
.kpi-meta { font-size:.8rem; color:var(--text-muted); }
.panel { background:var(--white); border-radius:18px; box-shadow:var(--shadow); padding:1rem 1.1rem; }
.panel h3 { font-size:.98rem; margin-bottom:.9rem; color:var(--text-dark); }
.panel-half { grid-column:span 6; }
.panel-third { grid-column:span 3; }
.panel-full { grid-column:span 12; }
.summary-list { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.65rem; }
.summary-item { background:#F9FAFB; border:1px solid var(--border); border-radius:12px; padding:.75rem; }
.summary-item .label { font-size:.78rem; color:var(--text-muted); }
.summary-item .value { margin-top:.2rem; font-size:1.05rem; font-weight:700; color:var(--text-dark); }
.progress-stack { display:flex; flex-direction:column; gap:.7rem; }
.progress-row { display:grid; grid-template-columns:110px 1fr 52px; align-items:center; gap:.55rem; }
.progress-label { font-size:.82rem; color:var(--text-dark); font-weight:600; }
.progress-value { text-align:left; font-size:.78rem; color:var(--text-muted); }
.progress-track { height:8px; background:#EEF2F7; border-radius:99px; overflow:hidden; }
.progress-fill { height:100%; border-radius:99px; }
.progress-fill.pending { background:#F59E0B; }
.progress-fill.preparing { background:#6366F1; }
.progress-fill.delivering { background:#3B82F6; }
.progress-fill.completed { background:#10B981; }
.progress-fill.cancelled { background:#EF4444; }
.quick-actions { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.6rem; }
.quick-link { background:#F9FAFB; border:1px solid var(--border); border-radius:12px; padding:.75rem; text-decoration:none; color:var(--text-dark); display:flex; align-items:center; gap:.55rem; font-size:.85rem; font-weight:600; }
.quick-link:hover { border-color:var(--primary); color:var(--primary); }
.recent-orders { overflow-x:auto; }
.recent-table { width:100%; border-collapse:collapse; }
.recent-table th,.recent-table td { padding:.65rem .5rem; border-bottom:1px solid #F1F5F9; text-align:right; white-space:nowrap; font-size:.82rem; }
.recent-table th { color:var(--text-muted); font-weight:700; }
.badge-soft { display:inline-flex; padding:.2rem .55rem; border-radius:999px; font-size:.72rem; font-weight:700; }
.badge-soft.pending { background:#FEF3C7; color:#B45309; }
.badge-soft.accepted { background:#DCFCE7; color:#166534; }
.badge-soft.preparing { background:#E0E7FF; color:#3730A3; }
.badge-soft.delivering { background:#DBEAFE; color:#1D4ED8; }
.badge-soft.completed { background:#D1FAE5; color:#065F46; }
.badge-soft.cancelled { background:#FEE2E2; color:#991B1B; }
@media (max-width:1200px){ .kpi-card{grid-column:span 6;} .panel-half,.panel-third{grid-column:span 12;} }
@media (max-width:768px){ .kpi-card,.panel-third,.panel-half{grid-column:span 12;} .summary-list,.quick-actions{grid-template-columns:1fr;} .progress-row{grid-template-columns:90px 1fr 44px;} }
</style>
@endsection

@section('content')
<div class="header">
    <h1 class="page-title">لوحة المؤشرات</h1>
    <p class="page-subtitle">نظرة شاملة على نشاط المنصة والعمليات الحالية</p>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-head"><span class="kpi-title">إجمالي الطلبات</span><span class="kpi-icon primary"><i class="fas fa-receipt"></i></span></div>
        <div class="kpi-value">{{ number_format($stats['totalOrders']) }}</div>
        <div class="kpi-meta">طلبات اليوم: {{ number_format($stats['todayOrders']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-head"><span class="kpi-title">إيراد اليوم</span><span class="kpi-icon success"><i class="fas fa-coins"></i></span></div>
        <div class="kpi-value">@price($stats['todayRevenue'])</div>
        <div class="kpi-meta">الإيراد الكلي: @price($stats['totalRevenue'])</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-head"><span class="kpi-title">العملاء</span><span class="kpi-icon info"><i class="fas fa-users"></i></span></div>
        <div class="kpi-value">{{ number_format($stats['totalCustomers']) }}</div>
        <div class="kpi-meta">النشطون: {{ number_format($stats['activeCustomers']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-head"><span class="kpi-title">المطاعم</span><span class="kpi-icon warning"><i class="fas fa-store"></i></span></div>
        <div class="kpi-value">{{ number_format($stats['totalRestaurants']) }}</div>
        <div class="kpi-meta">فعّال: {{ number_format($stats['activeRestaurants']) }} | مفتوح: {{ number_format($stats['openRestaurants']) }}</div>
    </div>

    <section class="panel panel-half">
        <h3>حالة الطلبات</h3>
        <div class="progress-stack">
            <div class="progress-row"><span class="progress-label">قيد الانتظار</span><div class="progress-track"><div class="progress-fill pending" style="width: {{ $orderProgress['pending'] }}%"></div></div><span class="progress-value">{{ $orderStats['pending'] }}</span></div>
            <div class="progress-row"><span class="progress-label">قيد التجهيز</span><div class="progress-track"><div class="progress-fill preparing" style="width: {{ $orderProgress['preparing'] }}%"></div></div><span class="progress-value">{{ $orderStats['preparing'] }}</span></div>
            <div class="progress-row"><span class="progress-label">في الطريق</span><div class="progress-track"><div class="progress-fill delivering" style="width: {{ $orderProgress['delivering'] }}%"></div></div><span class="progress-value">{{ $orderStats['delivering'] }}</span></div>
            <div class="progress-row"><span class="progress-label">مكتمل</span><div class="progress-track"><div class="progress-fill completed" style="width: {{ $orderProgress['completed'] }}%"></div></div><span class="progress-value">{{ $orderStats['completed'] }}</span></div>
            <div class="progress-row"><span class="progress-label">ملغي</span><div class="progress-track"><div class="progress-fill cancelled" style="width: {{ $orderProgress['cancelled'] }}%"></div></div><span class="progress-value">{{ $orderStats['cancelled'] }}</span></div>
        </div>
    </section>

    <section class="panel panel-third">
        <h3>ملخص التشغيل</h3>
        <div class="summary-list">
            <div class="summary-item"><div class="label">السائقون</div><div class="value">{{ $stats['activeDrivers'] }} / {{ $stats['totalDrivers'] }}</div></div>
            <div class="summary-item"><div class="label">المدراء</div><div class="value">{{ $stats['totalAdmins'] }}</div></div>
            <div class="summary-item"><div class="label">طلبات مقبولة</div><div class="value">{{ $orderStats['accepted'] }}</div></div>
            <div class="summary-item"><div class="label">طلبات اليوم</div><div class="value">{{ $stats['todayOrders'] }}</div></div>
        </div>
    </section>

    <section class="panel panel-third">
        <h3>إجراءات سريعة</h3>
        <div class="quick-actions">
            <a href="{{ route('admin.orders') }}" class="quick-link"><i class="fas fa-shopping-bag"></i> إدارة الطلبات</a>
            <a href="{{ route('admin.users') }}" class="quick-link"><i class="fas fa-users"></i> إدارة الحسابات</a>
            <a href="{{ route('admin.restaurants') }}" class="quick-link"><i class="fas fa-store"></i> إدارة المطاعم</a>
            <a href="{{ route('admin.menu') }}" class="quick-link"><i class="fas fa-tags"></i> إدارة التصنيفات</a>
            <a href="{{ route('admin.menu') }}" class="quick-link"><i class="fas fa-utensils"></i> تحديث القوائم</a>
            <a href="{{ route('admin.offers') }}" class="quick-link"><i class="fas fa-gift"></i> العروض النشطة</a>
        </div>
    </section>

    <section class="panel panel-full">
        <h3>أحدث الطلبات</h3>
        <div class="recent-orders">
            <table class="recent-table">
                <thead>
                    <tr><th>رقم الطلب</th><th>المطعم</th><th>الحالة</th><th>الإجمالي</th><th>التاريخ</th></tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                        @php
                            $statusLabel = match($order->status) {
                                'pending' => 'قيد الانتظار',
                                'accepted' => 'مقبول',
                                'preparing' => 'قيد التجهيز',
                                'delivering' => 'في الطريق',
                                'completed' => 'مكتمل',
                                'cancelled' => 'ملغي',
                                default => $order->status,
                            };
                        @endphp
                        <tr>
                            <td>#{{ $order->order_number ?: $order->id }}</td>
                            <td>{{ $order->restaurant?->name ?? '-' }}</td>
                            <td><span class="badge-soft {{ $order->status }}">{{ $statusLabel }}</span></td>
                            <td>@price($order->total_price)</td>
                            <td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align:center;color:var(--text-muted);">لا توجد طلبات حديثة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection