@extends('layouts.restaurant')

@section('styles')
<style>
.orders-toolbar { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.65rem; margin-bottom:1rem; }
.toolbar-card { background:#fff; border:1px solid var(--border-subtle); border-radius:14px; padding:.75rem .9rem; box-shadow:var(--shadow-sm); }
.toolbar-label { font-size:.75rem; color:var(--text-secondary); }
.toolbar-value { margin-top:.15rem; font-size:1.1rem; font-weight:700; color:var(--text-primary); }

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

.badge-status { display:inline-flex; align-items:center; padding:.28rem .62rem; border-radius:999px; font-size:.72rem; font-weight:700; }
.badge-status.pending { background:#FEF3C7; color:#B45309; }
.badge-status.accepted { background:#DBEAFE; color:#1D4ED8; }
.badge-status.preparing { background:#FFEDD5; color:#C2410C; }
.badge-status.delivering { background:#F3E8FF; color:#7E22CE; }
.badge-status.completed { background:#DCFCE7; color:#166534; }
.badge-status.cancelled { background:#FEE2E2; color:#991B1B; }

.actions { display:flex; flex-wrap:wrap; gap:.35rem; }
.action-btn { border:none; border-radius:10px; padding:.42rem .66rem; font-size:.75rem; font-weight:700; cursor:pointer; }
.action-btn.primary { background:var(--accent-primary); color:#fff; }
.action-btn.ghost { border:1px solid var(--border-light); color:var(--text-secondary); background:#fff; }
.action-btn.danger { background:#FEE2E2; color:#B91C1C; }

#statusUpdateModal .modal-content { border-radius:16px; border:none; box-shadow:0 20px 50px rgba(0,0,0,.15); }

@media (max-width: 992px) { .orders-toolbar { grid-template-columns:1fr; } }
</style>
@endsection

@section('content')
<div class="mb-4">
    <h1 class="page-title">الطلبات</h1>
    <p class="page-subtitle">طلبات العملاء والمتابعة</p>
</div>

<div class="orders-toolbar">
    <div class="toolbar-card"><div class="toolbar-label">إجمالي الطلبات</div><div class="toolbar-value">{{ count($myOrders ?? []) }}</div></div>
    <div class="toolbar-card"><div class="toolbar-label">قيد الانتظار</div><div class="toolbar-value">{{ collect($myOrders ?? [])->where('status','pending')->count() }}</div></div>
    <div class="toolbar-card"><div class="toolbar-label">قيد التنفيذ</div><div class="toolbar-value">{{ collect($myOrders ?? [])->whereIn('status',['accepted','preparing','delivering'])->count() }}</div></div>
</div>

@if(count($myOrders ?? []) > 0)
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
            <tbody>
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
                            $statusClass = match($order->status) {
                                'pending' => 'pending',
                                'accepted' => 'accepted',
                                'preparing' => 'preparing',
                                'delivering' => 'delivering',
                                'completed' => 'completed',
                                'cancelled' => 'cancelled',
                                default => 'pending'
                            };
                            $statusText = match($order->status) {
                                'pending' => 'قيد الانتظار',
                                'accepted' => 'مقبول',
                                'preparing' => 'جاري التحضير',
                                'delivering' => 'قيد التوصيل',
                                'completed' => 'مكتمل',
                                'cancelled' => 'ملغي',
                                default => $order->status
                            };
                        @endphp
                        <span class="badge-status {{ $statusClass }}">{{ $statusText }}</span>
                    </td>

                    <td>
                        <div>{{ $order->created_at?->format('Y-m-d') }}</div>
                        <div class="muted">{{ $order->created_at?->format('H:i') }}</div>
                    </td>

                    <td>
                        <div class="actions">
                        @if($order->status === 'pending')
                            <button type="button" class="action-btn primary quick-status-btn" data-url="{{ route('restaurant.orders.status', $order->id) }}" data-status="accepted">قبول</button>
                            <button type="button" class="action-btn danger quick-status-btn" data-url="{{ route('restaurant.orders.status', $order->id) }}" data-status="cancelled">رفض</button>
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
            </tbody>
        </table>
    </div>
</div>
@else
<div class="glass-card p-5 text-center">
    <i class="bi bi-bag-check" style="font-size: 4rem; color: var(--text-muted);"></i>
    <h3 class="mt-4 mb-2" style="color: var(--text-primary);">لا توجد طلبات</h3>
    <p class="mb-0" style="color: var(--text-secondary);">الطلبات ستظهر هنا عند وصولها من العملاء</p>
</div>
@endif

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
                            <option value="pending">قيد الانتظار</option>
                            <option value="accepted">مقبول</option>
                            <option value="preparing">جاري التحضير</option>
                            <option value="delivering">قيد التوصيل</option>
                            <option value="completed">مكتمل</option>
                            <option value="cancelled">ملغي</option>
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
        pending: ['accepted', 'cancelled'],
        accepted: ['preparing', 'cancelled'],
        preparing: ['delivering', 'cancelled'],
        delivering: ['completed', 'cancelled'],
        completed: [],
        cancelled: [],
    };

    const labels = {
        pending: 'قيد الانتظار',
        accepted: 'مقبول',
        preparing: 'جاري التحضير',
        delivering: 'قيد التوصيل',
        completed: 'مكتمل',
        cancelled: 'ملغي',
    };

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
                window.location.reload();
            } catch (e) {
                alert(e.message || 'حدث خطأ');
                this.disabled = false;
            }
        });
    });
});
</script>
@endsection