@extends('layouts.admin')

@section('title', 'إدارة العملاء')

@section('styles')
<style>
.users-page { padding: 0; }

.page-header {
    margin-bottom: 1.5rem;
}

.page-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1F2937;
    margin-bottom: 0.25rem;
}

.page-header p {
    font-size: 0.9rem;
    color: #6B7280;
}

.filter-bar {
    background: #FFFFFF;
    border-radius: 16px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.filter-form {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    align-items: center;
}

.search-input {
    flex: 1;
    min-width: 200px;
    padding: 0.625rem 1rem;
    border: 1px solid #E5E7EB;
    border-radius: 10px;
    font-size: 0.875rem;
    outline: none;
    transition: all 0.2s;
}

.search-input:focus {
    border-color: #FF6B2C;
    box-shadow: 0 0 0 3px rgba(255, 107, 44, 0.1);
}

.filter-select {
    padding: 0.625rem 2rem 0.625rem 1rem;
    border: 1px solid #E5E7EB;
    border-radius: 10px;
    font-size: 0.875rem;
    outline: none;
    background: #FFFFFF url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236B7280' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E") no-repeat left 0.75rem center;
    appearance: none;
    cursor: pointer;
    min-width: 120px;
}

.date-input {
    padding: 0.625rem 1rem;
    border: 1px solid #E5E7EB;
    border-radius: 10px;
    font-size: 0.875rem;
    outline: none;
    width: 140px;
}

.date-input:focus {
    border-color: #FF6B2C;
}

.btn-filter {
    padding: 0.625rem 1.25rem;
    background: #FF6B2C;
    color: #FFFFFF;
    border: none;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-filter:hover {
    background: #E55A1C;
}

.table-card {
    background: #FFFFFF;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    overflow: hidden;
}

.users-table {
    width: 100%;
    border-collapse: collapse;
}

.users-table th {
    background: #F9FAFB;
    padding: 0.875rem 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #6B7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-align: right;
    border-bottom: 1px solid #E5E7EB;
}

.users-table td {
    padding: 1rem;
    border-bottom: 1px solid #F3F4F6;
    vertical-align: middle;
}

.users-table tr:hover {
    background: #F9FAFB;
}

.users-table tr:last-child td {
    border-bottom: none;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    object-fit: cover;
    background: #FFE8DC;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #FF6B2C;
    font-weight: 700;
    font-size: 1rem;
}

.user-details h4 {
    font-size: 0.9rem;
    font-weight: 600;
    color: #1F2937;
    margin-bottom: 0.125rem;
}

.user-details p {
    font-size: 0.8rem;
    color: #6B7280;
}

.date-cell {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: #4B5563;
}

.date-cell i {
    color: #9CA3AF;
}

.stats-cell {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.order-count {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #1F2937;
}

.order-count i {
    color: #FF6B2C;
    font-size: 0.75rem;
}

.total-spent {
    font-size: 0.8rem;
    color: #059669;
    font-weight: 600;
}

.status-badge {
    display: inline-flex;
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge.active {
    background: #D1FAE5;
    color: #059669;
}

.status-badge.banned {
    background: #FEE2E2;
    color: #DC2626;
}

.status-badge.pending {
    background: #FEF3C7;
    color: #B45309;
}

.status-badge.rejected {
    background: #FEE2E2;
    color: #B91C1C;
}

.driver-meta {
    display: block;
    margin-top: 0.2rem;
    font-size: 0.75rem;
    color: #6B7280;
}

.action-btns {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    padding: 0.5rem 0.875rem;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-details {
    background: #F3F4F6;
    color: #4B5563;
    border: 1px solid #E5E7EB;
}

.btn-details:hover {
    background: #E5E7EB;
    color: #1F2937;
}

.btn-ban {
    background: #FEE2E2;
    color: #DC2626;
    border: 1px solid #FECACA;
}

.btn-ban:hover {
    background: #FECACA;
}

.btn-unban {
    background: #FFE8DC;
    color: #FF6B2C;
    border: 1px solid #FFCBA4;
}

.btn-unban:hover {
    background: #FF6B2C;
    color: #FFFFFF;
}

.btn-approve {
    background: #D1FAE5;
    color: #047857;
    border: 1px solid #A7F3D0;
}

.btn-approve:hover {
    background: #A7F3D0;
}

.btn-reject {
    background: #FEE2E2;
    color: #B91C1C;
    border: 1px solid #FECACA;
}

.btn-reject:hover {
    background: #FECACA;
}

.pagination-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-top: 1px solid #E5E7EB;
}

.pagination-info {
    font-size: 0.85rem;
    color: #6B7280;
}

.pagination-links {
    display: flex;
    gap: 0.375rem;
}

.page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 0.625rem;
    border-radius: 8px;
    background: #FFFFFF;
    border: 1px solid #E5E7EB;
    color: #4B5563;
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.2s;
}

.page-btn:hover {
    background: #F3F4F6;
    border-color: #D1D5DB;
}

.page-btn.active {
    background: #FF6B2C;
    border-color: #FF6B2C;
    color: #FFFFFF;
}

.page-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.empty-state {
    padding: 3rem;
    text-align: center;
    color: #9CA3AF;
}

@media (max-width: 768px) {
    .filter-form { flex-direction: column; align-items: stretch; }
    .search-input, .filter-select, .date-input { width: 100%; }
    .users-table { display: block; overflow-x: auto; }
}

.drawer-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s;
}

.drawer-overlay.open {
    opacity: 1;
    visibility: visible;
}

.customer-drawer {
    position: fixed;
    top: 0;
    left: 0;
    width: 420px;
    max-width: 90vw;
    height: 100vh;
    background: #F7F7F7;
    transform: translateX(-100%);
    transition: transform 0.3s ease;
    overflow-y: auto;
    box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
}

.drawer-overlay.open .customer-drawer {
    transform: translateX(0);
}

.drawer-close {
    position: absolute;
    top: 1rem;
    left: 1rem;
    width: 32px;
    height: 32px;
    border: none;
    background: #FFFFFF;
    border-radius: 50%;
    font-size: 1.25rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6B7280;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    z-index: 10;
}

.drawer-close:hover {
    background: #F3F4F6;
}

.drawer-content {
    padding: 1.5rem;
    padding-top: 4rem;
}

.drawer-header {
    text-align: center;
    margin-bottom: 1.5rem;
}

.drawer-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 0.75rem;
    border: 3px solid #FFFFFF;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.drawer-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1F2937;
    margin-bottom: 0.5rem;
}

.drawer-section {
    background: #FFFFFF;
    border-radius: 16px;
    padding: 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.drawer-section h3 {
    font-size: 0.85rem;
    font-weight: 600;
    color: #6B7280;
    margin-bottom: 0.75rem;
    text-transform: uppercase;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 0.625rem;
    background: #F9FAFB;
    border-radius: 10px;
    margin-bottom: 0.5rem;
}

.info-row:last-child {
    margin-bottom: 0;
}

.info-label {
    font-size: 0.8rem;
    color: #6B7280;
}

.info-value {
    font-size: 0.85rem;
    font-weight: 600;
    color: #1F2937;
}

.address-card {
    background: #F9FAFB;
    border: 1px solid #E5E7EB;
    border-radius: 12px;
    padding: 0.875rem;
    margin-bottom: 0.75rem;
}

.address-card:last-child {
    margin-bottom: 0;
}

.address-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.address-title {
    font-weight: 600;
    color: #1F2937;
}

.default-badge {
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
    background: #D1FAE5;
    color: #059669;
    border-radius: 12px;
    font-weight: 600;
}

.address-text {
    font-size: 0.8rem;
    color: #4B5563;
    line-height: 1.5;
}

.order-card {
    background: #F9FAFB;
    border: 1px solid #E5E7EB;
    border-radius: 12px;
    padding: 0.875rem;
    margin-bottom: 0.75rem;
}

.order-card:last-child {
    margin-bottom: 0;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.order-id {
    font-family: monospace;
    font-size: 0.8rem;
    color: #374151;
}

.order-details {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
    color: #6B7280;
    margin-bottom: 0.375rem;
}

.payment-status {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 8px;
    display: inline-block;
}

.payment-status.paid {
    background: #D1FAE5;
    color: #059669;
}

.payment-status.unpaid {
    background: #FEE2E2;
    color: #DC2626;
}

.empty-text {
    text-align: center;
    color: #9CA3AF;
    font-size: 0.85rem;
    padding: 1rem;
}

.btn-ban-action {
    width: 100%;
    padding: 0.75rem;
    border: none;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    margin-top: 0.75rem;
    transition: all 0.2s;
}

.btn-ban-action.ban {
    background: #FEE2E2;
    color: #DC2626;
}

.btn-ban-action.ban:hover {
    background: #FECACA;
}

.btn-ban-action.unban {
    background: #FFE8DC;
    color: #FF6B2C;
}

.btn-ban-action.unban:hover {
    background: #FF6B2C;
    color: #FFFFFF;
}

.confirm-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1100;
    align-items: center;
    justify-content: center;
}

.confirm-modal.show {
    display: flex;
}

.confirm-modal-content {
    background: #FFFFFF;
    border-radius: 16px;
    width: 100%;
    max-width: 420px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    overflow: hidden;
}

.confirm-modal-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #E5E7EB;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.confirm-modal-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1F2937;
}

.confirm-modal-close {
    border: none;
    background: transparent;
    color: #6B7280;
    font-size: 1.2rem;
    cursor: pointer;
}

.confirm-modal-body {
    padding: 1.25rem;
    color: #4B5563;
    font-size: 0.9rem;
}

.confirm-modal-footer {
    padding: 0.9rem 1.25rem;
    border-top: 1px solid #E5E7EB;
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.confirm-btn {
    border: none;
    border-radius: 10px;
    padding: 0.5rem 0.9rem;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
}

.confirm-btn.cancel {
    background: #F3F4F6;
    color: #374151;
}

.confirm-btn.confirm {
    background: #DC2626;
    color: #FFFFFF;
}

.confirm-btn.confirm.unblock {
    background: #FF6B2C;
}
</style>
@endsection

@section('content')
<div class="users-page">
    <div class="page-header">
        <h1>إدارة العملاء</h1>
        <p>عرض ومتابعة كل أنواع الحسابات في المنصة</p>
    </div>

    <div class="filter-bar">
        <form method="GET" action="{{ route('admin.users') }}" class="filter-form">
            <input type="text" name="search" class="search-input" placeholder="البحث برقم الحساب أو البريد الإلكتروني..." value="{{ request()->get('search') }}">
            <select name="role" class="filter-select">
                <option value="">الكل</option>
                <option value="customer" {{ request()->get('role') == 'customer' ? 'selected' : '' }}>العملاء</option>
                <option value="driver" {{ request()->get('role') == 'driver' ? 'selected' : '' }}>السائقين</option>
                <option value="restaurant" {{ request()->get('role') == 'restaurant' ? 'selected' : '' }}>المطاعم</option>
                <option value="admin" {{ request()->get('role') == 'admin' ? 'selected' : '' }}>المدراء</option>
            </select>
            <select name="approval_status" class="filter-select">
                <option value="">كل حالات السائقين</option>
                <option value="pending" {{ request()->get('approval_status') == 'pending' ? 'selected' : '' }}>بانتظار الموافقة</option>
                <option value="approved" {{ request()->get('approval_status') == 'approved' ? 'selected' : '' }}>موافق عليه</option>
                <option value="rejected" {{ request()->get('approval_status') == 'rejected' ? 'selected' : '' }}>مرفوض</option>
            </select>
            <input type="date" name="date_from" class="date-input" placeholder="من" value="{{ request()->get('date_from') }}">
            <input type="date" name="date_to" class="date-input" placeholder="إلى" value="{{ request()->get('date_to') }}">
            <button type="submit" class="btn-filter">
                <i class="fas fa-filter"></i> بحث
            </button>
        </form>
    </div>

    <div class="table-card">
        <table class="users-table">
            <thead>
                <tr>
                    <th>معلومات الحساب</th>
                    <th>تاريخ التسجيل</th>
                    <th>إحصائيات الشراء</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <div class="user-info">
                            <img src="{{ $user->avatar ? '/storage/' . $user->avatar : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=FFE8DC&color=FF6B2C&size=44' }}" alt="{{ $user->name }}" class="user-avatar">
                            <div class="user-details">
                                <h4>{{ $user->name }}</h4>
                                <p>{{ $user->email }}</p>
                                <p>{{ $roles[$user->account_type] ?? ucfirst($user->account_type) }}</p>
                                @if($user->account_type === 'driver')
                                    <span class="driver-meta">
                                        {{ $user->national_id ? 'هوية: '.$user->national_id : 'هوية غير مسجلة' }}
                                        @if($user->vehicle_type)
                                            · {{ str_replace('_', ' ', $user->vehicle_type) }}
                                        @endif
                                    </span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="date-cell">
                            <i class="far fa-calendar"></i>
                            {{ \Illuminate\Support\Carbon::parse($user->created_at)->format('Y-m-d H:i') }}
                        </div>
                    </td>
                    <td>
                        <div class="stats-cell">
                            <span class="order-count">
                                <i class="fas fa-shopping-bag"></i>
                                {{ $user->orders_count }} طلب
                            </span>
                            <span class="total-spent">{{ config('app.currency_symbol', '₪') }}{{ number_format($user->total_spent, 2) }}</span>
                        </div>
                    </td>
                    <td>
                        <span class="status-badge {{ $user->is_active ? 'active' : 'banned' }}">
                            {{ $user->is_active ? 'نشط' : 'محظور' }}
                        </span>
                        @if($user->account_type === 'driver')
                            @php($approvalStatus = $user->approval_status ?? 'approved')
                            <span class="status-badge {{ $approvalStatus === 'approved' ? 'active' : ($approvalStatus === 'rejected' ? 'rejected' : 'pending') }}" style="margin-top: .35rem;">
                                {{ $approvalStatus === 'approved' ? 'موافق عليه' : ($approvalStatus === 'rejected' ? 'مرفوض' : 'بانتظار الموافقة') }}
                            </span>
                        @endif
                    </td>
                    <td>
                        <div class="action-btns">
                            <button class="btn-action btn-details" onclick="openDrawer({{ $user->id }}, '{{ $user->account_type }}')">التفاصيل</button>
                            @if($user->account_type === 'driver' && ($user->approval_status ?? 'approved') !== 'approved')
                                <button class="btn-action btn-approve" onclick="driverApproval({{ $user->id }}, 'approve')">موافقة</button>
                            @endif
                            @if($user->account_type === 'driver' && ($user->approval_status ?? 'approved') !== 'rejected')
                                <button class="btn-action btn-reject" onclick="driverApproval({{ $user->id }}, 'reject')">رفض</button>
                            @endif
                            @if($user->account_type !== 'admin')
                                <button class="btn-action {{ $user->is_active ? 'btn-ban' : 'btn-unban' }}" onclick="quickToggle({{ $user->id }}, '{{ $user->account_type }}', {{ $user->is_active ? 'true' : 'false' }})">
                                    {{ $user->is_active ? 'حظر' : 'إلغاء حظر' }}
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="empty-state">لا توجد حسابات عملاء</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($users->hasPages())
        <div class="pagination-bar">
            <span class="pagination-info">إجمالي النتائج: {{ $users->total() }} حساب</span>
            <div class="pagination-links">
                @if($users->previousPageUrl())
                <a href="{{ $users->previousPageUrl() }}" class="page-btn">التالي</a>
                @endif

                @for($i = 1; $i <= $users->lastPage(); $i++)
                <a href="{{ $users->url($i) }}" class="page-btn {{ $users->currentPage() == $i ? 'active' : '' }}">{{ $i }}</a>
                @endfor

                @if($users->nextPageUrl())
                <a href="{{ $users->nextPageUrl() }}" class="page-btn">السابق</a>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

<div id="customerDrawer" class="drawer-overlay" onclick="closeDrawer(event)">
    <div class="customer-drawer" onclick="event.stopPropagation()">
        <button class="drawer-close" onclick="closeDrawer()">&times;</button>
        <div class="drawer-content">
            <div class="drawer-header">
                <img src="" alt="" class="drawer-avatar" id="drawerAvatar">
                <h2 class="drawer-name" id="drawerName"></h2>
                <span class="status-badge" id="drawerStatus"></span>
                <button class="btn-ban-action" id="banBtn" onclick="toggleUserStatus()"></button>
            </div>

            <div class="drawer-section">
                <h3>البيانات الأساسية</h3>
                <div class="info-row">
                    <span class="info-label">البريد الإلكتروني</span>
                    <span class="info-value" id="drawerEmail"></span>
                </div>
                <div class="info-row">
                    <span class="info-label">رقم الحساب</span>
                    <span class="info-value" id="drawerId"></span>
                </div>
                <div class="info-row">
                    <span class="info-label">تاريخ الانضمام</span>
                    <span class="info-value" id="drawerJoined"></span>
                </div>
                <div id="driverApprovalDetails" style="display: none;">
                    <div class="info-row">
                        <span class="info-label">حالة طلب السائق</span>
                        <span class="info-value" id="drawerDriverApproval"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">رقم الهوية</span>
                        <span class="info-value" id="drawerNationalId"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">نوع المركبة</span>
                        <span class="info-value" id="drawerVehicleType"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">رقم اللوحة</span>
                        <span class="info-value" id="drawerVehiclePlate"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">المدينة</span>
                        <span class="info-value" id="drawerCity"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">رقم الطوارئ</span>
                        <span class="info-value" id="drawerEmergencyContact"></span>
                    </div>
                </div>
            </div>

            <div class="drawer-section">
                <h3>العناوين المحفوظة</h3>
                <div id="drawerAddresses"></div>
            </div>

            <div class="drawer-section">
                <h3>سجل الطلبات</h3>
                <div id="drawerOrders"></div>
            </div>
        </div>
    </div>
</div>
</div>

<div class="confirm-modal" id="statusConfirmModal" onclick="closeStatusConfirm(event)">
    <div class="confirm-modal-content" onclick="event.stopPropagation()">
        <div class="confirm-modal-header">
            <span class="confirm-modal-title">تأكيد الإجراء</span>
            <button class="confirm-modal-close" onclick="closeStatusConfirm()">&times;</button>
        </div>
        <div class="confirm-modal-body" id="statusConfirmMessage"></div>
        <div class="confirm-modal-footer">
            <button type="button" class="confirm-btn cancel" onclick="closeStatusConfirm()">إلغاء</button>
            <button type="button" class="confirm-btn confirm" id="statusConfirmAction" onclick="submitConfirmedToggle()">تأكيد</button>
        </div>
    </div>
</div>

<script>
let currentUserId = null;
let currentUserType = null;
let currentUserIsActive = true;
const currencySymbol = @json(config('app.currency_symbol', '₪'));
let pendingToggle = null;

function openDrawer(userId, accountType = 'customer') {
    currentUserId = userId;
    currentUserType = accountType;
    fetch('/admin/users/' + userId + '?type=' + encodeURIComponent(accountType))
        .then(res => res.json())
        .then(data => {
            document.getElementById('drawerAvatar').src = data.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(data.name) + '&background=FFE8DC&color=FF6B2C&size=80';
            document.getElementById('drawerName').textContent = data.name;
            document.getElementById('drawerEmail').textContent = data.email;
            document.getElementById('drawerId').textContent = '#' + data.id;
            document.getElementById('drawerJoined').textContent = data.created_at;
            const driverDetails = document.getElementById('driverApprovalDetails');
            if (data.account_type === 'driver') {
                const approvalLabels = {
                    pending: 'بانتظار الموافقة',
                    approved: 'موافق عليه',
                    rejected: 'مرفوض'
                };
                driverDetails.style.display = 'block';
                document.getElementById('drawerDriverApproval').textContent = approvalLabels[data.approval_status] || data.approval_status || '-';
                document.getElementById('drawerNationalId').textContent = data.national_id || '-';
                document.getElementById('drawerVehicleType').textContent = (data.vehicle_type || '-').replaceAll('_', ' ');
                document.getElementById('drawerVehiclePlate').textContent = data.vehicle_plate_number || '-';
                document.getElementById('drawerCity').textContent = data.city || '-';
                document.getElementById('drawerEmergencyContact').textContent = data.emergency_contact_number || '-';
            } else {
                driverDetails.style.display = 'none';
            }
            
            const statusEl = document.getElementById('drawerStatus');
            if (data.account_type === 'admin') {
                statusEl.textContent = 'نشط';
                statusEl.className = 'status-badge active';
                document.getElementById('banBtn').style.display = 'none';
            } else if (data.is_active) {
                currentUserIsActive = true;
                statusEl.textContent = 'نشط';
                statusEl.className = 'status-badge active';
                document.getElementById('banBtn').textContent = 'حظر هذا الحساب';
                document.getElementById('banBtn').className = 'btn-ban-action ban';
                document.getElementById('banBtn').style.display = 'block';
            } else {
                currentUserIsActive = false;
                statusEl.textContent = 'محظور';
                statusEl.className = 'status-badge banned';
                document.getElementById('banBtn').textContent = 'إلغاء الحظر';
                document.getElementById('banBtn').className = 'btn-ban-action unban';
                document.getElementById('banBtn').style.display = 'block';
            }

            const addrContainer = document.getElementById('drawerAddresses');
            if (data.addresses && data.addresses.length) {
                addrContainer.innerHTML = data.addresses.map(addr => `
                    <div class="address-card">
                        <div class="address-header">
                            <span class="address-title">${addr.label}</span>
                            ${addr.is_default ? '<span class="default-badge">افتراضي</span>' : ''}
                        </div>
                        <p class="address-text">${addr.address}</p>
                    </div>
                `).join('');
            } else {
                addrContainer.innerHTML = '<p class="empty-text">لا توجد عناوين محفوظة</p>';
            }

            const orderContainer = document.getElementById('drawerOrders');
            if (data.orders && data.orders.length) {
                orderContainer.innerHTML = data.orders.map(order => `
                    <div class="order-card">
                        <div class="order-header">
                            <span class="order-id">#${order.order_number}</span>
                            <span class="status-badge ${order.status === 'completed' ? 'active' : order.status === 'delivering' ? 'delivering' : 'pending'}">
                                ${order.status === 'pending' ? 'قيد الانتظار' : order.status === 'accepted' ? 'مقبول' : order.status === 'preparing' ? 'قيد التجهيز' : order.status === 'delivering' ? 'في الطريق' : order.status === 'completed' ? 'مكتمل' : 'ملغى'}
                            </span>
                        </div>
                        <div class="order-details">
                            <span>${order.created_at}</span>
                            <span>${currencySymbol}${order.total_price}</span>
                            <span>${order.items_count} عناصر</span>
                        </div>
                        <div class="payment-status ${order.is_paid ? 'paid' : 'unpaid'}">
                            ${order.is_paid ? 'مدفوع' : 'غير مدفوع'}
                        </div>
                    </div>
                `).join('');
            } else {
                orderContainer.innerHTML = '<p class="empty-text">لا توجد طلبات سابقة</p>';
            }

            document.getElementById('customerDrawer').classList.add('open');
        });
}

function closeDrawer(e) {
    if (e && e.target !== e.currentTarget) return;
    document.getElementById('customerDrawer').classList.remove('open');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeDrawer();
});

function toggleUserStatus() {
    if (!currentUserId) return;
    openStatusConfirm(currentUserId, currentUserType || 'customer', currentUserIsActive);
}

function openStatusConfirm(userId, accountType, isActive) {
    pendingToggle = { userId, accountType, isActive };
    const message = isActive
        ? 'هل أنت متأكد من حظر هذا الحساب؟'
        : 'هل أنت متأكد من إلغاء حظر هذا الحساب؟';
    document.getElementById('statusConfirmMessage').textContent = message;

    const actionBtn = document.getElementById('statusConfirmAction');
    actionBtn.textContent = isActive ? 'تأكيد الحظر' : 'تأكيد إلغاء الحظر';
    actionBtn.className = 'confirm-btn confirm' + (isActive ? '' : ' unblock');

    document.getElementById('statusConfirmModal').classList.add('show');
}

function closeStatusConfirm(e) {
    if (e && e.target !== e.currentTarget) return;
    document.getElementById('statusConfirmModal').classList.remove('show');
    pendingToggle = null;
}

function submitConfirmedToggle() {
    if (!pendingToggle) return;

    fetch('/admin/users/' + pendingToggle.userId + '/ban', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ type: pendingToggle.accountType || 'customer' })
    })
    .then(async res => {
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) {
            throw new Error(data.message || 'تعذر تحديث حالة الحساب');
        }
        return data;
    })
    .then(data => {
        closeStatusConfirm();
        toastr.success(data.message || 'تم تحديث الحالة');
        closeDrawer();
        window.location.reload();
    })
    .catch(err => {
        toastr.error(err.message || 'حدث خطأ');
    });
}
</script>
<script>
function quickToggle(userId, accountType = 'customer', isActive = true) {
    openStatusConfirm(userId, accountType, isActive);
}

function driverApproval(driverId, action) {
    const label = action === 'approve' ? 'الموافقة على السائق' : 'رفض طلب السائق';
    if (!confirm('تأكيد ' + label + '؟')) return;

    fetch('/admin/drivers/' + driverId + '/' + action, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(async res => {
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) {
            throw new Error(data.message || 'تعذر تحديث حالة السائق');
        }
        return data;
    })
    .then(data => {
        toastr.success(data.message || 'تم تحديث حالة السائق');
        window.location.reload();
    })
    .catch(err => {
        toastr.error(err.message || 'حدث خطأ');
    });
}
</script>
@endsection
