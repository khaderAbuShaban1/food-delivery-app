@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h1 class="page-title">الطلبات</h1>
    <p class="page-subtitle">طلبات العملاء والمتابعة</p>
</div>

@if(count($myOrders ?? []) > 0)
<div class="glass-card" style="overflow: hidden;">
    <div class="table-responsive">
        <table class="table table-borderless mb-0">
            <thead>
                <tr style="background: var(--bg-card-hover);">
                    <th class="py-3 px-4" style="color: var(--text-secondary); font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">رقم الطلب</th>
                    <th class="py-3 px-4" style="color: var(--text-secondary); font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">الأصناف</th>
                    <th class="py-3 px-4" style="color: var(--text-secondary); font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">المجموع</th>
                    <th class="py-3 px-4" style="color: var(--text-secondary); font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">الحالة</th>
                    <th class="py-3 px-4" style="color: var(--text-secondary); font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">التاريخ</th>
                    <th class="py-3 px-4" style="color: var(--text-secondary); font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @foreach($myOrders as $order)
                <tr class="border-bottom" style="border-color: var(--border-subtle) !important;">
                    <!-- Order ID -->
                    <td class="py-3 px-4">
                        <span class="fw-bold" style="color: var(--accent-primary);">#{{ $order['id'] }}</span>
                    </td>
                    
                    <!-- Items -->
                    <td class="py-3 px-4">
                        @if(isset($order['order_items']) && count($order['order_items']) > 0)
                            <div style="max-width: 200px;">
                                @foreach($order['order_items'] as $item)
                                    <div class="mb-1" style="color: var(--text-secondary); font-size: 0.85rem;">
                                        {{ $item['quantity'] ?? 1 }} × {{ $item['menu_item']['name'] ?? 'صنف' }}
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span style="color: var(--text-muted); font-size: 0.85rem;">لا توجد أصناف</span>
                        @endif
                    </td>
                    
                    <!-- Total -->
                    <td class="py-3 px-4">
                        <span class="fw-bold" style="color: var(--text-primary); font-size: 1rem;">
                            ${{ number_format($order['total_price'], 2) }}
                        </span>
                    </td>
                    
                    <!-- Status -->
                    <td class="py-3 px-4">
                        @php
                            $statusClass = match($order['status']) {
                                'pending' => 'badge-pending',
                                'accepted' => 'badge-accepted',
                                'preparing' => 'badge-preparing',
                                'delivering' => 'badge-delivering',
                                'completed' => 'badge-completed',
                                default => 'badge-pending'
                            };
                            $statusText = match($order['status']) {
                                'pending' => 'قيد الانتظار',
                                'accepted' => 'تم القبول',
                                'preparing' => 'جاري التحضير',
                                'delivering' => 'قيد التوصيل',
                                'completed' => 'مكتمل',
                                default => $order['status']
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                    </td>
                    
                    <!-- Date -->
                    <td class="py-3 px-4">
                        <span style="color: var(--text-secondary); font-size: 0.85rem;">
                            {{ $order['created_at'] ?? 'N/A' }}
                        </span>
                    </td>
                    
                    <!-- Actions -->
                    <td class="py-3 px-4">
                        <button class="btn btn-sm btn-outline" data-bs-toggle="modal" data-bs-target="#statusModal{{ $order['id'] }}">
                            <i class="bi bi-pencil me-1"></i>تحديث
                        </button>
                    </td>
                </tr>

                <!-- Status Update Modal -->
                <div class="modal fade" id="statusModal{{ $order['id'] }}" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form action="/orders/{{ $order['id'] }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title">تحديث حالة الطلب #{{ $order['id'] }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">الحالة الجديدة</label>
                                        <select name="status" class="form-select" required>
                                            <option value="pending" {{ $order['status'] == 'pending' ? 'selected' : '' }}>قيد الانتظار</option>
                                            <option value="accepted" {{ $order['status'] == 'accepted' ? 'selected' : '' }}>تم القبول</option>
                                            <option value="preparing" {{ $order['status'] == 'preparing' ? 'selected' : '' }}>جاري التحضير</option>
                                            <option value="delivering" {{ $order['status'] == 'delivering' ? 'selected' : '' }}>قيد التوصيل</option>
                                            <option value="completed" {{ $order['status'] == 'completed' ? 'selected' : '' }}>مكتمل</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-orange w-100">حفظ</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<!-- Empty State -->
<div class="glass-card p-5 text-center">
    <i class="bi bi-bag-check" style="font-size: 4rem; color: var(--text-muted);"></i>
    <h3 class="mt-4 mb-2" style="color: var(--text-primary);">لا توجد طلبات</h3>
    <p class="mb-0" style="color: var(--text-secondary);">الطلبات ستظهر هنا عند وصولها من العملاء</p>
</div>
@endif

<style>
.table {
    margin-bottom: 0;
    width: 100%;
    table-layout: fixed;
}

.table thead th {
    border-bottom: 1px solid var(--border-subtle);
    background: transparent;
    white-space: nowrap;
}

.table td {
    vertical-align: middle;
    word-wrap: break-word;
}

.table tbody tr {
    transition: background 0.2s;
}

.table tbody tr:nth-child(even) {
    background: var(--bg-card-hover);
}

.table tbody tr:hover {
    background: var(--bg-card-hover);
}

.badge {
    padding: 0.4rem 0.75rem;
    font-weight: 500;
    font-size: 0.75rem;
    white-space: nowrap;
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--border-light);
    color: var(--text-secondary);
    padding: 0.4rem 0.75rem;
    font-size: 0.85rem;
    white-space: nowrap;
}

.btn-outline:hover {
    background: var(--bg-card-hover);
    border-color: var(--accent-primary);
    color: var(--accent-primary);
}

.border-bottom {
    border-color: var(--border-subtle) !important;
}

.modal-backdrop {
    background-color: rgba(0, 0, 0, 0.5);
}
</style>
@endsection