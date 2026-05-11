@extends('layouts.restaurant')

@section('styles')
<style>
.details-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem; }
.panel { background:#fff; border:1px solid var(--border-subtle); border-radius:16px; box-shadow:var(--shadow-sm); padding:1rem; }
.panel h5 { font-size:1rem; margin-bottom:.75rem; font-weight:700; }
.row-item { display:flex; justify-content:space-between; gap:.8rem; padding:.55rem 0; border-bottom:1px dashed #E6EAF0; }
.row-item:last-child { border-bottom:none; }
.label { color:var(--text-secondary); font-size:.84rem; }
.value { color:var(--text-primary); font-weight:600; text-align:left; }
.badge-invoice { display:inline-flex; align-items:center; padding:.28rem .62rem; border-radius:999px; font-size:.72rem; font-weight:700; }
.badge-invoice.pending { background:#FEF3C7; color:#B45309; }
.badge-invoice.paid { background:#DCFCE7; color:#166534; }
.badge-invoice.unpaid { background:#FEE2E2; color:#991B1B; }

.orders-card { background:#fff; border:1px solid var(--border-subtle); border-radius:16px; box-shadow:var(--shadow-sm); overflow:hidden; }
.orders-table { width:100%; border-collapse:collapse; }
.orders-table th { background:var(--bg-card-hover); color:var(--text-secondary); font-size:.75rem; font-weight:700; padding:.8rem; text-align:right; border-bottom:1px solid var(--border-subtle); }
.orders-table td { padding:.8rem; border-bottom:1px solid #EEF2F7; font-size:.84rem; }
@media (max-width: 900px) { .details-grid { grid-template-columns:1fr; } }
</style>
@endsection

@section('content')
<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">تفاصيل الفاتورة {{ $invoice->invoice_number }}</h1>
        <p class="page-subtitle">عرض معلومات الفاتورة والطلبات المضمنة والملخص المالي.</p>
    </div>
    <a href="{{ route('restaurant.invoices') }}" class="btn btn-outline">رجوع للفواتير</a>
</div>

<div class="details-grid">
    <div class="panel">
        <h5>معلومات الفاتورة</h5>
        <div class="row-item"><span class="label">رقم الفاتورة</span><span class="value">{{ $invoice->invoice_number }}</span></div>
        <div class="row-item"><span class="label">الفترة</span><span class="value">{{ $invoice->start_date?->format('Y-m-d') }} - {{ $invoice->end_date?->format('Y-m-d') }}</span></div>
        <div class="row-item"><span class="label">تاريخ الإنشاء</span><span class="value">{{ optional($invoice->created_at)->format('Y-m-d H:i') }}</span></div>
        <div class="row-item">
            <span class="label">الحالة</span>
            <span class="value">
                <span class="badge-invoice {{ $invoice->status }}">
                    {{ $invoice->status === 'pending' ? 'قيد الانتظار' : ($invoice->status === 'paid' ? 'مدفوع' : 'غير مدفوع') }}
                </span>
            </span>
        </div>
        <div class="row-item"><span class="label">ملاحظات</span><span class="value">{{ $invoice->notes ?: '-' }}</span></div>
    </div>

    <div class="panel">
        <h5>معلومات المطعم</h5>
        <div class="row-item"><span class="label">الاسم</span><span class="value">{{ $invoice->restaurant?->name ?? '-' }}</span></div>
        <div class="row-item"><span class="label">البريد</span><span class="value">{{ $invoice->restaurant?->email ?? '-' }}</span></div>
        <div class="row-item"><span class="label">الهاتف</span><span class="value">{{ $invoice->restaurant?->phone ?? '-' }}</span></div>
    </div>
</div>

<div class="panel mb-3">
    <h5>الملخص المالي</h5>
    <div class="row-item"><span class="label">إجمالي المبيعات</span><span class="value">{{ number_format($invoice->total_sales, 2) }} ₪</span></div>
    <div class="row-item"><span class="label">عمولة المنصة ({{ number_format($invoice->commission_percentage, 2) }}%)</span><span class="value">{{ number_format($invoice->commission_amount, 2) }} ₪</span></div>
    <div class="row-item"><span class="label">صافي المبلغ للمطعم</span><span class="value">{{ number_format($invoice->final_amount, 2) }} ₪</span></div>
    <div class="row-item"><span class="label">عدد الطلبات</span><span class="value">{{ number_format($invoice->total_orders) }}</span></div>
</div>

@if($invoice->payment_proof_image || $invoice->payment_proof)
<div class="panel mb-3">
    <h5>إثبات دفع الفاتورة</h5>
    <a href="{{ asset('storage/' . ($invoice->payment_proof_image ?: $invoice->payment_proof)) }}" target="_blank" rel="noopener">
        <img src="{{ asset('storage/' . ($invoice->payment_proof_image ?: $invoice->payment_proof)) }}" alt="إثبات دفع الفاتورة" style="max-height:240px;border-radius:12px;border:1px solid var(--border-subtle);">
    </a>
    <div class="mt-2 text-muted">Paid At: {{ $invoice->payment_paid_at?->format('Y-m-d') ?: '-' }}</div>
</div>
@endif

<div class="orders-card">
    <div class="table-responsive">
        <table class="orders-table">
            <thead>
                <tr>
                    <th>رقم الطلب</th>
                    <th>التاريخ</th>
                    <th>الإجمالي</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->orders as $order)
                <tr>
                    <td>{{ $order->order_number ?: ('#' . $order->id) }}</td>
                    <td>{{ optional($order->created_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ number_format((float) $order->total_price, 2) }} ₪</td>
                    <td>{{ \App\Services\OrderWorkflow::label($order->status) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align:center;color:var(--text-muted);padding:1.2rem;">لا توجد طلبات ضمن هذه الفاتورة.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
