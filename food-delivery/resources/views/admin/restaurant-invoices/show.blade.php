@extends('layouts.admin')

@section('title', 'تفاصيل الفاتورة')

@section('styles')
<style>
.details-grid { display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1rem; }
.card-clean { background:#fff; border:1px solid var(--border); border-radius:16px; box-shadow: var(--shadow); }
.summary-row { display:flex; justify-content:space-between; padding:.55rem 0; border-bottom:1px dashed var(--border); }
.summary-row:last-child { border-bottom:0; }
.status-badge { padding:.35rem .7rem; border-radius:999px; font-size:.75rem; font-weight:700; }
.status-badge.pending { background:#FFF7D6; color:#9A6700; }
.status-badge.paid { background:#DCFCE7; color:#166534; }
.status-badge.unpaid { background:#FEE2E2; color:#991B1B; }
@media (max-width: 900px) { .details-grid { grid-template-columns:1fr; } }
</style>
@endsection

@section('content')
<div class="header mb-3">
    <h1 class="page-title">الفاتورة {{ $invoice->invoice_number }}</h1>
    <p class="page-subtitle">تفاصيل الفاتورة والطلبات المضمنة.</p>
</div>

<div class="details-grid">
    <div class="card-clean p-3">
        <h5 class="mb-3">معلومات المطعم</h5>
        <div class="summary-row"><span>الاسم</span><strong>{{ $invoice->restaurant?->name ?? '-' }}</strong></div>
        <div class="summary-row"><span>البريد</span><strong>{{ $invoice->restaurant?->email ?? '-' }}</strong></div>
        <div class="summary-row"><span>الهاتف</span><strong>{{ $invoice->restaurant?->phone ?? '-' }}</strong></div>
        <div class="summary-row"><span>الفترة</span><strong>{{ $invoice->start_date?->format('Y-m-d') }} - {{ $invoice->end_date?->format('Y-m-d') }}</strong></div>
    </div>

    <div class="card-clean p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="m-0">ملخص الفاتورة</h5>
            <span class="status-badge {{ $invoice->status }}">{{ $invoice->status === 'pending' ? 'قيد الانتظار' : ($invoice->status === 'paid' ? 'مدفوع' : 'غير مدفوع') }}</span>
        </div>
        <div class="summary-row"><span>إجمالي الطلبات</span><strong>{{ $invoice->total_orders }}</strong></div>
        <div class="summary-row"><span>إجمالي المبيعات</span><strong>{{ number_format($invoice->total_sales, 2) }} شيكل</strong></div>
        <div class="summary-row"><span>العمولة ({{ number_format($invoice->commission_percentage, 2) }}%)</span><strong>{{ number_format($invoice->commission_amount, 2) }} شيكل</strong></div>
        <div class="summary-row"><span>صافي المطعم</span><strong>{{ number_format($invoice->final_amount, 2) }} شيكل</strong></div>
        <div class="summary-row"><span>ملاحظات</span><strong>{{ $invoice->notes ?: '-' }}</strong></div>
    </div>
</div>

<div class="card-clean p-3 mb-3" id="edit-form">
    <h5 class="mb-3">تعديل الفاتورة + إثبات الدفع</h5>
    <form method="POST" action="{{ route('admin.restaurant-invoices.update', $invoice) }}" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap" onsubmit="return confirm('هل تريد حفظ تعديلات الفاتورة؟ سيتم إعادة احتساب الإجماليات تلقائيًا.');">
        @csrf
        @method('PATCH')
        <input type="date" name="start_date" class="form-control" style="max-width:200px;" value="{{ $invoice->start_date?->format('Y-m-d') }}" required>
        <input type="date" name="end_date" class="form-control" style="max-width:200px;" value="{{ $invoice->end_date?->format('Y-m-d') }}" required>
        <input type="number" name="commission_percentage" min="0" max="100" step="0.01" class="form-control" style="max-width:180px;" value="{{ number_format((float)$invoice->commission_percentage, 2, '.', '') }}" placeholder="نسبة العمولة %">
        <select name="status" class="form-select" style="max-width:220px;">
            <option value="pending" @selected($invoice->status === 'pending')>قيد الانتظار</option>
            <option value="paid" @selected($invoice->status === 'paid')>مدفوع</option>
            <option value="unpaid" @selected($invoice->status === 'unpaid')>غير مدفوع</option>
        </select>
        <input type="date" name="payment_paid_at" class="form-control" style="max-width:200px;" value="{{ $invoice->payment_paid_at?->format('Y-m-d') }}" placeholder="تاريخ الدفع">
        <input type="text" name="notes" class="form-control" style="min-width:260px;" value="{{ $invoice->notes }}" placeholder="ملاحظات الفاتورة">
        <input type="file" id="paymentProofInput" name="payment_proof_image" class="form-control" style="max-width:320px;" accept=".jpg,.jpeg,.png,.webp,image/*">
        @if($invoice->payment_proof_image || $invoice->payment_proof)
            <div class="form-check d-flex align-items-center">
                <input class="form-check-input ms-2" type="checkbox" name="remove_payment_proof" value="1" id="removePaymentProof">
                <label class="form-check-label" for="removePaymentProof">حذف صورة الإثبات الحالية</label>
            </div>
        @endif
        <button class="btn btn-primary" type="submit">تحديث</button>
        <a href="{{ route('admin.restaurant-invoices.index') }}" class="btn btn-outline-secondary">رجوع</a>
    </form>
    @if($invoice->payment_proof_image || $invoice->payment_proof)
    <div class="mt-3" id="proof-upload">
        <div class="text-muted mb-2">صورة إثبات الدفع الحالية:</div>
        <a href="{{ asset('storage/' . ($invoice->payment_proof_image ?: $invoice->payment_proof)) }}" target="_blank" rel="noopener">
            <img id="paymentProofCurrent" src="{{ asset('storage/' . ($invoice->payment_proof_image ?: $invoice->payment_proof)) }}" alt="إثبات دفع" style="max-height:220px;border-radius:12px;border:1px solid var(--border);">
        </a>
        <div class="text-muted mt-2">تاريخ الدفع: {{ $invoice->payment_paid_at?->format('Y-m-d') ?: '-' }}</div>
    </div>
    @endif
    <div class="mt-3 d-none" id="paymentProofPreviewWrapper">
        <div class="text-muted mb-2">معاينة الصورة قبل الرفع:</div>
        <img id="paymentProofPreview" src="" alt="معاينة إثبات الدفع" style="max-height:220px;border-radius:12px;border:1px solid var(--border);">
    </div>
</div>

<div class="card-clean p-0 overflow-hidden">
    <div class="p-3 border-bottom"><h5 class="m-0">الطلبات المضمنة ({{ $invoice->orders->count() }})</h5></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>رقم الطلب</th>
                    <th>العميل</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                    <th>الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->orders as $order)
                <tr>
                    <td>{{ $order->order_number ?: ('#' . $order->id) }}</td>
                    <td>{{ $order->customer?->name ?? '-' }}</td>
                    <td>{{ $order->status }}</td>
                    <td>{{ optional($order->created_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ number_format((float) $order->total_price, 2) }} شيكل</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">لا توجد طلبات ضمن هذه الفاتورة.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
const paymentProofInput = document.getElementById('paymentProofInput');
const previewWrapper = document.getElementById('paymentProofPreviewWrapper');
const previewImage = document.getElementById('paymentProofPreview');

if (paymentProofInput && previewWrapper && previewImage) {
    paymentProofInput.addEventListener('change', function () {
        const [file] = this.files || [];
        if (!file) {
            previewWrapper.classList.add('d-none');
            previewImage.src = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = function (e) {
            previewImage.src = e.target.result;
            previewWrapper.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    });
}
</script>
@endsection
