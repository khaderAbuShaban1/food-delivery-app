@extends('layouts.restaurant')

@section('styles')
<style>
    .settings-grid { display: grid; grid-template-columns: 260px 1fr; gap: 1rem; align-items: start; }
    .settings-nav { position: sticky; top: 1rem; }
    .settings-nav .nav-link {
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-md);
        color: var(--text-secondary);
        margin-bottom: .5rem;
        padding: .75rem 1rem;
        font-weight: 600;
    }
    .settings-nav .nav-link.active {
        background: linear-gradient(135deg, rgba(249, 115, 22, 0.1), rgba(249, 115, 22, 0.06));
        border-color: rgba(249, 115, 22, 0.2);
        color: var(--accent-primary);
    }
    .settings-card { margin-bottom: 1rem; }
    .settings-card .card-header {
        border-bottom: 1px solid var(--border-subtle);
        background: transparent;
        padding: 1rem 1.25rem;
    }
    .settings-card .card-body { padding: 1.25rem; }
    .section-title { margin: 0; font-weight: 700; font-size: 1rem; }
    .section-subtitle { color: var(--text-muted); font-size: .8rem; margin-top: .2rem; }
    .day-row { border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: .75rem; margin-bottom: .5rem; }
    .preview-logo { width: 88px; height: 88px; border-radius: 14px; object-fit: cover; border: 1px solid var(--border-subtle); }
    @media (max-width: 992px) {
        .settings-grid { grid-template-columns: 1fr; }
        .settings-nav { position: static; display: flex; gap: .5rem; overflow-x: auto; padding-bottom: .5rem; }
        .settings-nav .nav-link { white-space: nowrap; margin-bottom: 0; }
    }
</style>
@endsection

@section('content')
<div class="mb-4">
    <h1 class="page-title">الإعدادات</h1>
    <p class="page-subtitle">إدارة بيانات المطعم وساعات العمل وخيارات الطلب والحساب</p>
</div>

@if ($errors->any())
    <div class="alert alert-danger mb-4">
        <i class="bi bi-exclamation-triangle me-2"></i>
        يرجى مراجعة الحقول المدخلة والمحاولة مرة أخرى.
    </div>
@endif

<div class="settings-grid">
    <div class="settings-nav">
        <div class="nav flex-column nav-pills" role="tablist">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#general-settings" type="button">معلومات عامة</button>
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#working-hours-settings" type="button">ساعات العمل</button>
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#order-settings" type="button">إعدادات الطلبات</button>
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#account-settings" type="button">إعدادات الحساب</button>
        </div>
    </div>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="general-settings">
            <div class="glass-card settings-card">
                <div class="card-header">
                    <h3 class="section-title">المعلومات العامة</h3>
                    <p class="section-subtitle">الاسم والوصف ووسائل التواصل والشعار</p>
                </div>
                <div class="card-body">
                    <form action="{{ route('restaurant.settings.general') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="settings_tab" value="general-settings">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">اسم المطعم</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $restaurant->name) }}" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">البريد الإلكتروني للتواصل</label>
                                <input type="email" class="form-control" value="{{ $restaurant->email }}" readonly disabled>
                                <small class="text-muted d-block mt-1">لا يمكن تعديل البريد الإلكتروني من لوحة المطعم.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">رقم الهاتف</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $restaurant->phone) }}">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">شعار / صورة المطعم</label>
                                <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                                @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">وصف المطعم</label>
                                <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', $restaurant->description) }}</textarea>
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            @if($restaurant->image)
                                <div class="col-12 d-flex align-items-center gap-3">
                                    <img src="{{ asset('storage/' . $restaurant->image) }}" alt="Logo" class="preview-logo">
                                    <small class="text-muted">الصورة الحالية</small>
                                </div>
                            @endif
                        </div>
                        <button type="submit" class="btn btn-orange mt-3">حفظ المعلومات العامة</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="working-hours-settings">
            <div class="glass-card settings-card">
                <div class="card-header">
                    <h3 class="section-title">ساعات العمل</h3>
                    <p class="section-subtitle">تفعيل/تعطيل الأيام وتحديد وقت الفتح والإغلاق</p>
                </div>
                <div class="card-body">
                    <form action="{{ route('restaurant.settings.working-hours') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="settings_tab" value="working-hours-settings">
                        @foreach($days as $dayKey => $dayLabel)
                            @php $dayData = $workingHours[$dayKey] ?? ['enabled' => false, 'open' => '09:00', 'close' => '22:00']; @endphp
                            <div class="day-row">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input type="hidden" name="working_hours[{{ $dayKey }}][enabled]" value="0">
                                            <input class="form-check-input" type="checkbox" value="1" name="working_hours[{{ $dayKey }}][enabled]" id="day_{{ $dayKey }}" {{ old("working_hours.$dayKey.enabled", $dayData['enabled']) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="day_{{ $dayKey }}">{{ $dayLabel }}</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label mb-1">وقت الفتح</label>
                                        <input type="time" name="working_hours[{{ $dayKey }}][open]" class="form-control" value="{{ old("working_hours.$dayKey.open", $dayData['open']) }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label mb-1">وقت الإغلاق</label>
                                        <input type="time" name="working_hours[{{ $dayKey }}][close]" class="form-control" value="{{ old("working_hours.$dayKey.close", $dayData['close']) }}" required>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <button type="submit" class="btn btn-orange mt-2">حفظ ساعات العمل</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="order-settings">
            <div class="glass-card settings-card">
                <div class="card-header">
                    <h3 class="section-title">إعدادات الطلبات</h3>
                    <p class="section-subtitle">التحكم في استقبال الطلبات والحد الأدنى والتوصيل</p>
                </div>
                <div class="card-body">
                    <form action="{{ route('restaurant.settings.orders') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="settings_tab" value="order-settings">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">حالة استقبال الطلبات</label>
                                <select name="is_open" class="form-select @error('is_open') is-invalid @enderror">
                                    <option value="1" {{ (string) old('is_open', (int) $restaurant->is_open) === '1' ? 'selected' : '' }}>مفتوح</option>
                                    <option value="0" {{ (string) old('is_open', (int) $restaurant->is_open) === '0' ? 'selected' : '' }}>مغلق</option>
                                </select>
                                @error('is_open') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">الحد الأدنى للطلب (اختياري)</label>
                                <input type="number" step="0.01" min="0" name="minimum_order_amount" class="form-control @error('minimum_order_amount') is-invalid @enderror" value="{{ old('minimum_order_amount', $restaurant->minimum_order_amount) }}">
                                @error('minimum_order_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">إتاحة التوصيل</label>
                                <select name="delivery_available" class="form-select @error('delivery_available') is-invalid @enderror">
                                    <option value="1" {{ (string) old('delivery_available', (int) ($restaurant->delivery_available ?? 1)) === '1' ? 'selected' : '' }}>متاح</option>
                                    <option value="0" {{ (string) old('delivery_available', (int) ($restaurant->delivery_available ?? 1)) === '0' ? 'selected' : '' }}>غير متاح</option>
                                </select>
                                @error('delivery_available') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <button type="submit" class="btn btn-orange mt-3">حفظ إعدادات الطلبات</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="account-settings">
            <div class="glass-card settings-card">
                <div class="card-header">
                    <h3 class="section-title">إعدادات الحساب</h3>
                    <p class="section-subtitle">تحديث بيانات تسجيل الدخول وتغيير كلمة المرور</p>
                </div>
                <div class="card-body">
                    <form action="{{ route('restaurant.settings.account') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="settings_tab" value="account-settings">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">البريد الإلكتروني لتسجيل الدخول</label>
                                <input type="email" class="form-control" value="{{ $restaurant->email }}" readonly disabled>
                                <small class="text-muted d-block mt-1">البريد الإلكتروني مقفل ولا يمكن تغييره.</small>
                            </div>
                            <div class="col-md-6"></div>
                            <div class="col-md-4">
                                <label class="form-label">كلمة المرور الحالية</label>
                                <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror">
                                @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">كلمة المرور الجديدة</label>
                                <input type="password" name="new_password" class="form-control @error('new_password') is-invalid @enderror">
                                @error('new_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">تأكيد كلمة المرور الجديدة</label>
                                <input type="password" name="new_password_confirmation" class="form-control">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-orange mt-3">حفظ إعدادات الحساب</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const activeTab = @json(session('active_settings_tab'));
        if (!activeTab) return;

        const trigger = document.querySelector('.settings-nav [data-bs-target="#' + activeTab + '"]');
        if (trigger) {
            bootstrap.Tab.getOrCreateInstance(trigger).show();
        }
    });
</script>
@endsection
