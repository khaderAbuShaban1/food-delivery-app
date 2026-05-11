@extends('layouts.admin')

@section('title', 'فواتير المطاعم')

@section('styles')
<style>
.card-clean { background: #fff; border-radius: 16px; box-shadow: var(--shadow); border: 1px solid var(--border); }
.summary-grid { display:grid; grid-template-columns: repeat(4,minmax(0,1fr)); gap:1rem; margin-bottom:1rem; }
.summary-item { padding:1rem; }
.summary-label { color: var(--text-muted); font-size:.85rem; }
.summary-value { font-size:1.35rem; font-weight:700; color: var(--text-dark); }
.filter-row { display:grid; grid-template-columns: 1.2fr 1fr 1fr 1fr auto; gap:.75rem; }
.form-control, .form-select { border-radius: 10px; border:1px solid var(--border); }
.table thead th { font-size:.82rem; color: var(--text-muted); font-weight:700; background: #fafafa; }
.badge-status { padding:.35rem .7rem; border-radius:999px; font-size:.75rem; font-weight:700; }
.badge-status.pending { background:#FFF7D6; color:#9A6700; }
.badge-status.paid { background:#DCFCE7; color:#166534; }
.badge-status.unpaid { background:#FEE2E2; color:#991B1B; }
.modal .modal-content { border-radius: 16px; border: 1px solid var(--border); }
.modal.fade .modal-dialog { transform: translateY(16px) scale(.98); transition: all .2s ease; }
.modal.show .modal-dialog { transform: translateY(0) scale(1); }
@media (max-width: 1100px) {
    .summary-grid { grid-template-columns: repeat(2,minmax(0,1fr)); }
    .filter-row { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 700px) {
    .summary-grid { grid-template-columns: 1fr; }
    .filter-row { grid-template-columns: 1fr; }
}
</style>
@endsection

@section('content')
<div class="header mb-3">
    <h1 class="page-title">فواتير المطاعم</h1>
    <p class="page-subtitle">إنشاء وإدارة فواتير المطاعم وحساب العمولات وحالة الدفع.</p>
</div>

<div class="summary-grid">
    <div class="card-clean summary-item"><div class="summary-label">عدد الفواتير</div><div class="summary-value">{{ number_format($summary['total_invoices']) }}</div></div>
    <div class="card-clean summary-item"><div class="summary-label">إجمالي المبيعات</div><div class="summary-value">{{ number_format($summary['total_sales'], 2) }} شيكل</div></div>
    <div class="card-clean summary-item"><div class="summary-label">إجمالي العمولة</div><div class="summary-value">{{ number_format($summary['total_commission'], 2) }} شيكل</div></div>
    <div class="card-clean summary-item"><div class="summary-label">صافي المطاعم</div><div class="summary-value">{{ number_format($summary['total_restaurant_amount'], 2) }} شيكل</div></div>
</div>

<div class="card-clean p-3 mb-3">
    <form method="GET" action="{{ route('admin.restaurant-invoices.index') }}" class="filter-row">
        <select name="restaurant_id" class="form-select">
            <option value="">كل المطاعم</option>
            @foreach($restaurants as $restaurant)
                <option value="{{ $restaurant->id }}" @selected(request('restaurant_id') == $restaurant->id)>{{ $restaurant->name }}</option>
            @endforeach
        </select>
        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
        <select name="status" class="form-select">
            <option value="">كل الحالات</option>
            <option value="pending" @selected(request('status') === 'pending')>قيد الانتظار</option>
            <option value="paid" @selected(request('status') === 'paid')>مدفوع</option>
            <option value="unpaid" @selected(request('status') === 'unpaid')>غير مدفوع</option>
        </select>
        <button type="submit" class="btn btn-primary">تصفية</button>
    </form>
</div>

<div class="card-clean p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table align-middle mb-0" id="invoicesTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>رقم الفاتورة</th>
                    <th>المطعم</th>
                    <th>الفترة</th>
                    <th>عدد الطلبات</th>
                    <th>المبيعات</th>
                    <th>العمولة</th>
                    <th>الصافي</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                <tr id="invoice-row-{{ $invoice->id }}"
                    data-id="{{ $invoice->id }}"
                    data-invoice-number="{{ $invoice->invoice_number }}"
                    data-start-date="{{ $invoice->start_date?->format('Y-m-d') }}"
                    data-end-date="{{ $invoice->end_date?->format('Y-m-d') }}"
                    data-status="{{ $invoice->status }}"
                    data-notes="{{ $invoice->notes }}"
                    data-commission="{{ number_format((float)$invoice->commission_percentage, 2, '.', '') }}"
                    data-paid-at="{{ $invoice->payment_paid_at?->format('Y-m-d') }}"
                    data-update-url="{{ route('admin.restaurant-invoices.update', $invoice) }}"
                    data-proof-url="{{ route('admin.restaurant-invoices.payment-proof', $invoice) }}"
                    data-delete-url="{{ route('admin.restaurant-invoices.destroy', $invoice) }}">
                    <td>{{ $invoice->id }}</td>
                    <td class="col-invoice-number">{{ $invoice->invoice_number }}</td>
                    <td>{{ $invoice->restaurant?->name ?? '-' }}</td>
                    <td class="col-period">{{ $invoice->start_date?->format('Y-m-d') }} - {{ $invoice->end_date?->format('Y-m-d') }}</td>
                    <td class="col-total-orders">{{ $invoice->total_orders }}</td>
                    <td class="col-total-sales">{{ number_format($invoice->total_sales, 2) }} شيكل</td>
                    <td class="col-commission">{{ number_format($invoice->commission_percentage, 2) }}% ({{ number_format($invoice->commission_amount, 2) }} شيكل)</td>
                    <td class="col-final-amount">{{ number_format($invoice->final_amount, 2) }} شيكل</td>
                    <td class="col-status"><span class="badge-status {{ $invoice->status }}">{{ $invoice->status === 'pending' ? 'قيد الانتظار' : ($invoice->status === 'paid' ? 'مدفوع' : 'غير مدفوع') }}</span></td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="{{ route('admin.restaurant-invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary">عرض</a>
                            <button type="button" class="btn btn-sm btn-outline-dark js-edit-btn">تعديل</button>
                            <button type="button" class="btn btn-sm btn-outline-info js-proof-btn">رفع إثبات</button>
                            <button type="button" class="btn btn-sm btn-danger js-delete-btn">حذف</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center py-4 text-muted">لا توجد فواتير.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($invoices->hasPages())
<div class="mt-3">{{ $invoices->links('vendor.pagination.bootstrap-5') }}</div>
@endif

<div class="modal fade" id="editInvoiceModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="editInvoiceForm">
        <div class="modal-header"><h5 class="modal-title">تعديل الفاتورة</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body d-grid gap-2">
          <input type="date" class="form-control" name="start_date" required>
          <input type="date" class="form-control" name="end_date" required>
          <select class="form-select" name="status" required><option value="pending">قيد الانتظار</option><option value="paid">مدفوع</option><option value="unpaid">غير مدفوع</option></select>
          <input type="number" class="form-control" name="commission_percentage" min="0" max="100" step="0.01" placeholder="نسبة العمولة %">
          <textarea class="form-control" rows="3" name="notes" placeholder="ملاحظات"></textarea>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button><button type="submit" class="btn btn-primary" id="saveEditBtn">حفظ التعديلات</button></div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="proofModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="proofForm" enctype="multipart/form-data">
        <div class="modal-header"><h5 class="modal-title">رفع إثبات الدفع</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body d-grid gap-2">
          <input type="file" class="form-control" name="payment_proof_image" id="proofImageInput" accept=".jpg,.jpeg,.png,.webp,image/*" required>
          <input type="date" class="form-control" name="payment_paid_at">
          <img id="proofPreview" src="" alt="Preview" class="d-none" style="max-height:220px;border-radius:12px;border:1px solid var(--border);">
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button><button type="submit" class="btn btn-info" id="uploadProofBtn">رفع</button></div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="deleteInvoiceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">تأكيد حذف الفاتورة</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">Are you sure you want to delete this invoice?</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
const csrf = '{{ csrf_token() }}';
let activeRow = null;
let deleteUrl = '';
const editModal = new bootstrap.Modal(document.getElementById('editInvoiceModal'));
const proofModal = new bootstrap.Modal(document.getElementById('proofModal'));
const deleteModal = new bootstrap.Modal(document.getElementById('deleteInvoiceModal'));

function statusText(status){ return status === 'paid' ? 'مدفوع' : (status === 'unpaid' ? 'غير مدفوع' : 'قيد الانتظار'); }
function money(v){ return Number(v||0).toFixed(2); }

function bindRowActions() {
  document.querySelectorAll('#invoicesTable .js-edit-btn').forEach(btn => {
    btn.onclick = () => {
      activeRow = btn.closest('tr');
      const f = document.getElementById('editInvoiceForm');
      f.start_date.value = activeRow.dataset.startDate || '';
      f.end_date.value = activeRow.dataset.endDate || '';
      f.status.value = activeRow.dataset.status || 'pending';
      f.commission_percentage.value = activeRow.dataset.commission || '';
      f.notes.value = activeRow.dataset.notes || '';
      editModal.show();
    };
  });

  document.querySelectorAll('#invoicesTable .js-proof-btn').forEach(btn => {
    btn.onclick = () => {
      activeRow = btn.closest('tr');
      document.getElementById('proofForm').reset();
      document.getElementById('proofPreview').classList.add('d-none');
      proofModal.show();
    };
  });

  document.querySelectorAll('#invoicesTable .js-delete-btn').forEach(btn => {
    btn.onclick = () => {
      activeRow = btn.closest('tr');
      deleteUrl = activeRow.dataset.deleteUrl;
      deleteModal.show();
    };
  });
}

bindRowActions();

document.getElementById('editInvoiceForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  if(!activeRow) return;
  const btn = document.getElementById('saveEditBtn');
  btn.disabled = true;
  const fd = new FormData(e.target);
  fd.append('_token', csrf);
  fd.append('_method', 'PATCH');
  const res = await fetch(activeRow.dataset.updateUrl, { method:'POST', headers:{'Accept':'application/json'}, body: fd });
  const json = await res.json();
  btn.disabled = false;
  if(!res.ok || !json.success){ toastr.error(json.message || 'حدث خطأ'); return; }
  const inv = json.invoice;
  activeRow.dataset.startDate = inv.start_date;
  activeRow.dataset.endDate = inv.end_date;
  activeRow.dataset.status = inv.status;
  activeRow.dataset.notes = inv.notes || '';
  activeRow.dataset.commission = inv.commission_percentage;
  activeRow.querySelector('.col-period').textContent = `${inv.start_date} - ${inv.end_date}`;
  activeRow.querySelector('.col-total-orders').textContent = inv.total_orders;
  activeRow.querySelector('.col-total-sales').textContent = `${money(inv.total_sales)} شيكل`;
  activeRow.querySelector('.col-commission').textContent = `${money(inv.commission_percentage)}% (${money(inv.commission_amount)} شيكل)`;
  activeRow.querySelector('.col-final-amount').textContent = `${money(inv.final_amount)} شيكل`;
  activeRow.querySelector('.col-status').innerHTML = `<span class="badge-status ${inv.status}">${statusText(inv.status)}</span>`;
  editModal.hide();
  toastr.success(json.message || 'تم الحفظ');
});

document.getElementById('proofImageInput').addEventListener('change', (e) => {
  const [file] = e.target.files || [];
  const preview = document.getElementById('proofPreview');
  if(!file){ preview.classList.add('d-none'); preview.src=''; return; }
  const reader = new FileReader();
  reader.onload = (ev) => { preview.src = ev.target.result; preview.classList.remove('d-none'); };
  reader.readAsDataURL(file);
});

document.getElementById('proofForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  if(!activeRow) return;
  const btn = document.getElementById('uploadProofBtn');
  btn.disabled = true;
  document.querySelector('#proofModal .btn-close').disabled = true;
  const fd = new FormData(e.target);
  fd.append('_token', csrf);
  fd.append('_method', 'PATCH');
  const res = await fetch(activeRow.dataset.proofUrl, { method:'POST', headers:{'Accept':'application/json'}, body: fd });
  const json = await res.json();
  btn.disabled = false;
  document.querySelector('#proofModal .btn-close').disabled = false;
  if(!res.ok || !json.success){ toastr.error(json.message || 'حدث خطأ'); return; }
  proofModal.hide();
  toastr.success(json.message || 'تم الرفع');
});

document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
  if(!activeRow || !deleteUrl) return;
  const btn = document.getElementById('confirmDeleteBtn');
  btn.disabled = true;
  const fd = new FormData();
  fd.append('_token', csrf);
  fd.append('_method', 'DELETE');
  const res = await fetch(deleteUrl, { method:'POST', headers:{'Accept':'application/json'}, body: fd });
  const json = await res.json();
  btn.disabled = false;
  if(!res.ok || !json.success){ toastr.error(json.message || 'حدث خطأ'); return; }
  activeRow.remove();
  deleteModal.hide();
  toastr.success(json.message || 'تم الحذف');
});
</script>
@endsection
