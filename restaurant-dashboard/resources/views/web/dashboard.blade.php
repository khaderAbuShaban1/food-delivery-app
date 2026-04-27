@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h1 class="page-title">لوحة التحكم</h1>
    <p class="page-subtitle">مرحباً، {{ session('user')['name'] ?? 'المطعم' }}</p>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="glass-card stat-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 56px; height: 56px; background: rgba(249, 115, 22, 0.15); border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-bag" style="font-size: 1.5rem; color: var(--accent-primary);"></i>
                </div>
                <div>
                    <p class="mb-0" style="color: var(--text-secondary); font-size: 0.85rem;">إجمالي الطلبات</p>
                    <h2 class="mb-0 stat-number" style="font-size: 2rem; color: var(--accent-primary);">{{ $ordersCount }}</h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="glass-card stat-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 56px; height: 56px; background: rgba(249, 115, 22, 0.15); border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-hourglass-split" style="font-size: 1.5rem; color: #F97316;"></i>
                </div>
                <div>
                    <p class="mb-0" style="color: var(--text-secondary); font-size: 0.85rem;">قيد الانتظار</p>
                    <h2 class="mb-0" style="font-size: 2rem; font-weight: 700; color: #F97316;">{{ $pendingOrders }}</h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="glass-card stat-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 56px; height: 56px; background: {{ $restaurant['is_open'] ? 'rgba(34, 197, 94, 0.15)' : 'rgba(239, 68, 68, 0.15)' }}; border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-toggle-on" style="font-size: 1.5rem; color: {{ $restaurant['is_open'] ? '#22C55E' : '#EF4444' }};"></i>
                </div>
                <div>
                    <p class="mb-0" style="color: var(--text-secondary); font-size: 0.85rem;">الحالة</p>
                    <h2 class="mb-0" style="font-size: 1.5rem; font-weight: 700; color: {{ $restaurant['is_open'] ? '#22C55E' : '#EF4444' }};">
                        {{ $restaurant['is_open'] ? 'مفتوح' : 'مغلق' }}
                    </h2>
                </div>
            </div>
            <form action="/dashboard/status/{{ $restaurant['id'] }}" method="POST" class="mt-3">
                @csrf
                @method('PATCH')
                <input type="hidden" name="is_open" value="{{ $restaurant['is_open'] ? 0 : 1 }}">
                <button type="submit" class="btn btn-orange btn-sm w-100">
                    {{ $restaurant['is_open'] ? 'إغلاق' : 'تفعيل' }}
                </button>
            </form>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <div class="col-md-6">
        <div class="glass-card p-4">
            <h5 class="mb-4" style="color: var(--text-secondary); font-weight: 600;">
                <i class="bi bi-graph-up me-2" style="color: var(--accent-primary);"></i>إحص��ئيات سريعة
            </h5>
            <div class="d-flex justify-content-between align-items-center py-3" style="border-bottom: 1px solid var(--border-subtle);">
                <span style="color: var(--text-secondary);">الأصناف في القائمة</span>
                <span class="badge" style="background: rgba(249, 115, 22, 0.15); color: var(--accent-primary);">{{ count($menuItems ?? []) }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-3">
                <span style="color: var(--text-secondary);">حالة المطعم</span>
                <span class="badge {{ $restaurant['is_open'] ? 'badge-open' : 'badge-closed' }}">
                    {{ $restaurant['is_open'] ? 'يقبل الطلبات' : 'لا يقبل' }}
                </span>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="glass-card p-4">
            <h5 class="mb-4" style="color: var(--text-secondary); font-weight: 600;">
                <i class="bi bi-lightning me-2" style="color: var(--accent-primary);"></i>إجراءات سريعة
            </h5>
            <div class="d-grid gap-3">
                <a href="/menu" class="btn btn-orange d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-plus-circle"></i>إضافة صنف جديد
                </a>
                <a href="/orders" class="btn btn-outline d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-bag"></i>عرض الطلبات
                </a>
            </div>
        </div>
    </div>
</div>
@endsection