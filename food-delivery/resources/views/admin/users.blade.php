@extends('layouts.admin')

@section('title', 'إدارة العملاء')

@section('content')
<div class="header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">إدارة العملاء</h1>
        <p class="page-subtitle">عرض وإدارة حسابات العملاء</p>
    </div>
</div>

<div class="filter-tabs mb-4">
    <a href="{{ route('admin.users') }}" class="filter-tab {{ !request()->get('role') || request()->get('role') == 'all' ? 'active' : '' }}">الكل</a>
    <a href="{{ route('admin.users', ['role' => 'customer']) }}" class="filter-tab {{ request()->get('role') == 'customer' ? 'active' : '' }}">العملاء</a>
    <a href="{{ route('admin.users', ['role' => 'driver']) }}" class="filter-tab {{ request()->get('role') == 'driver' ? 'active' : '' }}">السائقين</a>
    <a href="{{ route('admin.users', ['role' => 'restaurant']) }}" class="filter-tab {{ request()->get('role') == 'restaurant' ? 'active' : '' }}">المطاعم</a>
</div>

<style>
.filter-tabs {
    display: flex;
    gap: 0.5rem;
    padding: 0.5rem;
    background: var(--white);
    border-radius: 12px;
    flex-wrap: wrap;
    box-shadow: var(--shadow-sm);
}
.filter-tab {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.85rem;
    color: var(--text-muted);
    text-decoration: none;
    font-weight: 500;
}
.filter-tab:hover, .filter-tab.active {
    background: var(--primary-muted);
    color: var(--primary);
}
</style>

<div class="data-card">
    <div class="data-card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الاسم</th>
                    <th>البريد الإلكتروني</th>
                    <th>الهاتف</th>
                    <th>الدور</th>
                    <th>تاريخ التسجيل</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->phone ?? '-' }}</td>
                    <td>
                        <span class="badge-role badge-{{ $user->role }}">
                            @switch($user->role)
                                @case('customer')عميل@break
                                @case('driver')سائق@break
                                @case('restaurant')مطعم@break
                                @case('admin')مدير@break
                            @endswitch
                        </span>
                    </td>
                    <td>{{ $user->created_at->format('Y-m-d') }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted p-4">لا توجد مستخدمين</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($users->hasPages())
<div class="mt-4 d-flex justify-content-center">
    {!! $users->links() !!}
</div>
@endif

<style>
.data-card { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
.data-card-body { padding: 0; }
.table { margin: 0; }
.table th { background: var(--bg页面); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); padding: 0.75rem 1rem; border: none; }
.table td { padding: 0.75rem 1rem; border-color: var(--border); vertical-align: middle; font-size: 0.9rem; }
.table tr:hover { background: var(--bg页面); }
.badge-role { padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.75rem; }
.badge-customer { background: #E0F2FE; color: #0284C7; }
.badge-driver { background: #FEF3C7; color: #D97706; }
.badge-restaurant { background: #FCE7F3; color: #DB2777; }
</style>
@endsection