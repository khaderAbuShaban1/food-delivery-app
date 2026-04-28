@extends('layouts.admin')

@section('title', 'إدارة العروض')

@section('content')
<div class="header">
    <h1 class="page-title">إدارة العروض</h1>
    <p class="page-subtitle">إنشاء ومتابعة العروض الخاصة</p>
</div>

<div class="stat-card text-center p-5">
    <div class="stat-icon orange mx-auto mb-3" style="width: 64px; height: 64px;">
        <i class="bi bi-gift"></i>
    </div>
    <div class="page-title mb-2">قريباً</div>
    <p class="text-muted">سيتم إضافة قسم العروض بشكل قريب</p>
</div>
@endsection