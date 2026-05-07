@extends('layouts.restaurant')

@section('styles')
<style>
.orders-toolbar { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.65rem; margin-bottom:1rem; }
.toolbar-card { background:#fff; border:1px solid var(--border-subtle); border-radius:14px; padding:.75rem .9rem; box-shadow:var(--shadow-sm); }
.toolbar-label { font-size:.75rem; color:var(--text-secondary); }
.toolbar-value { margin-top:.15rem; font-size:1.1rem; font-weight:700; color:var(--text-primary); }
.orders-filter-bar { background:#F7F8FA; border:1px solid #E6E8EC; border-radius:16px; padding:.75rem; margin-bottom:1rem; }
.orders-filter-grid { display:grid; grid-template-columns:minmax(240px,1fr) minmax(240px,280px) auto; gap:.55rem; align-items:stretch; }
.orders-filter-grid .form-control,
.orders-filter-grid .form-select {
    border-radius:12px;
    border:1px solid #E4E7EC;
    background:#fff;
    font-size:.9rem;
    line-height:1.55;
}
.orders-filter-grid .form-control {
    min-height:46px;
    padding:.65rem .875rem;
}
.orders-filter-grid .form-select {
    min-height:46px;
    /* RTL: text from the right, chevron on the left */
    padding:.6rem .875rem .6rem 2.35rem;
    line-height:1.55;
    overflow: visible;
}
.orders-filter-grid .form-control:focus,
.orders-filter-grid .form-select:focus { border-color:var(--accent-primary); box-shadow:0 0 0 3px rgba(249,115,22,.12); }
.orders-filter-grid .btn {
    min-height:46px;
    padding:.55rem 1rem;
    align-self:stretch;
    border-radius:12px;
}

.orders-card { background:#fff; border:1px solid var(--border-subtle); border-radius:16px; box-shadow:var(--shadow-sm); overflow:hidden; }
.orders-table { width:100%; border-collapse:collapse; }
.orders-table thead th { background:var(--bg-card-hover); color:var(--text-secondary); font-size:.75rem; font-weight:700; padding:.85rem .8rem; text-align:right; border-bottom:1px solid var(--border-subtle); white-space:nowrap; }
.orders-table td { padding:.85rem .8rem; border-bottom:1px solid #EEF2F7; vertical-align:top; font-size:.84rem; }
.orders-table tbody tr { transition: background .2s ease; }
.orders-table tbody tr:hover { background:#FAFBFD; }

.order-id { color:var(--accent-primary); font-weight:700; }
.customer-name { font-weight:600; color:var(--text-primary); }
.muted { color:var(--text-muted); font-size:.75rem; }
.items-list { max-width:260px; display:flex; flex-direction:column; gap:.25rem; color:var(--text-secondary); }
.item-base-price { margin-top:.15rem; line-height:1.35; }
.item-options { margin-top:.25rem; padding-right:.9rem; display:flex; flex-direction:column; gap:.15rem; }
.item-option-line { font-size:.75rem; color:var(--text-muted); line-height:1.35; }
.item-option-group { font-weight:700; }

.badge-status { display:inline-flex; align-items:center; padding:.28rem .62rem; border-radius:999px; font-size:.72rem; font-weight:700; }
.badge-status.pending { background:#FEF3C7; color:#B45309; }
.badge-status.accepted { background:#DBEAFE; color:#1D4ED8; }
.badge-status.preparing { background:#FFEDD5; color:#C2410C; }
.badge-status.delivering { background:#F3E8FF; color:#7E22CE; }
.badge-status.completed { background:#DCFCE7; color:#166534; }
.badge-status.cancelled { background:#FEE2E2; color:#991B1B; }
.badge-status.payment_verified { background:#DBEAFE; color:#1D4ED8; }
.badge-status.accepted_by_restaurant { background:#E0F2FE; color:#0369A1; }
.badge-status.on_the_way { background:#F3E8FF; color:#7E22CE; }
.badge-status.delivered { background:#DCFCE7; color:#166534; }

.actions { display:flex; flex-wrap:wrap; gap:.35rem; }
.action-btn { border:none; border-radius:10px; padding:.42rem .66rem; font-size:.75rem; font-weight:700; cursor:pointer; }
.action-btn.primary { background:var(--accent-primary); color:#fff; }
.action-btn.ghost { border:1px solid var(--border-light); color:var(--text-secondary); background:#fff; }
.action-btn.danger { background:#FEE2E2; color:#B91C1C; }

#statusUpdateModal .modal-content { border-radius:16px; border:none; box-shadow:0 20px 50px rgba(0,0,0,.15); }

@media (max-width: 992px) {
    .orders-toolbar { grid-template-columns:1fr; }
    .orders-filter-grid {
        grid-template-columns:minmax(220px,1fr) minmax(200px,260px) minmax(110px,auto);
        overflow-x:auto;
        padding-bottom:.2rem;
    }
}
</style>
@endsection

@section('content')
<div class="mb-4">
    <h1 class="page-title">الطلبات</h1>
    <p class="page-subtitle">طلبات العملاء والمتابعة</p>
</div>

<div class="orders-toolbar">
    <div class="toolbar-card"><div class="toolbar-label">إجمالي الطلبات</div><div id="summaryTotalOrders" class="toolbar-value">{{ count($myOrders ?? []) }}</div></div>
    <div class="toolbar-card"><div class="toolbar-label">في انتظار قبولكم</div><div id="summaryPendingOrders" class="toolbar-value">{{ collect($myOrders ?? [])->where('status','payment_verified')->count() }}</div></div>
    <div class="toolbar-card"><div class="toolbar-label">قيد التنفيذ</div><div id="summaryInProgressOrders" class="toolbar-value">{{ collect($myOrders ?? [])->whereIn('status',['accepted_by_restaurant','preparing','on_the_way'])->count() }}</div></div>
</div>

<div class="orders-filter-bar">
    <div class="orders-filter-grid">
        <input type="text" id="ordersSearchInput" class="form-control" placeholder="بحث برقم الطلب أو اسم العميل..." value="{{ $filters['search'] ?? '' }}">
        <select id="ordersStatusFilter" class="form-select">
            <option value="" @selected(($filters['status'] ?? '') === '')>كل حالات الطلب</option>
            <option value="payment_verified" @selected(($filters['status'] ?? '') === 'payment_verified')>بانتظار القبول</option>
            <option value="accepted_by_restaurant" @selected(($filters['status'] ?? '') === 'accepted_by_restaurant')>مقبول من المطعم</option>
            <option value="preparing" @selected(($filters['status'] ?? '') === 'preparing')>جاري التحضير</option>
            <option value="on_the_way" @selected(($filters['status'] ?? '') === 'on_the_way')>في الطريق</option>
            <option value="delivered" @selected(($filters['status'] ?? '') === 'delivered')>تم التسليم</option>
        </select>
        <button type="button" class="btn btn-outline" id="ordersResetFiltersBtn">إعادة تعيين</button>
    </div>
</div>

<div class="orders-card">
    <div class="table-responsive">
        <table class="orders-table">
            <thead>
                <tr>
                    <th>رقم الطلب / العميل</th>
                    <th>الأصناف</th>
                    <th>المجموع</th>
                    <th>الحالة</th>
                    <th>وقت الطلب</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody id="ordersTableBody">
                @if(count($myOrders ?? []) > 0)
                @foreach($myOrders as $order)
                <tr>
                    <td>
                        <div class="order-id">#{{ $order->order_number ?: $order->id }}</div>
                        <div class="customer-name">{{ $order->customerUser?->name ?? $order->legacyUser?->name ?? 'عميل غير محدد' }}</div>
                    </td>

                    <td>
                        @if($order->orderItems->count() > 0)
                            <div class="items-list">
                                @foreach($order->orderItems as $item)
                                    <div>
                                        {{ $item->quantity ?? 1 }} × {{ $item->name ?? $item->menuItem->name ?? 'صنف' }}

                                        <div class="muted item-base-price">
                                            السعر الأساسي:
                                            <span dir="ltr">{{ number_format((float)($item->price ?? 0), 2) }} ₪</span>
                                        </div>

                                        @php
                                            $opts = $item->optionValues ?? collect();
                                            $optionsGroups = [];

                                            if ($opts->count() > 0) {
                                                $grouped = $opts->groupBy(fn ($o) => trim((string) ($o->group_name ?? '')));
                                                foreach ($grouped as $g => $rows) {
                                                    $g = trim((string) $g);
                                                    if ($g === '') continue;

                                                    $values = [];
                                                    $seen = [];
                                                    foreach ($rows as $row) {
                                                        $valueName = trim((string) ($row->value_name ?? ''));
                                                        if ($valueName === '' || isset($seen[$valueName])) continue;
                                                        $seen[$valueName] = true;

                                                        $values[] = [
                                                            'name' => $valueName,
                                                            'extra_price' => (float) ($row->extra_price ?? 0),
                                                        ];
                                                    }

                                                    if (!empty($values)) {
                                                        $optionsGroups[] = [
                                                            'group_name' => $g,
                                                            'values' => $values,
                                                        ];
                                                    }
                                                }
                                            } else {
                                                // Fallback: some older schemas may store options as JSON on the order_items row.
                                                $rawOptions = $item->options ?? null;
                                                $decoded = null;
                                                if (is_string($rawOptions)) {
                                                    $decoded = json_decode($rawOptions, true);
                                                } elseif (is_array($rawOptions)) {
                                                    $decoded = $rawOptions;
                                                }

                                                if (is_array($decoded) && !empty($decoded)) {
                                                    $first = $decoded[0] ?? null;
                                                    $isRowShape = is_array($first)
                                                        && (array_key_exists('value_name', $first) || array_key_exists('value', $first) || array_key_exists('name', $first));

                                                    if ($isRowShape) {
                                                        $grouped = collect($decoded)->groupBy(fn ($r) => trim((string) ($r['group_name'] ?? '')));
                                                        foreach ($grouped as $g => $rowsGroup) {
                                                            $g = trim((string) $g);
                                                            if ($g === '') continue;

                                                            $values = [];
                                                            $seen = [];
                                                            foreach ($rowsGroup as $r) {
                                                                if (!is_array($r)) continue;
                                                                $valueName = trim((string) ($r['value_name'] ?? $r['name'] ?? $r['value'] ?? ''));
                                                                if ($valueName === '' || isset($seen[$valueName])) continue;
                                                                $seen[$valueName] = true;

                                                                $values[] = [
                                                                    'name' => $valueName,
                                                                    'extra_price' => (float) ($r['extra_price'] ?? 0),
                                                                ];
                                                            }

                                                            if (!empty($values)) {
                                                                $optionsGroups[] = [
                                                                    'group_name' => $g,
                                                                    'values' => $values,
                                                                ];
                                                            }
                                                        }
                                                    } else {
                                                        // Group shape: [ { group_name: 'Size', values: [ { name: 'Large', extra_price: 3 } ] } ]
                                                        foreach ($decoded as $groupObj) {
                                                            if (!is_array($groupObj)) continue;
                                                            $g = trim((string) ($groupObj['group_name'] ?? $groupObj['name'] ?? ''));
                                                            if ($g === '') continue;

                                                            $groupValues = $groupObj['values'] ?? [];
                                                            if (!is_array($groupValues)) continue;

                                                            $values = [];
                                                            $seen = [];
                                                            foreach ($groupValues as $v) {
                                                                if (!is_array($v)) continue;
                                                                $valueName = trim((string) ($v['value_name'] ?? $v['name'] ?? $v['value'] ?? ''));
                                                                if ($valueName === '' || isset($seen[$valueName])) continue;
                                                                $seen[$valueName] = true;

                                                                $values[] = [
                                                                    'name' => $valueName,
                                                                    'extra_price' => (float) ($v['extra_price'] ?? 0),
                                                                ];
                                                            }

                                                            if (!empty($values)) {
                                                                $optionsGroups[] = [
                                                                    'group_name' => $g,
                                                                    'values' => $values,
                                                                ];
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        @endphp

                                        @if(count($optionsGroups) > 0)
                                            <div class="item-options">
                                                @foreach($optionsGroups as $group)
                                                    <div class="item-option-line">
                                                        - {{ $group['group_name'] }}:
                                                        @foreach($group['values'] as $val)
                                                            <span>{{ $val['name'] }}</span>
                                                            @if((float) $val['extra_price'] > 0)
                                                                <span dir="ltr"> (+{{ number_format((float) $val['extra_price'], 2) }} ₪)</span>
                                                            @endif
                                                            @if(!$loop->last)، @endif
                                                        @endforeach
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span class="muted">لا توجد أصناف</span>
                        @endif
                    </td>

                    <td>
                        <span style="font-weight:700;">@price($order->total_price)</span>
                    </td>

                    <td>
                        @php
                            $wf = [
                                'payment_verified' => ['payment_verified', 'تم التحقق — قبولكم'],
                                'accepted_by_restaurant' => ['accepted_by_restaurant', 'مقبول منكم'],
                                'preparing' => ['preparing', 'جاري التحضير'],
                                'on_the_way' => ['on_the_way', 'في الطريق'],
                                'delivered' => ['delivered', 'تم التسليم'],
                            ];
                            $statusRow = $wf[$order->status] ?? [$order->status, $order->status];
                            $statusClass = $statusRow[0];
                            $statusText = $statusRow[1];
                        @endphp
                        <span class="badge-status {{ $statusClass }}">{{ $statusText }}</span>
                    </td>

                    <td>
                        <div>{{ $order->created_at?->format('Y-m-d') }}</div>
                        <div class="muted">{{ $order->created_at?->format('H:i') }}</div>
                    </td>

                    <td>
                        <div class="actions">
                        @if($order->status === 'payment_verified')
                            <button type="button" class="action-btn primary quick-status-btn" data-url="{{ route('restaurant.orders.status', $order->id) }}" data-status="accepted_by_restaurant">قبول الطلب</button>
                        @elseif($order->status === 'accepted_by_restaurant')
                            <button type="button" class="action-btn primary quick-status-btn" data-url="{{ route('restaurant.orders.status', $order->id) }}" data-status="preparing">بدء التحضير</button>
                        @endif
                        <button class="action-btn ghost update-status-btn"
                            data-order-id="{{ $order->id }}"
                            data-current-status="{{ $order->status }}"
                            data-url="{{ route('restaurant.orders.status', $order->id) }}">
                            تحديث
                        </button>
                        </div>
                    </td>
                </tr>
                @endforeach
                @else
                <tr><td colspan="6" style="text-align:center;color:var(--text-muted);">لا توجد طلبات</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- Single Reusable Status Update Modal -->
<div class="modal fade" id="statusUpdateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="statusForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-arrow-repeat me-2"></i>تحديث حالة الطلب</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">طلب رقم: <strong id="modalOrderId">#</strong></p>
                    <div class="mb-3">
                        <label class="form-label">الحالة الجديدة</label>
                        <select name="status" id="statusSelect" class="form-select" required>
                            <option value="payment_verified">تم التحقق من الدفع</option>
                            <option value="accepted_by_restaurant">مقبول من المطعم</option>
                            <option value="preparing">جاري التحضير</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-orange">
                        <i class="bi bi-check-lg me-1"></i>حفظ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusModal = document.getElementById('statusUpdateModal');
    const statusForm = document.getElementById('statusForm');
    const statusSelect = document.getElementById('statusSelect');
    const modalOrderId = document.getElementById('modalOrderId');
    const transitions = {
        payment_verified: ['accepted_by_restaurant'],
        accepted_by_restaurant: ['preparing'],
        preparing: [],
        on_the_way: [],
        delivered: [],
    };

    const labels = {
        payment_verified: 'تم التحقق من الدفع',
        accepted_by_restaurant: 'مقبول من المطعم',
        preparing: 'جاري التحضير',
        on_the_way: 'في الطريق',
        delivered: 'تم التسليم',
    };

    let ordersRealtimeTimer = null;
    const ordersSearchInput = document.getElementById('ordersSearchInput');
    const ordersStatusFilter = document.getElementById('ordersStatusFilter');
    const ordersResetFiltersBtn = document.getElementById('ordersResetFiltersBtn');

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatAmount(value) {
        return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value || 0));
    }

    function formatPrice(value) {
        return formatAmount(value) + ' ₪';
    }

    function formatExtraPrice(extraPrice) {
        const n = Number(extraPrice || 0);
        return n > 0 ? ` (+${formatAmount(n)} ₪)` : '';
    }

    function renderItemOptions(options) {
        if (!Array.isArray(options) || options.length === 0) return '';

        const optionLines = options.map((opt) => {
            const groupName = String(opt?.group_name ?? '').trim();
            const vals = Array.isArray(opt?.values) ? opt.values : [];
            if (vals.length === 0) return '';

            const valuesText = vals.map((v) => {
                const name = escapeHtml(v?.name ?? '');
                const extra = v?.extra_price ?? 0;
                return `${name}${formatExtraPrice(extra)}`;
            }).join('، ');

            if (!valuesText) return '';
            return `<div class="item-option-line">- ${escapeHtml(groupName)}: ${valuesText}</div>`;
        }).filter(Boolean).join('');

        return optionLines ? `<div class="item-options">${optionLines}</div>` : '';
    }

    function normalizeStatusClass(status) {
        return String(status || '').trim() || 'payment_verified';
    }

    function renderOrdersRows(orders) {
        const tbody = document.getElementById('ordersTableBody');
        if (!tbody) return;
        if (!orders || !orders.length) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--text-muted);">لا توجد طلبات</td></tr>';
            return;
        }
        tbody.innerHTML = orders.map((order) => {
            const statusClass = normalizeStatusClass(order.status);
            const itemsHtml = (order.items || []).length
                ? `<div class="items-list">${order.items.map((item) => {
                    const qty = escapeHtml(item.quantity);
                    const name = escapeHtml(item.name);
                    const basePriceLine = (item.base_price !== undefined && item.base_price !== null)
                        ? `<div class="muted item-base-price">السعر الأساسي: <span dir="ltr">${formatPrice(item.base_price)}</span></div>`
                        : '';
                    const optionsHtml = renderItemOptions(item.options);
                    return `<div>${qty} × ${name}${basePriceLine}${optionsHtml}</div>`;
                }).join('')}</div>`
                : '<span class="muted">لا توجد أصناف</span>';
            const pendingActions = order.status === 'payment_verified'
                ? `<button type="button" class="action-btn primary quick-status-btn" data-url="${escapeHtml(order.status_update_url)}" data-status="accepted_by_restaurant">قبول الطلب</button>`
                : order.status === 'accepted_by_restaurant'
                ? `<button type="button" class="action-btn primary quick-status-btn" data-url="${escapeHtml(order.status_update_url)}" data-status="preparing">بدء التحضير</button>`
                : '';
            return `
                <tr>
                    <td>
                        <div class="order-id">#${escapeHtml(order.order_number)}</div>
                        <div class="customer-name">${escapeHtml(order.customer_name)}</div>
                    </td>
                    <td>${itemsHtml}</td>
                    <td><span style="font-weight:700;">${formatPrice(order.total_price)}</span></td>
                    <td><span class="badge-status ${statusClass}">${escapeHtml(order.status_label || labels[order.status] || order.status)}</span></td>
                    <td>
                        <div>${escapeHtml(order.created_date || '')}</div>
                        <div class="muted">${escapeHtml(order.created_time || '')}</div>
                    </td>
                    <td>
                        <div class="actions">
                            ${pendingActions}
                            <button class="action-btn ghost update-status-btn"
                                data-order-id="${escapeHtml(order.id)}"
                                data-current-status="${escapeHtml(order.status)}"
                                data-url="${escapeHtml(order.status_update_url)}">
                                تحديث
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
        bindOrderActionButtons();
    }

    function updateSummary(summary) {
        const totalEl = document.getElementById('summaryTotalOrders');
        const pendingEl = document.getElementById('summaryPendingOrders');
        const progressEl = document.getElementById('summaryInProgressOrders');
        if (totalEl) totalEl.textContent = Number(summary?.total || 0).toLocaleString();
        if (pendingEl) pendingEl.textContent = Number(summary?.awaiting_accept || 0).toLocaleString();
        if (progressEl) progressEl.textContent = Number(summary?.in_progress || 0).toLocaleString();
    }

    async function fetchOrdersRealtime() {
        try {
            const params = new URLSearchParams();
            if (ordersSearchInput && ordersSearchInput.value.trim()) {
                params.set('search', ordersSearchInput.value.trim());
            }
            if (ordersStatusFilter && ordersStatusFilter.value) {
                params.set('status', ordersStatusFilter.value);
            }

            const realtimeUrl = '{{ route('restaurant.orders.realtime') }}' + (params.toString() ? `?${params.toString()}` : '');
            const response = await fetch(realtimeUrl, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) return;
            const payload = await response.json();
            if (!payload?.success) return;
            const data = payload.data || {};
            renderOrdersRows(data.orders || []);
            updateSummary(data.summary || {});
        } catch (_) {
            // Keep page functional if one realtime pull fails.
        }
    }

    function bindOrderActionButtons() {
        document.querySelectorAll('.update-status-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const orderId = this.dataset.orderId;
                const currentStatus = this.dataset.currentStatus;
                const url = this.dataset.url;

                statusForm.action = url;
                modalOrderId.textContent = '#' + orderId;
                statusSelect.innerHTML = '';
                const allowed = transitions[currentStatus] || [];
                if (!allowed.length) {
                    const option = document.createElement('option');
                    option.value = currentStatus;
                    option.textContent = labels[currentStatus] || currentStatus;
                    statusSelect.appendChild(option);
                } else {
                    allowed.forEach((statusKey) => {
                        const option = document.createElement('option');
                        option.value = statusKey;
                        option.textContent = labels[statusKey] || statusKey;
                        statusSelect.appendChild(option);
                    });
                }

                const modal = new bootstrap.Modal(statusModal);
                modal.show();
            });
        });

        document.querySelectorAll('.quick-status-btn').forEach(function(btn) {
            btn.addEventListener('click', async function() {
                this.disabled = true;
                try {
                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('_method', 'PUT');
                    formData.append('status', this.dataset.status);
                    const res = await fetch(this.dataset.url, { method: 'POST', body: formData });
                    if (!res.ok) throw new Error('تعذر تحديث حالة الطلب');
                    await fetchOrdersRealtime();
                } catch (e) {
                    alert(e.message || 'حدث خطأ');
                } finally {
                    this.disabled = false;
                }
            });
        });
    }

    bindOrderActionButtons();
    let searchDebounce = null;
    if (ordersSearchInput) {
        ordersSearchInput.addEventListener('input', function() {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(fetchOrdersRealtime, 300);
        });
    }
    if (ordersStatusFilter) {
        ordersStatusFilter.addEventListener('change', fetchOrdersRealtime);
    }
    if (ordersResetFiltersBtn) {
        ordersResetFiltersBtn.addEventListener('click', function() {
            if (ordersSearchInput) ordersSearchInput.value = '';
            if (ordersStatusFilter) ordersStatusFilter.value = '';
            fetchOrdersRealtime();
        });
    }
    ordersRealtimeTimer = setInterval(fetchOrdersRealtime, 5000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) fetchOrdersRealtime();
    });

    statusForm.addEventListener('submit', async function(event) {
        event.preventDefault();
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        try {
            const formData = new FormData(statusForm);
            const response = await fetch(statusForm.action, {
                method: 'POST',
                body: formData,
            });
            if (!response.ok) throw new Error('تعذر تحديث حالة الطلب');
            const modal = bootstrap.Modal.getInstance(statusModal);
            if (modal) modal.hide();
            await fetchOrdersRealtime();
        } catch (error) {
            alert(error.message || 'حدث خطأ');
        } finally {
            submitButton.disabled = false;
        }
    });
});
</script>
@endsection