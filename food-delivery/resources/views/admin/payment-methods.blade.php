@extends('layouts.admin')

@section('title', 'طرق تحويل المدفوعات')

@section('styles')
<style>
.pm-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1rem; }
.pm-stat-card { background: var(--white); border-radius: 14px; box-shadow: var(--shadow-sm); padding: 0.85rem 1rem; }
.pm-data-card { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
.table th { background: var(--bg-page); font-size: 0.75rem; font-weight: 700; color: var(--text-muted); padding: 0.875rem 1rem; border: none; white-space: nowrap; }
.table td { padding: 0.875rem 1rem; border-color: var(--border); vertical-align: middle; font-size: 0.875rem; }
.badge-soft { padding: 0.25rem 0.55rem; border-radius: 20px; font-size: 0.72rem; font-weight: 700; }
.badge-bank { background: #DCFCE7; color: #166534; }
.badge-wallet { background: #DBEAFE; color: #1D4ED8; }
.preview-thumb { height: 48px; max-width: 120px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border); background:#fff;}
.btn-action { padding: 0.375rem 0.75rem; border-radius: 8px; font-size: 0.78rem; border: 1px solid var(--border); background: var(--white); color: var(--text-muted); cursor: pointer; margin-left: 0.35rem; }
.btn-action:hover { background: #F9FAFB; color: var(--text-dark); }
.btn-delete:hover { background: #FEE2E2; border-color: #DC2626; color: #DC2626; }
.btn-add { background: var(--primary); border: none; padding: 0.6rem 1.25rem; border-radius: var(--radius-sm); font-family: 'Cairo', sans-serif; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; color: white !important; cursor: pointer; }
.modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;}
.modal.show { display: flex; }
.modal-content { background: var(--white); border-radius: 16px; width: 100%; max-width: 560px; max-height: 92vh; overflow-y: auto; box-shadow: var(--shadow-md); }
.modal-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; background: var(--white); z-index: 1;}
.modal-body { padding: 1.5rem; }
.modal-footer { padding: 1rem 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 0.75rem; }
.form-group { margin-bottom: 1rem; }
.form-label { display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.45rem; }
.form-control, .form-select { width: 100%; padding: 0.62rem 0.875rem; border: 1px solid var(--border); border-radius: 8px; font-family: 'Cairo', sans-serif; font-size: 0.88rem; }
.form-control:focus, .form-select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-muted); }
.btn { padding: 0.625rem 1.25rem; border-radius: 8px; font-family: 'Cairo', sans-serif; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-secondary { background: var(--white); border: 1px solid var(--border); color: var(--text-muted); }
.btn-save { background: var(--primary); border: none; color: white; }
.preview-card { margin-top: 0.65rem; padding: 0.75rem; border-radius: 12px; border: 1px dashed var(--border); background: var(--bg-page); text-align: center;}
.preview-card img { max-width: 100%; max-height: 120px; object-fit: contain; border-radius: 8px; }
.preview-card.muted { color: var(--text-muted); font-size: 0.82rem;}
.field-section { padding: 0.75rem; border-radius: 12px; background: #F9FAFB; border: 1px solid var(--border); margin-bottom: 1rem;}
.field-section-title { font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.75rem;}
.pagination-bar { display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-top:1rem; background:#fff; border-radius:14px; box-shadow:var(--shadow-sm); padding:0.7rem 1rem; }
.pagination-info { font-size:0.83rem; color:var(--text-muted); }
.pagination-links { display:flex; align-items:center; gap:0.35rem; }
.page-pill { min-width:34px; height:34px; padding:0 0.6rem; display:inline-flex; align-items:center; justify-content:center; border-radius:10px; border:1px solid var(--border); background:#fff; color:#4B5563; text-decoration:none; font-size:0.8rem; font-weight:600; }
.page-pill.active { background:var(--primary); border-color:var(--primary); color:#fff; }
.page-pill.disabled { opacity:0.5; pointer-events:none; }
@media (max-width: 992px){ .pm-stats { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
@php
    use App\Enums\PaymentMethodType;
@endphp

<div class="header d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h1 class="page-title">طرق تحويل المدفوعات</h1>
        <p class="page-subtitle">إدارة المستلمين البنكيين والمحافظ الإلكترونية للتحويل اليدوي</p>
    </div>
    <button type="button" class="btn-add" onclick="pmOpenModalCreate()">
        <i class="fas fa-plus"></i>
        إضافة مستلم
    </button>
</div>

@if ($errors->any())
<div class="alert-error d-flex align-items-start mb-4 flex-column gap-1">
    <div class="fw-bold"><i class="fas fa-exclamation-circle me-1"></i>تعذّر الحفظ</div>
    <ul class="mb-0 ps-3" style="font-size:.88rem;">
        @foreach ($errors->all() as $e)
            <li>{{ $e }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="pm-stats">
    <div class="pm-stat-card"><div class="stats-label" style="font-size:.78rem;color:var(--text-muted);">إجمالي الطرق</div><div style="margin-top:.2rem;font-size:1.2rem;font-weight:700;color:var(--text-dark);">{{ number_format($stats['total']) }}</div></div>
    <div class="pm-stat-card"><div class="stats-label" style="font-size:.78rem;color:var(--text-muted);">نشطة</div><div style="margin-top:.2rem;font-size:1.2rem;font-weight:700;color:var(--text-dark);">{{ number_format($stats['active']) }}</div></div>
    <div class="pm-stat-card"><div class="stats-label" style="font-size:.78rem;color:var(--text-muted);">متوقّفة</div><div style="margin-top:.2rem;font-size:1.2rem;font-weight:700;color:var(--text-dark);">{{ number_format(max(0, $stats['total'] - $stats['active'])) }}</div></div>
</div>

<div class="pm-data-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>النوع</th>
                    <th>البنك / المحفظة</th>
                    <th>صاحب الحساب</th>
                    <th>معاينة الشعار</th>
                    <th>النشاط</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($paymentMethods as $method)
                    <tr>
                        <td>
                            <span class="badge-soft {{ $method->type === PaymentMethodType::Bank ? 'badge-bank' : 'badge-wallet' }}">
                                {{ $typeLabels[$method->type->value] ?? $method->type->value }}
                            </span>
                        </td>
                        <td>
                            {{ $method->subtypeLabel() }}
                            @if($method->type === PaymentMethodType::Bank && $method->account_number)
                                <div style="font-size:.78rem;color:var(--text-muted);">رقم حساب: {{ $method->account_number }}</div>
                            @endif
                            <div style="font-size:.78rem;color:var(--text-muted);">{{ $method->phone_number }}</div>
                        </td>
                        <td>{{ $method->account_holder_name }}</td>
                        <td>
                            @if($method->static_image)
                                <img src="{{ asset($method->static_image) }}" alt="" class="preview-thumb">
                            @else
                                <span style="color:var(--text-muted);font-size:.8rem;">—</span>
                            @endif
                        </td>
                        <td>
                            <form action="{{ route('admin.payment-methods.toggle', $method) }}" method="post" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="badge-soft btn border-0" style="{{ $method->is_active ? 'background:#D1FAE5;color:#059669;' : 'background:#FEE2E2;color:#DC2626;' }} cursor:pointer;">
                                    {{ $method->is_active ? 'نشط' : 'غير نشط' }}
                                </button>
                            </form>
                        </td>
                        <td>
                            <button type="button" class="btn-action" onclick='pmEdit(@json($method->id))'>
                                <i class="fas fa-edit"></i> تعديل
                            </button>
                            <form action="{{ route('admin.payment-methods.destroy', $method) }}" method="post" class="d-inline" onsubmit="return confirm('هل أنت متأكد من الحذف؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-action btn-delete"><i class="fas fa-trash"></i> حذف</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">لم تُعرّف طرق تحويل بعد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($paymentMethods->hasPages())
<div class="pagination-bar">
    <span class="pagination-info">عرض {{ $paymentMethods->firstItem() }} - {{ $paymentMethods->lastItem() }} من {{ $paymentMethods->total() }}</span>
    <div class="pagination-links">
        <a href="{{ $paymentMethods->previousPageUrl() ?: '#' }}" class="page-pill {{ $paymentMethods->onFirstPage() ? 'disabled' : '' }}">السابق</a>
        @php $start = max(1, $paymentMethods->currentPage() - 2); $end = min($paymentMethods->lastPage(), $paymentMethods->currentPage() + 2); @endphp
        @for($page = $start; $page <= $end; $page++)
            <a href="{{ $paymentMethods->url($page) }}" class="page-pill {{ $paymentMethods->currentPage() === $page ? 'active' : '' }}">{{ $page }}</a>
        @endfor
        <a href="{{ $paymentMethods->nextPageUrl() ?: '#' }}" class="page-pill {{ $paymentMethods->hasMorePages() ? '' : 'disabled' }}">التالي</a>
    </div>
</div>
@endif

<div class="modal" id="pmModal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="modal-title fw-bold"><i class="fas fa-money-check-alt ms-2 text-primary"></i><span id="pmModalTitle">إضافة مستلم</span></span>
            <button type="button" class="modal-close bg-transparent border-0 fs-4" onclick="pmCloseModal()">&times;</button>
        </div>
        <form id="pmForm" method="post" action="{{ route('admin.payment-methods.store') }}">
            @csrf
            <input type="hidden" name="_method" id="pmFormMethod" value="POST">

            <div class="modal-body">
                <div class="field-section-title">نوع الطريقة</div>
                <div class="form-group">
                    <label class="form-label">التصنيف</label>
                    <select name="type" id="pmType" class="form-select" required onchange="pmOnTypeChange()">
                        @foreach(\App\Enums\PaymentMethodType::cases() as $t)
                            <option value="{{ $t->value }}" @selected(old('type') === $t->value)>{{ $t->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="pmBankSection" class="field-section">
                    <div class="field-section-title">بيانات البنك</div>
                    <div class="form-group">
                        <label class="form-label">البنك</label>
                        <select name="bank_name" id="pmBankName" class="form-select" onchange="pmUpdatePreview()">
                            <option value="">— اختر —</option>
                            @foreach($bankLabels as $val => $arLabel)
                                <option value="{{ $val }}" data-label="{{ $val }}" @selected(old('bank_name') === $val)>{{ $arLabel }} — {{ $val }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">رقم الحساب</label>
                        <input type="text" name="account_number" id="pmAccountNumber" class="form-control" value="{{ old('account_number') }}" autocomplete="off">
                    </div>
                </div>

                <div id="pmWalletSection" class="field-section" style="display:none;">
                    <div class="field-section-title">بيانات المحفظة</div>
                    <div class="form-group">
                        <label class="form-label">مزوّد المحفظة</label>
                        <select name="wallet_provider" id="pmWalletProvider" class="form-select" onchange="pmUpdatePreview()">
                            <option value="">— اختر —</option>
                            @foreach($walletLabels as $val => $arLabel)
                                <option value="{{ $val }}" @selected(old('wallet_provider') === $val)>{{ $arLabel }} — {{ $val }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">اسم صاحب الحساب</label>
                    <input type="text" name="account_holder_name" id="pmAccountHolder" class="form-control" required value="{{ old('account_holder_name') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">رقم الهاتف</label>
                    <input type="text" name="phone_number" id="pmPhone" class="form-control" required value="{{ old('phone_number') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">النشاط عند الإضافة / التحديث</label>
                    <select name="is_active" id="pmIsActive" class="form-select" required>
                        <option value="1" @selected(old('is_active','1') == '1')>نشط</option>
                        <option value="0" @selected(old('is_active') === '0')>غير نشط</option>
                    </select>
                </div>

                <div class="preview-card" id="pmPreview">
                    <div class="muted" id="pmPreviewPlaceholder">سيظهر الشعار أو تعليمات البنك/المحفّة بعد الاختيار.</div>
                    <img id="pmPreviewImg" src="" alt="" style="display:none;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="pmCloseModal()">إلغاء</button>
                <button type="submit" class="btn btn-save">حفظ</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
window.__pmRows = @json($paymentMethodsForJs);
window.__pmPreviewUrls = @json($previewUrls);

function pmPreviewKey(type, selection) {
    if (!selection) return null;
    return type + '|' + selection;
}

function pmUpdatePreview() {
    const type = document.getElementById('pmType').value;
    let selection = '';
    if (type === 'bank') {
        selection = document.getElementById('pmBankName').value;
    } else if (type === 'wallet') {
        selection = document.getElementById('pmWalletProvider').value;
    }
    const key = pmPreviewKey(type, selection);
    const url = key && window.__pmPreviewUrls ? window.__pmPreviewUrls[key] : null;
    const img = document.getElementById('pmPreviewImg');
    const placeholder = document.getElementById('pmPreviewPlaceholder');
    if (url) {
        img.src = url;
        img.style.display = 'block';
        placeholder.style.display = 'none';
    } else {
        img.removeAttribute('src');
        img.style.display = 'none';
        placeholder.style.display = 'block';
    }
}

function pmOnTypeChange() {
    const type = document.getElementById('pmType').value;
    document.getElementById('pmBankSection').style.display = type === 'bank' ? 'block' : 'none';
    document.getElementById('pmWalletSection').style.display = type === 'wallet' ? 'block' : 'none';
    pmUpdatePreview();
}

function pmOpenModalCreate() {
    document.getElementById('pmModalTitle').textContent = 'إضافة مستلم';
    const form = document.getElementById('pmForm');
    form.action = @json(route('admin.payment-methods.store'));
    document.getElementById('pmFormMethod').value = 'POST';

    ['pmBankName','pmWalletProvider','pmAccountNumber','pmAccountHolder','pmPhone'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = (id === 'pmBankName' || id === 'pmWalletProvider') ? '' : '';
    });
    document.getElementById('pmIsActive').value = '1';
    document.getElementById('pmType').selectedIndex = 0;
    pmOnTypeChange();
    document.getElementById('pmModal').classList.add('show');
}

function pmEdit(id) {
    const row = (window.__pmRows || []).find(r => Number(r.id) === Number(id));
    if (!row) return;

    document.getElementById('pmModalTitle').textContent = 'تعديل مستلم';
    const form = document.getElementById('pmForm');
    form.action = @json(url('/admin/payment-methods')) + '/' + id;
    document.getElementById('pmFormMethod').value = 'PUT';

    document.getElementById('pmType').value = row.type;
    document.getElementById('pmBankName').value = row.bank_name || '';
    document.getElementById('pmWalletProvider').value = row.wallet_provider || '';
    document.getElementById('pmAccountNumber').value = row.account_number || '';
    document.getElementById('pmAccountHolder').value = row.account_holder_name || '';
    document.getElementById('pmPhone').value = row.phone_number || '';
    document.getElementById('pmIsActive').value = row.is_active ? '1' : '0';

    pmOnTypeChange();
    document.getElementById('pmModal').classList.add('show');
}

function pmCloseModal() {
    document.getElementById('pmModal').classList.remove('show');
}

document.getElementById('pmModal').addEventListener('click', function (e) {
    if (e.target === this) pmCloseModal();
});

document.addEventListener('DOMContentLoaded', function () {
    pmOnTypeChange();
@if($errors->any())
    document.getElementById('pmModal').classList.add('show');
    pmOnTypeChange();
    pmUpdatePreview();
@endif
});
</script>
@endsection
