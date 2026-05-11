@extends('layouts.restaurant')

@section('styles')
<style>
.invoices-summary { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.75rem; margin-bottom:1rem; }
.summary-card { background:#fff; border:1px solid var(--border-subtle); border-radius:14px; padding:.8rem 1rem; box-shadow:var(--shadow-sm); }
.summary-label { font-size:.78rem; color:var(--text-secondary); }
.summary-value { margin-top:.2rem; font-size:1.2rem; font-weight:700; color:var(--text-primary); }

.filter-card { background:#F7F8FA; border:1px solid #E6E8EC; border-radius:16px; padding:.8rem; margin-bottom:1rem; }
.filter-grid { display:grid; grid-template-columns: minmax(180px,1fr) minmax(160px,1fr) minmax(160px,1fr) auto auto; gap:.55rem; }
.filter-grid .form-control, .filter-grid .form-select { border-radius:12px; min-height:44px; background:#fff; border:1px solid #E4E7EC; }
.filter-grid .form-control:focus, .filter-grid .form-select:focus { border-color:var(--accent-primary); box-shadow:0 0 0 3px rgba(249,115,22,.12); }

.invoices-card { background:#fff; border:1px solid var(--border-subtle); border-radius:16px; box-shadow:var(--shadow-sm); overflow:hidden; }
.invoices-table { width:100%; border-collapse:collapse; }
.invoices-table th { background:var(--bg-card-hover); color:var(--text-secondary); font-size:.75rem; font-weight:700; padding:.8rem; text-align:right; border-bottom:1px solid var(--border-subtle); white-space:nowrap; }
.invoices-table td { font-size:.84rem; padding:.8rem; border-bottom:1px solid #EEF2F7; vertical-align:top; }

.badge-invoice { display:inline-flex; align-items:center; padding:.28rem .62rem; border-radius:999px; font-size:.72rem; font-weight:700; }
.badge-invoice.pending { background:#FEF3C7; color:#B45309; }
.badge-invoice.paid { background:#DCFCE7; color:#166534; }
.badge-invoice.unpaid { background:#FEE2E2; color:#991B1B; }

@media (max-width: 992px) {
    .invoices-summary { grid-template-columns:1fr 1fr; }
    .filter-grid { grid-template-columns:1fr 1fr; }
}
@media (max-width: 600px) {
    .invoices-summary { grid-template-columns:1fr; }
    .filter-grid { grid-template-columns:1fr; }
}
</style>
@endsection

@section('content')
<div class="mb-4">
    <h1 class="page-title">الفواتير</h1>
    <p class="page-subtitle">متابعة المبيعات والعمولة وصافي المبلغ وحالة الدفع.</p>
</div>

<div class="invoices-summary">
    <div class="summary-card">
        <div class="summary-label">عدد الفواتير</div>
        <div class="summary-value">{{ number_format($summary['total_invoices']) }}</div>
    </div>
    <div class="summary-card">
        <div class="summary-label">إجمالي المبيعات</div>
        <div class="summary-value">{{ number_format($summary['total_sales'], 2) }} ₪</div>
    </div>
    <div class="summary-card">
        <div class="summary-label">عمولة المنصة</div>
        <div class="summary-value">{{ number_format($summary['total_commission'], 2) }} ₪</div>
    </div>
    <div class="summary-card">
        <div class="summary-label">صافي مستحقاتك</div>
        <div class="summary-value">{{ number_format($summary['total_final_amount'], 2) }} ₪</div>
    </div>
</div>

<div class="filter-card">
    <form method="GET" action="{{ route('restaurant.invoices') }}" class="filter-grid">
        <select name="status" class="form-select">
            <option value="" @selected(($filters['status'] ?? '') === '')>كل الحالات</option>
            <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>قيد الانتظار</option>
            <option value="paid" @selected(($filters['status'] ?? '') === 'paid')>مدفوع</option>
            <option value="unpaid" @selected(($filters['status'] ?? '') === 'unpaid')>غير مدفوع</option>
        </select>
        <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
        <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
        <button class="btn btn-orange" type="submit">تصفية</button>
        <a href="{{ route('restaurant.invoices') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>
</div>

<div class="invoices-card">
    <div class="table-responsive">
        <table class="invoices-table">
            <thead>
                <tr>
                    <th>رقم الفاتورة</th>
                    <th>الفترة</th>
                    <th>عدد الطلبات</th>
                    <th>إجمالي المبيعات</th>
                    <th>العمولة</th>
                    <th>الصافي</th>
                    <th>الحالة</th>
                    <th>تاريخ الإنشاء</th>
                    <th>التفاصيل</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                <tr>
                    <td><strong>{{ $invoice->invoice_number }}</strong></td>
                    <td>{{ $invoice->start_date?->format('Y-m-d') }} - {{ $invoice->end_date?->format('Y-m-d') }}</td>
                    <td>{{ number_format($invoice->total_orders) }}</td>
                    <td>{{ number_format($invoice->total_sales, 2) }} ₪</td>
                    <td>{{ number_format($invoice->commission_amount, 2) }} ₪ ({{ number_format($invoice->commission_percentage, 2) }}%)</td>
                    <td><strong>{{ number_format($invoice->final_amount, 2) }} ₪</strong></td>
                    <td>
                        <span class="badge-invoice {{ $invoice->status }}">
                            {{ $invoice->status === 'pending' ? 'قيد الانتظار' : ($invoice->status === 'paid' ? 'مدفوع' : 'غير مدفوع') }}
                        </span>
                    </td>
                    <td>{{ optional($invoice->created_at)->format('Y-m-d') }}</td>
                    <td>
                        <a href="{{ route('restaurant.invoices.show', $invoice->id) }}" class="btn btn-outline btn-sm">عرض التفاصيل</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center;color:var(--text-muted);padding:1.2rem;">لا توجد فواتير مطابقة للفلاتر.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($invoices->hasPages())
<div class="mt-3">{{ $invoices->links('vendor.pagination.bootstrap-5') }}</div>
@endif
@endsection

