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
        <div id="kpiTotalOrders" class="kpi-value">{{ number_format($stats['totalOrders']) }}</div>
        <div class="kpi-meta">طلبات اليوم: <span id="kpiTodayOrders">{{ number_format($stats['todayOrders']) }}</span></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-head"><span class="kpi-title">إيراد اليوم</span><span class="kpi-icon success"><i class="fas fa-coins"></i></span></div>
        <div id="kpiTodayRevenue" class="kpi-value">@price($stats['todayRevenue'])</div>
        <div class="kpi-meta">الإيراد الكلي: <span id="kpiTotalRevenue">@price($stats['totalRevenue'])</span></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-head"><span class="kpi-title">العملاء</span><span class="kpi-icon info"><i class="fas fa-users"></i></span></div>
        <div id="kpiTotalCustomers" class="kpi-value">{{ number_format($stats['totalCustomers']) }}</div>
        <div class="kpi-meta">النشطون: <span id="kpiActiveCustomers">{{ number_format($stats['activeCustomers']) }}</span></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-head"><span class="kpi-title">المطاعم</span><span class="kpi-icon warning"><i class="fas fa-store"></i></span></div>
        <div id="kpiTotalRestaurants" class="kpi-value">{{ number_format($stats['totalRestaurants']) }}</div>
        <div class="kpi-meta">فعّال: <span id="kpiActiveRestaurants">{{ number_format($stats['activeRestaurants']) }}</span> | مفتوح: <span id="kpiOpenRestaurants">{{ number_format($stats['openRestaurants']) }}</span></div>
    </div>

    <section class="panel panel-half">
        <h3>حالة الطلبات</h3>
        <div class="progress-stack">
            @foreach(\App\Services\OrderWorkflow::arabicLabels() as $code => $label)
            <div class="progress-row"><span class="progress-label">{{ $label }}</span><div class="progress-track"><div id="progressFill-{{ $code }}" class="progress-fill preparing" style="width: {{ $orderProgress[$code] ?? 0 }}%"></div></div><span id="progressValue-{{ $code }}" class="progress-value">{{ $orderStats[$code] ?? 0 }}</span></div>
            @endforeach
        </div>
    </section>

    <section class="panel panel-third">
        <h3>ملخص التشغيل</h3>
        <div class="summary-list">
            <div class="summary-item"><div class="label">السائقون</div><div id="summaryDrivers" class="value">{{ $stats['activeDrivers'] }} / {{ $stats['totalDrivers'] }}</div></div>
            <div class="summary-item"><div class="label">المدراء</div><div id="summaryAdmins" class="value">{{ $stats['totalAdmins'] }}</div></div>
            <div class="summary-item"><div class="label">قيد تنفيذ المطعم</div><div id="summaryAcceptedOrders" class="value">{{ $summaryAcceptedOrders }}</div></div>
            <div class="summary-item"><div class="label">طلبات اليوم</div><div id="summaryTodayOrders" class="value">{{ $stats['todayOrders'] }}</div></div>
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
                <tbody id="recentOrdersBody">
                    @forelse($recentOrders as $order)
                        @php $statusLabel = \App\Services\OrderWorkflow::label($order->status); @endphp
                        <tr>
                            <td>#{{ $order->order_number ?: $order->id }}</td>
                            <td>{{ $order->restaurant?->name ?? '-' }}</td>
                            <td><span class="badge-soft">{{ $statusLabel }}</span></td>
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

@section('scripts')
<script>
let adminDashboardRealtimeTimer = null;

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatPrice(value) {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value || 0)) + ' ₪';
}

function updateOrderProgress(orderStats, totalOrders) {
    const total = Math.max(Number(totalOrders || 0), 1);
    const statuses = @json(array_keys(\App\Services\OrderWorkflow::arabicLabels()));
    statuses.forEach((status) => {
        const count = Number(orderStats?.[status] || 0);
        const fillEl = document.getElementById(`progressFill-${status}`);
        const valueEl = document.getElementById(`progressValue-${status}`);
        if (fillEl) fillEl.style.width = `${(count / total) * 100}%`;
        if (valueEl) valueEl.textContent = count.toLocaleString();
    });
}

function renderRecentOrders(orders) {
    const body = document.getElementById('recentOrdersBody');
    if (!body) return;
    if (!orders || !orders.length) {
        body.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--text-muted);">لا توجد طلبات حديثة</td></tr>';
        return;
    }
    body.innerHTML = orders.map((order) => `
        <tr>
            <td>#${escapeHtml(order.order_number)}</td>
            <td>${escapeHtml(order.restaurant_name)}</td>
            <td><span class="badge-soft ${escapeHtml(order.status)}">${escapeHtml(order.status_label)}</span></td>
            <td>${formatPrice(order.total_price)}</td>
            <td>${escapeHtml(order.created_at)}</td>
        </tr>
    `).join('');
}

async function fetchAdminDashboardRealtime() {
    try {
        const response = await fetch('{{ route('admin.dashboard.realtime') }}', {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        });
        if (!response.ok) return;
        const payload = await response.json();
        if (!payload?.success) return;
        const data = payload.data || {};
        const stats = data.stats || {};
        const orderStats = data.orderStats || {};

        const setText = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        };

        setText('kpiTotalOrders', Number(stats.totalOrders || 0).toLocaleString());
        setText('kpiTodayOrders', Number(stats.todayOrders || 0).toLocaleString());
        setText('kpiTodayRevenue', formatPrice(stats.todayRevenue));
        setText('kpiTotalRevenue', formatPrice(stats.totalRevenue));
        setText('kpiTotalCustomers', Number(stats.totalCustomers || 0).toLocaleString());
        setText('kpiActiveCustomers', Number(stats.activeCustomers || 0).toLocaleString());
        setText('kpiTotalRestaurants', Number(stats.totalRestaurants || 0).toLocaleString());
        setText('kpiActiveRestaurants', Number(stats.activeRestaurants || 0).toLocaleString());
        setText('kpiOpenRestaurants', Number(stats.openRestaurants || 0).toLocaleString());
        setText('summaryDrivers', `${Number(stats.activeDrivers || 0).toLocaleString()} / ${Number(stats.totalDrivers || 0).toLocaleString()}`);
        setText('summaryAdmins', Number(stats.totalAdmins || 0).toLocaleString());
        const pipelineCount = data.summaryAcceptedOrders != null
            ? Number(data.summaryAcceptedOrders)
            : (Number(orderStats?.payment_verified || 0) + Number(orderStats?.accepted_by_restaurant || 0) + Number(orderStats?.preparing || 0));
        setText('summaryAcceptedOrders', pipelineCount.toLocaleString());
        setText('summaryTodayOrders', Number(stats.todayOrders || 0).toLocaleString());

        updateOrderProgress(orderStats, stats.totalOrders);
        renderRecentOrders(data.recentOrders || []);
    } catch (_) {
        // Ignore one failed refresh tick.
    }
}

adminDashboardRealtimeTimer = setInterval(fetchAdminDashboardRealtime, 5000);
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) fetchAdminDashboardRealtime();
});
</script>
@endsection