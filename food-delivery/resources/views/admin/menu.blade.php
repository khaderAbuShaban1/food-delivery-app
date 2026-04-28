@extends('layouts.admin')

@section('title', 'إدارة الأصناف')

@section('content')
<div class="header">
    <h1 class="page-title">إدارة الأصناف</h1>
    <p class="page-subtitle">إدارة قوائم المطاعم والأصناف</p>
</div>

<div class="data-card">
    <div class="data-card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>اسم الصنف</th>
                    <th>المطعم</th>
                    <th>السعر</th>
                    <th>الفئة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($menuItems as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->restaurant?->name ?? 'غير معروف' }}</td>
                    <td>₪{{ $item->price }}</td>
                    <td>{{ $item->category ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted p-4">لا توجد أصناف</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($menuItems->hasPages())
<div class="mt-4 d-flex justify-content-center">
    {!! $menuItems->links() !!}
</div>
@endif

<style>
.data-card { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
.data-card-body { padding: 0; }
.table { margin: 0; }
.table th { background: var(--bg页面); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); padding: 0.75rem 1rem; border: none; }
.table td { padding: 0.75rem 1rem; border-color: var(--border); vertical-align: middle; font-size: 0.9rem; }
.table tr:hover { background: var(--bg页面); }
</style>
@endsection