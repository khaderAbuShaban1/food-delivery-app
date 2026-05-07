@extends('layouts.restaurant')

@section('styles')
<style>
.dash-grid { display:grid; grid-template-columns:repeat(12,minmax(0,1fr)); gap:1rem; }
.kpi-card { grid-column: span 3; background: var(--bg-card); border:1px solid var(--border-subtle); border-radius: var(--radius-lg); padding:1rem 1.1rem; box-shadow: var(--shadow-sm); }
.kpi-card.clickable { cursor: pointer; transition: all .2s ease; }
.kpi-card.clickable:hover { border-color: var(--accent-primary); box-shadow: var(--shadow-md); transform: translateY(-2px); }
.kpi-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:0.55rem; }
.kpi-title { color: var(--text-secondary); font-size:0.82rem; font-weight:600; }
.kpi-icon { width:36px; height:36px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; }
.kpi-icon.primary { background: rgba(249,115,22,.14); color: var(--accent-primary); }
.kpi-icon.success { background: rgba(34,197,94,.14); color: #16A34A; }
.kpi-icon.info { background: rgba(59,130,246,.14); color: #2563EB; }
.kpi-icon.warning { background: rgba(245,158,11,.14); color: #B45309; }
.kpi-value { font-size:1.45rem; font-weight:800; color:var(--text-primary); line-height:1.2; }
.kpi-meta { font-size:0.78rem; color:var(--text-muted); margin-top:0.2rem; }

.panel { background: var(--bg-card); border:1px solid var(--border-subtle); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding:1rem; }
.panel h3 { font-size:0.95rem; font-weight:700; margin-bottom:0.8rem; color:var(--text-primary); }
.panel-half { grid-column: span 6; }
.panel-third { grid-column: span 4; }
.panel-full { grid-column: span 12; }
.panel-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.85rem; }
.panel-subtitle { color:var(--text-muted); font-size:0.76rem; }

.status-list { display:flex; flex-direction:column; gap:0.65rem; }
.status-row { display:grid; grid-template-columns:110px 1fr 44px; gap:0.5rem; align-items:center; }
.status-label { font-size:0.8rem; font-weight:600; color:var(--text-primary); }
.status-track { height:8px; background:#EEF2F7; border-radius:999px; overflow:hidden; }
.status-fill { height:100%; border-radius:999px; }
.status-fill.pending { background:#F59E0B; }
.status-fill.accepted { background:#2563EB; }
.status-fill.preparing { background:#EA580C; }
.status-fill.delivering { background:#9333EA; }
.status-fill.completed { background:#16A34A; }
.status-fill.cancelled { background:#DC2626; }
.status-count { font-size:0.78rem; color:var(--text-muted); text-align:left; }

.quick-actions { display:grid; gap:0.6rem; }
.quick-link { display:flex; align-items:center; justify-content:center; gap:0.5rem; padding:0.65rem 0.8rem; border-radius:12px; font-size:0.85rem; font-weight:700; text-decoration:none; }
.quick-link.primary { background: linear-gradient(135deg, var(--accent-primary), var(--accent-muted)); color:#fff; }
.quick-link.ghost { border:1px solid var(--border-light); color:var(--text-secondary); background:#fff; }

.summary-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:0.55rem; }
.summary-card { border:1px solid var(--border-subtle); border-radius:12px; padding:0.65rem; background:#fff; }
.summary-label { font-size:0.75rem; color:var(--text-secondary); }
.summary-value { margin-top:0.15rem; font-size:1rem; font-weight:700; color:var(--text-primary); }

.orders-table { width:100%; border-collapse:collapse; }
.orders-table th,.orders-table td { padding:0.6rem 0.45rem; border-bottom:1px solid #EEF2F7; text-align:right; font-size:0.82rem; white-space:nowrap; }
.orders-table th { color:var(--text-muted); font-size:0.75rem; font-weight:700; }
.orders-table tbody tr { transition: background-color .25s ease, transform .25s ease; }

.kpi-card,
.panel,
.summary-card,
.quick-link {
  transition: transform .28s ease, box-shadow .28s ease, border-color .28s ease, background-color .28s ease, color .28s ease;
}

@media (hover: hover) and (pointer: fine) {
  .kpi-card:hover {
    transform: translateY(-3px) scale(1.01);
    box-shadow: var(--shadow-md);
    border-color: var(--border-light);
    background: var(--bg-card-hover);
  }

  .kpi-card.clickable:hover {
    transform: translateY(-3px) scale(1.012);
    box-shadow: var(--shadow-lg);
    border-color: var(--accent-primary);
  }

  .panel:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    border-color: var(--border-light);
    background: var(--bg-card-hover);
  }

  .summary-card:hover {
    transform: translateY(-2px) scale(1.01);
    box-shadow: var(--shadow-sm);
    border-color: rgba(249, 115, 22, .28);
    background: #FFFBF7;
  }

  .quick-link:hover {
    transform: translateY(-2px) scale(1.01);
    box-shadow: var(--shadow-sm);
  }

  .orders-table tbody tr:hover {
    background: #F8FAFC;
  }
}

.status-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1200; align-items:center; justify-content:center; }
.status-modal.show { display:flex; }
.status-modal-card { width:100%; max-width:420px; background:#fff; border-radius:16px; box-shadow:var(--shadow-xl); overflow:hidden; }
.status-modal-header { padding:1rem 1.1rem; border-bottom:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center; }
.status-modal-title { font-size:0.95rem; font-weight:700; color:var(--text-primary); }
.status-modal-close { border:none; background:transparent; color:var(--text-muted); font-size:1.2rem; cursor:pointer; }
.status-modal-body { padding:1rem 1.1rem; color:var(--text-secondary); font-size:0.88rem; }
.status-modal-error { margin-top:0.6rem; font-size:0.8rem; color:#DC2626; display:none; }
.status-modal-footer { padding:0.8rem 1.1rem; border-top:1px solid var(--border-subtle); display:flex; justify-content:flex-end; gap:0.5rem; }
.status-btn { border:none; border-radius:10px; padding:0.5rem 0.9rem; font-size:0.84rem; font-weight:700; cursor:pointer; }
.status-btn.cancel { background:#F3F4F6; color:#4B5563; }
.status-btn.confirm { background:var(--accent-primary); color:#fff; }

@media (max-width: 1200px) {
  .kpi-card { grid-column: span 6; }
  .panel-half,.panel-third { grid-column: span 12; }
}
@media (max-width: 768px) {
  .kpi-card,.panel-full,.panel-half,.panel-third { grid-column: span 12; }
  .summary-grid { grid-template-columns:1fr; }
}
</style>
@endsection

@section('content')
<div class="mb-4">
    <h1 class="page-title">لوحة التحكم</h1>
    <p class="page-subtitle">مرحباً، {{ $restaurant->name ?? 'المطعم' }}</p>
</div>

<div class="dash-grid">
    <div class="kpi-card">
        <div class="kpi-head"><span class="kpi-title">إجمالي الطلبات</span><span class="kpi-icon primary"><i class="bi bi-bag"></i></span></div>
        <div class="kpi-value" id="kpiOrdersTotal">{{ number_format($stats['orders_total']) }}</div>
        <div class="kpi-meta">طلبات اليوم: <span id="kpiOrdersToday">{{ number_format($stats['orders_today']) }}</span></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-head"><span class="kpi-title">بحاجة لقبولكم</span><span class="kpi-icon warning"><i class="bi bi-hourglass-split"></i></span></div>
        <div class="kpi-value" id="kpiPendingOrders">{{ number_format($stats['pending_orders']) }}</div>
        <div class="kpi-meta">متابعة الطلبات الجديدة</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-head"><span class="kpi-title">إيراد اليوم</span><span class="kpi-icon success"><i class="bi bi-cash-coin"></i></span></div>
        <div class="kpi-value" id="kpiRevenueToday">@price($stats['revenue_today'])</div>
        <div class="kpi-meta">الإيراد الكلي: <span id="kpiRevenueTotal">@price($stats['revenue_total'])</span></div>
    </div>
    <div class="kpi-card clickable" id="restaurantStatusCard">
        <div class="kpi-head"><span class="kpi-title">حالة المطعم</span><span class="kpi-icon info"><i class="bi bi-shop"></i></span></div>
        <div class="kpi-value" id="restaurantStatusLabel" style="font-size:1.25rem;">{{ $restaurant && $restaurant->is_open ? 'مفتوح' : 'مغلق' }}</div>
        <div class="kpi-meta" id="restaurantStatusDescription">{{ $restaurant && $restaurant->is_open ? 'يقبل الطلبات حالياً' : 'متوقف عن استقبال الطلبات' }}</div>
    </div>

    <section class="panel panel-half">
        <div class="panel-header">
            <h3><i class="bi bi-graph-up me-2"></i>توزيع حالات الطلب</h3>
            <span class="panel-subtitle">آخر حالة تشغيلية للطلبات</span>
        </div>
        @php
            $totalOrders = max($stats['orders_total'], 1);
            $vis = \App\Services\OrderWorkflow::restaurantVisibleStatuses();
            $lbl = \App\Services\OrderWorkflow::arabicLabels();
        @endphp
        <div class="status-list">
            @foreach($vis as $code)
            <div class="status-row"><span class="status-label">{{ $lbl[$code] ?? $code }}</span><div class="status-track"><div id="statusFill-{{ $code }}" class="status-fill preparing" style="width: {{ (($orderStats[$code] ?? 0) / $totalOrders) * 100 }}%"></div></div><span id="statusCount-{{ $code }}" class="status-count">{{ $orderStats[$code] ?? 0 }}</span></div>
            @endforeach
        </div>
    </section>

    <section class="panel panel-third">
        <div class="panel-header">
            <h3><i class="bi bi-list-check me-2"></i>ملخص القائمة</h3>
            <span class="panel-subtitle">جاهزية الأصناف</span>
        </div>
        <div class="summary-grid">
            <div class="summary-card"><div class="summary-label">عدد الأصناف</div><div id="summaryMenuItemsTotal" class="summary-value">{{ $stats['menu_items_total'] }}</div></div>
            <div class="summary-card"><div class="summary-label">المتاح</div><div id="summaryMenuItemsAvailable" class="summary-value">{{ $stats['menu_items_available'] }}</div></div>
            <div class="summary-card"><div class="summary-label">طلبات اليوم</div><div id="summaryOrdersToday" class="summary-value">{{ $stats['orders_today'] }}</div></div>
            <div class="summary-card"><div class="summary-label">بحاجة لقبولكم</div><div id="summaryPendingOrders" class="summary-value">{{ $stats['pending_orders'] }}</div></div>
        </div>
    </section>

    <section class="panel panel-third">
        <div class="panel-header">
            <h3><i class="bi bi-lightning-charge me-2"></i>إجراءات سريعة</h3>
            <span class="panel-subtitle">إدارة أسرع</span>
        </div>
        <div class="quick-actions">
            <a href="{{ route('restaurant.menu') }}" class="quick-link primary"><i class="bi bi-plus-circle"></i>إضافة صنف جديد</a>
            <a href="{{ route('restaurant.menu') }}" class="quick-link ghost"><i class="bi bi-card-list"></i>إدارة القائمة</a>
            <a href="{{ route('restaurant.orders') }}" class="quick-link ghost"><i class="bi bi-bag"></i>عرض الطلبات</a>
        </div>
    </section>

    <section class="panel panel-full">
        <div class="panel-header">
            <h3><i class="bi bi-clock-history me-2"></i>أحدث الطلبات</h3>
            <span class="panel-subtitle">آخر 6 طلبات</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>رقم الطلب</th>
                        <th>الحالة</th>
                        <th>الإجمالي</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody id="recentOrdersBody">
                    @forelse($recentOrders as $order)
                        @php $statusLabel = \App\Services\OrderWorkflow::label($order->status); @endphp
                        <tr>
                            <td>#{{ $order->order_number ?: $order->id }}</td>
                            <td><span class="badge">{{ $statusLabel }}</span></td>
                            <td>@price($order->total_price)</td>
                            <td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align:center;color:var(--text-muted);">لا توجد طلبات حديثة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="status-modal" id="statusToggleModal" onclick="closeStatusModal(event)">
    <div class="status-modal-card" onclick="event.stopPropagation()">
        <div class="status-modal-header">
            <span class="status-modal-title">تأكيد تغيير الحالة</span>
            <button class="status-modal-close" onclick="closeStatusModal()">&times;</button>
        </div>
        <div class="status-modal-body" id="statusToggleMessage"></div>
        <div class="status-modal-body status-modal-error" id="statusToggleError"></div>
        <div class="status-modal-footer">
            <button class="status-btn cancel" onclick="closeStatusModal()">إلغاء</button>
            <button class="status-btn confirm" id="statusToggleConfirm">تأكيد</button>
        </div>
    </div>
</div>

<script>
const statusCard = document.getElementById('restaurantStatusCard');
const statusLabelEl = document.getElementById('restaurantStatusLabel');
const statusDescEl = document.getElementById('restaurantStatusDescription');
const statusModal = document.getElementById('statusToggleModal');
const statusMsgEl = document.getElementById('statusToggleMessage');
const statusErrEl = document.getElementById('statusToggleError');
const statusConfirmBtn = document.getElementById('statusToggleConfirm');
const sidebarStatusBadge = document.getElementById('sidebarRestaurantStatusBadge');
let isOpen = @json((bool)($restaurant->is_open ?? false));
let dashboardRealtimeTimer = null;

function formatPrice(value) {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value || 0)) + ' ₪';
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function updateStatusBars(orderStats, ordersTotal) {
    const total = Math.max(Number(ordersTotal || 0), 1);
    const statuses = @json(\App\Services\OrderWorkflow::restaurantVisibleStatuses());
    statuses.forEach((status) => {
        const count = Number(orderStats?.[status] || 0);
        const fillEl = document.getElementById(`statusFill-${status}`);
        const countEl = document.getElementById(`statusCount-${status}`);
        if (fillEl) fillEl.style.width = `${(count / total) * 100}%`;
        if (countEl) countEl.textContent = count;
    });
}

function renderRecentOrders(orders) {
    const body = document.getElementById('recentOrdersBody');
    if (!body) return;
    if (!orders || !orders.length) {
        body.innerHTML = '<tr><td colspan="4" style="text-align:center;color:var(--text-muted);">لا توجد طلبات حديثة</td></tr>';
        return;
    }
    body.innerHTML = orders.map((order) => `
        <tr>
            <td>#${escapeHtml(order.order_number)}</td>
            <td><span class="badge">${escapeHtml(order.status_label)}</span></td>
            <td>${formatPrice(order.total_price)}</td>
            <td>${escapeHtml(order.created_at)}</td>
        </tr>
    `).join('');
}

async function fetchDashboardRealtime() {
    try {
        const response = await fetch('{{ route('restaurant.dashboard.realtime') }}', {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        });
        if (!response.ok) return;
        const payload = await response.json();
        if (!payload?.success) return;
        const data = payload.data || {};
        const stats = data.stats || {};
        const orderStats = data.orderStats || {};

        const kpiOrdersTotal = document.getElementById('kpiOrdersTotal');
        const kpiOrdersToday = document.getElementById('kpiOrdersToday');
        const kpiPendingOrders = document.getElementById('kpiPendingOrders');
        const kpiRevenueToday = document.getElementById('kpiRevenueToday');
        const kpiRevenueTotal = document.getElementById('kpiRevenueTotal');
        const summaryMenuItemsTotal = document.getElementById('summaryMenuItemsTotal');
        const summaryMenuItemsAvailable = document.getElementById('summaryMenuItemsAvailable');
        const summaryOrdersToday = document.getElementById('summaryOrdersToday');
        const summaryPendingOrders = document.getElementById('summaryPendingOrders');

        if (kpiOrdersTotal) kpiOrdersTotal.textContent = Number(stats.orders_total || 0).toLocaleString();
        if (kpiOrdersToday) kpiOrdersToday.textContent = Number(stats.orders_today || 0).toLocaleString();
        if (kpiPendingOrders) kpiPendingOrders.textContent = Number(stats.pending_orders || 0).toLocaleString();
        if (kpiRevenueToday) kpiRevenueToday.textContent = formatPrice(stats.revenue_today);
        if (kpiRevenueTotal) kpiRevenueTotal.textContent = formatPrice(stats.revenue_total);
        if (summaryMenuItemsTotal) summaryMenuItemsTotal.textContent = Number(stats.menu_items_total || 0).toLocaleString();
        if (summaryMenuItemsAvailable) summaryMenuItemsAvailable.textContent = Number(stats.menu_items_available || 0).toLocaleString();
        if (summaryOrdersToday) summaryOrdersToday.textContent = Number(stats.orders_today || 0).toLocaleString();
        if (summaryPendingOrders) summaryPendingOrders.textContent = Number(stats.pending_orders || 0).toLocaleString();

        updateStatusBars(orderStats, stats.orders_total);
        renderRecentOrders(data.recentOrders || []);
    } catch (_) {
        // Keep dashboard running even if one poll fails.
    }
}

function openStatusModal() {
    statusErrEl.style.display = 'none';
    statusErrEl.textContent = '';
    statusMsgEl.textContent = isOpen
        ? 'هل أنت متأكد من إغلاق المطعم؟ لن يتم استقبال طلبات جديدة.'
        : 'هل أنت متأكد من فتح المطعم؟ سيتم استقبال الطلبات الجديدة.';
    statusModal.classList.add('show');
}

function closeStatusModal(e) {
    if (e && e.target !== e.currentTarget) return;
    statusModal.classList.remove('show');
}

function applyStatusUI(open) {
    isOpen = open;
    statusLabelEl.textContent = open ? 'مفتوح' : 'مغلق';
    statusDescEl.textContent = open ? 'يقبل الطلبات حالياً' : 'متوقف عن استقبال الطلبات';
    if (sidebarStatusBadge) {
        sidebarStatusBadge.textContent = open ? 'مفتوح' : 'مغلق';
        sidebarStatusBadge.classList.remove('badge-open', 'badge-closed');
        sidebarStatusBadge.classList.add(open ? 'badge-open' : 'badge-closed');
    }
}

async function submitStatusToggle() {
    statusConfirmBtn.disabled = true;
    statusConfirmBtn.textContent = 'جاري التحديث...';
    try {
        const response = await fetch('{{ route('restaurant.dashboard.status', $restaurant->id) }}', {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ is_open: isOpen ? 0 : 1 }),
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'تعذر تحديث الحالة');
        }

        applyStatusUI(!!data.is_open);
        closeStatusModal();
    } catch (error) {
        statusErrEl.textContent = error.message || 'حدث خطأ أثناء تحديث الحالة';
        statusErrEl.style.display = 'block';
    } finally {
        statusConfirmBtn.disabled = false;
        statusConfirmBtn.textContent = 'تأكيد';
    }
}

statusCard.addEventListener('click', openStatusModal);
statusConfirmBtn.addEventListener('click', submitStatusToggle);
dashboardRealtimeTimer = setInterval(fetchDashboardRealtime, 5000);
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) fetchDashboardRealtime();
});
</script>
@endsection