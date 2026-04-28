@extends('layouts.admin')

@section('title', 'لوحة المؤشرات')

@section('content')
<div class="header">
    <h1 class="page-title">لوحة المؤشرات</h1>
    <p class="page-subtitle">مرحباً! إليك ملخصاً لما يحدث اليوم</p>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-store"></i>
            </div>
            <div class="stat-value">{{ $stats['activeRestaurants'] }}</div>
            <div class="stat-label">المطاعم الفعالة</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-value">{{ $stats['activeCustomers'] }}</div>
            <div class="stat-label">العملاء النشطون</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-shekel-sign"></i>
            </div>
            <div class="stat-value">₪{{ number_format($stats['todayRevenue'], 2) }}</div>
            <div class="stat-label">الإيراد اليومي</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <div class="stat-value">{{ $stats['todayOrders'] }}</div>
            <div class="stat-label">طلبات اليوم</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <h2 class="section-title">إجراءات سريعة</h2>
    </div>
    <div class="col-md-6 col-lg-3">
        <a href="{{ route('admin.restaurants') }}" class="action-card">
            <div class="action-icon">
                <i class="fas fa-store-alt"></i>
            </div>
            <span class="action-text">إدارة المطاعم</span>
        </a>
    </div>
    <div class="col-md-6 col-lg-3">
        <a href="{{ route('admin.menu') }}" class="action-card">
            <div class="action-icon">
                <i class="fas fa-utensils"></i>
            </div>
            <span class="action-text">تحديث القوائم</span>
        </a>
    </div>
    <div class="col-md-6 col-lg-3">
        <a href="{{ route('admin.menu') }}" class="action-card">
            <div class="action-icon">
                <i class="fas fa-tags"></i>
            </div>
            <span class="action-text">إدارة التصنيفات</span>
        </a>
    </div>
    <div class="col-md-6 col-lg-3">
        <a href="{{ route('admin.offers') }}" class="action-card">
            <div class="action-icon">
                <i class="fas fa-gift"></i>
            </div>
            <span class="action-text">العروض النشطة</span>
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <h2 class="section-title">حالة الطلبات</h2>
    </div>
    <div class="col-md-4">
        <div class="status-card">
            <div class="status-header">
                <span class="status-label">قيد التجهيز</span>
                <span class="status-count">{{ $orderStats['preparing'] }}</span>
            </div>
            <div class="progress">
                <div class="progress-bar primary" style="width: {{ $progressPreparing }}%"></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="status-card">
            <div class="status-header">
                <span class="status-label">في الطريق</span>
                <span class="status-count">{{ $orderStats['delivering'] }}</span>
            </div>
            <div class="progress">
                <div class="progress-bar info" style="width: {{ $progressDelivering }}%"></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="status-card">
            <div class="status-header">
                <span class="status-label">مكتمل</span>
                <span class="status-count">{{ $orderStats['completed'] }}</span>
            </div>
            <div class="progress">
                <div class="progress-bar success" style="width: {{ $progressCompleted }}%"></div>
            </div>
        </div>
    </div>
</div>
@endsection