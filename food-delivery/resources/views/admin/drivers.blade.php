@extends('layouts.admin')

@section('title', 'طلبات تسجيل السائقين')

@section('styles')
<style>
.drivers-page { padding: 0; color: #111827; }
.drivers-header {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
    margin-bottom: 1.5rem;
}
.drivers-header h1 {
    font-size: 1.65rem;
    font-weight: 800;
    color: #111827;
    margin: 0 0 .35rem;
}
.drivers-header p {
    color: #6B7280;
    font-size: .92rem;
    margin: 0;
    max-width: 680px;
}
.live-chip {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    border: 1px solid #DBEAFE;
    background: #EFF6FF;
    color: #1D4ED8;
    border-radius: 999px;
    padding: .48rem .8rem;
    font-size: .8rem;
    font-weight: 800;
    white-space: nowrap;
}
.live-dot {
    width: .55rem;
    height: .55rem;
    border-radius: 999px;
    background: #2563EB;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
}
.stats-row {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 1.1rem;
}
.stat-box {
    background: #FFFFFF;
    border-radius: 12px;
    padding: 1rem;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .08);
    border: 1px solid #EEF2F7;
    position: relative;
    overflow: hidden;
}
.stat-box::before {
    content: "";
    position: absolute;
    inset-inline-start: 0;
    top: 0;
    bottom: 0;
    width: 4px;
}
.stat-pending::before { background: #F59E0B; }
.stat-approved::before { background: #10B981; }
.stat-rejected::before { background: #EF4444; }
.stat-box span {
    display: block;
    color: #6B7280;
    font-size: .82rem;
    margin-bottom: .35rem;
}
.stat-box strong {
    color: #111827;
    font-size: 1.65rem;
    font-weight: 800;
    line-height: 1;
}
.filters {
    display: flex;
    gap: .6rem;
    flex-wrap: wrap;
    margin-bottom: 1.1rem;
}
.filter-pill {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    border: 1px solid #E5E7EB;
    background: #FFFFFF;
    color: #374151;
    border-radius: 999px;
    padding: .55rem .9rem;
    text-decoration: none;
    font-size: .84rem;
    font-weight: 800;
    transition: border-color .18s ease, background .18s ease, color .18s ease;
}
.filter-pill:hover {
    border-color: #CBD5E1;
    color: #111827;
}
.filter-pill.active {
    background: #FFF7ED;
    border-color: #FDBA74;
    color: #C2410C;
}
.filter-dot {
    width: .55rem;
    height: .55rem;
    border-radius: 999px;
}
.filter-dot.pending { background: #F59E0B; }
.filter-dot.approved { background: #10B981; }
.filter-dot.rejected { background: #EF4444; }
.drivers-card {
    background: #FFFFFF;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .08);
    border: 1px solid #EEF2F7;
    overflow: hidden;
}
.drivers-card-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: center;
    padding: 1rem 1.15rem;
    border-bottom: 1px solid #EEF2F7;
    background: #FFFFFF;
}
.drivers-card-head h2 {
    margin: 0 0 .25rem;
    font-size: 1rem;
    font-weight: 800;
    color: #111827;
}
.drivers-card-head p {
    margin: 0;
    color: #6B7280;
    font-size: .82rem;
}
.refresh-note {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    background: #F3F4F6;
    color: #6B7280;
    padding: .35rem .65rem;
    font-size: .75rem;
    font-weight: 800;
    white-space: nowrap;
}
.refresh-note.loading { background: #EFF6FF; color: #1D4ED8; }
.drivers-table-wrap { width: 100%; overflow-x: auto; }
.drivers-table {
    width: 100%;
    min-width: 1050px;
    border-collapse: collapse;
}
.drivers-table th {
    background: #F8FAFC;
    color: #64748B;
    font-size: .74rem;
    font-weight: 800;
    padding: .85rem 1rem;
    text-align: right;
    border-bottom: 1px solid #E5E7EB;
    white-space: nowrap;
}
.drivers-table td {
    padding: .95rem 1rem;
    border-bottom: 1px solid #F1F5F9;
    vertical-align: middle;
    color: #374151;
    font-size: .86rem;
}
.drivers-table tbody tr:hover { background: #FAFAFA; }
.driver-profile {
    display: flex;
    align-items: center;
    gap: .8rem;
    min-width: 260px;
}
.driver-avatar {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    object-fit: cover;
    border: 1px solid #E5E7EB;
    background: #F9FAFB;
    flex: 0 0 auto;
}
.avatar-fallback {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #475569;
    font-weight: 800;
    font-size: 1rem;
}
.driver-copy strong {
    display: block;
    color: #111827;
    font-size: .94rem;
    margin-bottom: .18rem;
}
.driver-copy span {
    display: block;
    color: #64748B;
    font-size: .8rem;
}
.cell-label {
    display: none;
    color: #94A3B8;
    font-size: .72rem;
    font-weight: 800;
    margin-bottom: .25rem;
}
.cell-muted {
    display: block;
    color: #94A3B8;
    font-size: .78rem;
    margin-top: .15rem;
}
.mono-value {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    font-size: .86rem;
    color: #111827;
}
.status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    padding: .36rem .75rem;
    font-size: .75rem;
    font-weight: 800;
    white-space: nowrap;
}
.status-badge.pending { background: #FEF3C7; color: #92400E; }
.status-badge.approved { background: #D1FAE5; color: #047857; }
.status-badge.rejected { background: #FEE2E2; color: #B91C1C; }
.actions {
    display: flex;
    gap: .45rem;
    flex-wrap: nowrap;
}
.action-btn {
    border: 0;
    border-radius: 9px;
    padding: .52rem .82rem;
    font-size: .78rem;
    font-weight: 800;
    cursor: pointer;
    transition: transform .15s ease, opacity .15s ease;
    white-space: nowrap;
}
.action-btn:hover:not(:disabled) { transform: translateY(-1px); }
.action-btn.approve { background: #10B981; color: #FFFFFF; }
.action-btn.reject { background: #EF4444; color: #FFFFFF; }
.action-btn:disabled {
    background: #E5E7EB;
    color: #9CA3AF;
    cursor: not-allowed;
    opacity: .9;
}
.doc-links {
    display: flex;
    gap: .3rem;
    flex-wrap: wrap;
    margin-top: .35rem;
}
.doc-link {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    background: #EEF2FF;
    color: #3730A3;
    padding: .22rem .5rem;
    font-size: .7rem;
    font-weight: 800;
    text-decoration: none;
}
.doc-link:hover { color: #312E81; background: #E0E7FF; }
.empty-state {
    padding: 3rem 1rem;
    text-align: center;
    color: #9CA3AF;
}
.pagination-wrap {
    padding: 1rem;
    border-top: 1px solid #E5E7EB;
}
.confirm-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(17, 24, 39, .52);
    z-index: 1200;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.confirm-modal.show { display: flex; }
.confirm-box {
    width: 100%;
    max-width: 430px;
    background: #FFFFFF;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0,0,0,.22);
    overflow: hidden;
}
.confirm-head {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #E5E7EB;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.confirm-title { font-weight: 800; color: #111827; }
.confirm-close {
    border: 0;
    background: transparent;
    color: #6B7280;
    font-size: 1.3rem;
    cursor: pointer;
}
.confirm-body {
    padding: 1.25rem;
    color: #4B5563;
    line-height: 1.7;
}
.confirm-footer {
    display: flex;
    justify-content: flex-end;
    gap: .5rem;
    padding: 1rem 1.25rem;
    border-top: 1px solid #E5E7EB;
}
.confirm-btn {
    border: 0;
    border-radius: 10px;
    padding: .55rem 1rem;
    font-weight: 800;
    cursor: pointer;
}
.confirm-btn.cancel { background: #F3F4F6; color: #374151; }
.confirm-btn.approve { background: #10B981; color: #FFFFFF; }
.confirm-btn.reject { background: #EF4444; color: #FFFFFF; }
.confirm-btn:disabled { opacity: .65; cursor: wait; }
@media (max-width: 900px) {
    .drivers-header { flex-direction: column; }
    .stats-row { grid-template-columns: 1fr; }
    .drivers-card-head { align-items: flex-start; flex-direction: column; }
}
@media (max-width: 720px) {
    .drivers-table,
    .drivers-table thead,
    .drivers-table tbody,
    .drivers-table th,
    .drivers-table td,
    .drivers-table tr {
        display: block;
        min-width: 0;
    }
    .drivers-table thead { display: none; }
    .drivers-table tr {
        padding: .85rem 1rem;
        border-bottom: 1px solid #E5E7EB;
    }
    .drivers-table td {
        padding: .45rem 0;
        border: 0;
    }
    .cell-label { display: block; }
    .actions { margin-top: .3rem; }
}
</style>
@endsection

@section('content')
<div class="drivers-page">
    <div class="drivers-header">
        <div>
            <h1>طلبات تسجيل السائقين</h1>
            <p>مراجعة بيانات السائقين الجدد والموافقة على الوصول إلى تطبيق السائق بعد التحقق من الهوية والمركبة.</p>
        </div>
        <div class="live-chip" title="يتم تحديث القائمة كل عدة ثوانٍ">
            <span class="live-dot"></span>
            <span>مباشر</span>
        </div>
    </div>

    <div id="driversListContent">
        @include('admin::partials.drivers-list')
    </div>
</div>

<div class="confirm-modal" id="driverConfirmModal" onclick="closeDriverConfirm(event)">
    <div class="confirm-box" onclick="event.stopPropagation()">
        <div class="confirm-head">
            <span class="confirm-title" id="driverConfirmTitle">تأكيد الإجراء</span>
            <button class="confirm-close" type="button" onclick="closeDriverConfirm()">&times;</button>
        </div>
        <div class="confirm-body" id="driverConfirmMessage"></div>
        <div class="confirm-footer">
            <button class="confirm-btn cancel" type="button" onclick="closeDriverConfirm()">إلغاء</button>
            <button class="confirm-btn" type="button" id="driverConfirmSubmit" onclick="submitDriverDecision()">تأكيد</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let pendingDriverDecision = null;
let isRefreshingDrivers = false;
const driversRefreshInterval = 5000;

function getDriversRefreshUrl() {
    const url = new URL(window.location.href);
    url.searchParams.set('partial', '1');
    return url.toString();
}

function setRefreshState(text, isLoading = false) {
    document.querySelectorAll('[data-refresh-state]').forEach(function (element) {
        element.textContent = text;
        element.classList.toggle('loading', isLoading);
    });
}

function refreshDriversList(showLoading = false) {
    if (isRefreshingDrivers) return Promise.resolve();
    if (document.getElementById('driverConfirmModal').classList.contains('show')) {
        return Promise.resolve();
    }

    isRefreshingDrivers = true;
    if (showLoading) {
        setRefreshState('جار التحديث...', true);
    }

    return fetch(getDriversRefreshUrl(), {
        headers: {
            'Accept': 'text/html',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function (response) {
        if (!response.ok) {
            throw new Error('تعذر تحديث قائمة السائقين');
        }
        return response.text();
    })
    .then(function (html) {
        document.getElementById('driversListContent').innerHTML = html;
        setRefreshState('تم التحديث الآن', false);
    })
    .catch(function () {
        setRefreshState('تعذر التحديث', false);
    })
    .finally(function () {
        isRefreshingDrivers = false;
    });
}

function openDriverConfirm(driverId, action, driverName, actionUrl) {
    pendingDriverDecision = { driverId, action, actionUrl };
    const isApprove = action === 'approve';
    document.getElementById('driverConfirmTitle').textContent = isApprove ? 'تأكيد الموافقة' : 'تأكيد الرفض';
    document.getElementById('driverConfirmMessage').textContent = isApprove
        ? 'هل تريد الموافقة على طلب السائق "' + driverName + '" والسماح له بالدخول إلى التطبيق؟'
        : 'هل تريد رفض طلب السائق "' + driverName + '"؟ لن يستطيع الدخول إلى التطبيق.';

    const submit = document.getElementById('driverConfirmSubmit');
    submit.textContent = isApprove ? 'موافقة' : 'رفض';
    submit.className = 'confirm-btn ' + (isApprove ? 'approve' : 'reject');
    submit.disabled = false;
    document.getElementById('driverConfirmModal').classList.add('show');
}

function closeDriverConfirm(event) {
    if (event && event.target !== event.currentTarget) return;
    document.getElementById('driverConfirmModal').classList.remove('show');
    pendingDriverDecision = null;
}

function submitDriverDecision() {
    if (!pendingDriverDecision) return;
    const decision = pendingDriverDecision;
    const submit = document.getElementById('driverConfirmSubmit');
    submit.disabled = true;
    document.querySelectorAll('[data-driver-row="' + decision.driverId + '"] .action-btn').forEach(function (button) {
        button.disabled = true;
    });

    fetch(decision.actionUrl || ('/admin/drivers/' + decision.driverId + '/' + decision.action), {
        method: 'PATCH',
        credentials: 'same-origin',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({})
    })
    .then(async function (response) {
        const data = await response.json().catch(function () { return {}; });
        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'تعذر تحديث حالة السائق');
        }
        return data;
    })
    .then(function (data) {
        toastr.success(data.message || 'تم تحديث حالة السائق');
        closeDriverConfirm();
        return refreshDriversList(true);
    })
    .catch(function (error) {
        submit.disabled = false;
        document.querySelectorAll('[data-driver-row="' + decision.driverId + '"] .action-btn').forEach(function (button) {
            button.disabled = false;
        });
        toastr.error(error.message || 'حدث خطأ أثناء تحديث الطلب');
    });
}

document.addEventListener('click', function(event) {
    const button = event.target.closest('[data-driver-action]');
    if (!button || button.disabled) return;

    const driverId = button.dataset.driverId;
    const action = button.dataset.driverAction;
    const driverName = button.dataset.driverName || '';
    const actionUrl = button.dataset.actionUrl;

    if (!driverId || !action || !actionUrl) {
        toastr.error('حدث خطأ أثناء تنفيذ العملية');
        return;
    }

    openDriverConfirm(driverId, action, driverName, actionUrl);
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeDriverConfirm();
    }
});

window.setInterval(function () {
    refreshDriversList(false);
}, driversRefreshInterval);
</script>
@endsection
