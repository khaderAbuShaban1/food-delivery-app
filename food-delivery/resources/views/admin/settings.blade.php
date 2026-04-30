@extends('layouts.admin')

@section('title', 'الإعدادات المتقدمة')

@section('styles')
<style>
    .settings-shell { display: grid; grid-template-columns: 240px 1fr; gap: 1rem; align-items: start; }
    .settings-nav { position: sticky; top: 1rem; background: #fff; border-radius: 16px; box-shadow: var(--shadow); padding: .85rem; }
    .settings-nav .nav-link { border-radius: 10px; color: var(--text-muted); font-weight: 600; margin-bottom: .35rem; text-align: right; }
    .settings-nav .nav-link.active { background: var(--primary-muted); color: var(--primary); }
    .card-box { background: #fff; border-radius: 16px; box-shadow: var(--shadow); padding: 1rem; margin-bottom: 1rem; }
    .card-title { font-size: 1rem; font-weight: 700; margin-bottom: .25rem; color: var(--text-dark); }
    .card-subtitle { font-size: .8rem; color: var(--text-muted); margin-bottom: 1rem; }
    .critical-toggle { border: 1px solid #FECACA; background: #FFF7F7; border-radius: 12px; padding: .75rem; }
    @media (max-width: 992px) { .settings-shell { grid-template-columns: 1fr; } .settings-nav { position: static; } }
</style>
@endsection

@section('content')
<div class="header">
    <h1 class="page-title">الإعدادات المتقدمة</h1>
    <p class="page-subtitle">تحكم كامل في النظام والمنصة والدفع والأمان</p>
</div>

@if($errors->any())
    <div class="alert-error mb-3"><i class="fas fa-exclamation-triangle me-2"></i>يرجى مراجعة الحقول وإعادة المحاولة.</div>
@endif

<div class="settings-shell">
    <div class="settings-nav">
        <div class="nav flex-column nav-pills">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#general-settings" type="button">الإعدادات العامة</button>
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#platform-settings" type="button">التحكم بالمنصة</button>
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#payment-settings" type="button">إعدادات الدفع</button>
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#notification-settings" type="button">الإشعارات</button>
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#security-settings" type="button">الأمان</button>
        </div>
    </div>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="general-settings">
            <div class="card-box">
                <h3 class="card-title">General System Settings</h3>
                <p class="card-subtitle">اسم المنصة والهوية واللغة الافتراضية</p>
                <form action="{{ route('admin.settings.general') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Site Name</label>
                            <input type="text" name="site_name" class="form-control" value="{{ old('site_name', $settings['general']['site_name'] ?? 'Food Delivery') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Default Language</label>
                            <select name="default_language" class="form-select">
                                <option value="ar" {{ old('default_language', $settings['general']['default_language'] ?? 'ar') === 'ar' ? 'selected' : '' }}>Arabic</option>
                                <option value="en" {{ old('default_language', $settings['general']['default_language'] ?? 'ar') === 'en' ? 'selected' : '' }}>English</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Logo</label>
                            <input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Favicon</label>
                            <input type="file" name="favicon" class="form-control" accept=".jpg,.jpeg,.png,.ico,.webp">
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3" type="submit">حفظ الإعدادات العامة</button>
                </form>
            </div>
        </div>

        <div class="tab-pane fade" id="platform-settings">
            <div class="card-box">
                <h3 class="card-title">Platform Control</h3>
                <p class="card-subtitle">تعطيل/تفعيل التسجيل والمطاعم والطلبات وتشغيل المنصة بالكامل</p>
                <form action="{{ route('admin.settings.platform') }}" method="POST" onsubmit="return confirm('هل تريد تطبيق إعدادات المنصة العامة؟')">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        @php $platform = $settings['platform'] ?? []; @endphp
                        <div class="col-md-6 critical-toggle">
                            <label class="form-label">Enable Registrations</label>
                            <select name="registration_enabled" class="form-select">
                                <option value="1" {{ (int)($platform['registration_enabled'] ?? 1) === 1 ? 'selected' : '' }}>Enabled</option>
                                <option value="0" {{ (int)($platform['registration_enabled'] ?? 1) === 0 ? 'selected' : '' }}>Disabled</option>
                            </select>
                        </div>
                        <div class="col-md-6 critical-toggle">
                            <label class="form-label">Enable Restaurants</label>
                            <select name="restaurants_enabled" class="form-select">
                                <option value="1" {{ (int)($platform['restaurants_enabled'] ?? 1) === 1 ? 'selected' : '' }}>Enabled</option>
                                <option value="0" {{ (int)($platform['restaurants_enabled'] ?? 1) === 0 ? 'selected' : '' }}>Disabled</option>
                            </select>
                        </div>
                        <div class="col-md-6 critical-toggle">
                            <label class="form-label">Orders System</label>
                            <select name="orders_enabled" class="form-select">
                                <option value="1" {{ (int)($platform['orders_enabled'] ?? 1) === 1 ? 'selected' : '' }}>Enabled</option>
                                <option value="0" {{ (int)($platform['orders_enabled'] ?? 1) === 0 ? 'selected' : '' }}>Disabled (Maintenance)</option>
                            </select>
                        </div>
                        <div class="col-md-6 critical-toggle">
                            <label class="form-label">Global Platform Open</label>
                            <select name="platform_open" class="form-select">
                                <option value="1" {{ (int)($platform['platform_open'] ?? 1) === 1 ? 'selected' : '' }}>Open</option>
                                <option value="0" {{ (int)($platform['platform_open'] ?? 1) === 0 ? 'selected' : '' }}>Closed</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3" type="submit">حفظ إعدادات المنصة</button>
                </form>
            </div>
        </div>

        <div class="tab-pane fade" id="payment-settings">
            <div class="card-box">
                <h3 class="card-title">Payment Settings</h3>
                <p class="card-subtitle">إعداد طرق الدفع وإعدادات المعاملات</p>
                @php $payment = $settings['payment'] ?? []; @endphp
                <form action="{{ route('admin.settings.payment') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Cash</label>
                            <select name="cash_enabled" class="form-select">
                                <option value="1" {{ (int)($payment['cash_enabled'] ?? 1) === 1 ? 'selected' : '' }}>Enabled</option>
                                <option value="0" {{ (int)($payment['cash_enabled'] ?? 1) === 0 ? 'selected' : '' }}>Disabled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Online</label>
                            <select name="online_enabled" class="form-select">
                                <option value="1" {{ (int)($payment['online_enabled'] ?? 0) === 1 ? 'selected' : '' }}>Enabled</option>
                                <option value="0" {{ (int)($payment['online_enabled'] ?? 0) === 0 ? 'selected' : '' }}>Disabled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Transaction Fee (%)</label>
                            <input type="number" min="0" max="100" step="0.01" name="transaction_fee_percent" class="form-control" value="{{ old('transaction_fee_percent', $payment['transaction_fee_percent'] ?? 0) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Auto Capture</label>
                            <select name="auto_capture" class="form-select">
                                <option value="1" {{ (int)($payment['auto_capture'] ?? 0) === 1 ? 'selected' : '' }}>Enabled</option>
                                <option value="0" {{ (int)($payment['auto_capture'] ?? 0) === 0 ? 'selected' : '' }}>Disabled</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3" type="submit">حفظ إعدادات الدفع</button>
                </form>
            </div>
        </div>

        <div class="tab-pane fade" id="notification-settings">
            <div class="card-box">
                <h3 class="card-title">Notification Settings</h3>
                <p class="card-subtitle">التحكم في قنوات وأنواع الإشعارات</p>
                @php $n = $settings['notifications'] ?? []; @endphp
                <form action="{{ route('admin.settings.notifications') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Email</label><select name="email_enabled" class="form-select"><option value="1" {{ (int)($n['email_enabled'] ?? 1) === 1 ? 'selected' : '' }}>Enabled</option><option value="0" {{ (int)($n['email_enabled'] ?? 1) === 0 ? 'selected' : '' }}>Disabled</option></select></div>
                        <div class="col-md-4"><label class="form-label">SMS</label><select name="sms_enabled" class="form-select"><option value="1" {{ (int)($n['sms_enabled'] ?? 0) === 1 ? 'selected' : '' }}>Enabled</option><option value="0" {{ (int)($n['sms_enabled'] ?? 0) === 0 ? 'selected' : '' }}>Disabled</option></select></div>
                        <div class="col-md-4"><label class="form-label">Push</label><select name="push_enabled" class="form-select"><option value="1" {{ (int)($n['push_enabled'] ?? 0) === 1 ? 'selected' : '' }}>Enabled</option><option value="0" {{ (int)($n['push_enabled'] ?? 0) === 0 ? 'selected' : '' }}>Disabled</option></select></div>
                        <div class="col-md-4"><label class="form-label">New Order Notifications</label><select name="new_order_notifications" class="form-select"><option value="1" {{ (int)($n['new_order_notifications'] ?? 1) === 1 ? 'selected' : '' }}>Enabled</option><option value="0" {{ (int)($n['new_order_notifications'] ?? 1) === 0 ? 'selected' : '' }}>Disabled</option></select></div>
                        <div class="col-md-4"><label class="form-label">Status Updates</label><select name="status_update_notifications" class="form-select"><option value="1" {{ (int)($n['status_update_notifications'] ?? 1) === 1 ? 'selected' : '' }}>Enabled</option><option value="0" {{ (int)($n['status_update_notifications'] ?? 1) === 0 ? 'selected' : '' }}>Disabled</option></select></div>
                        <div class="col-md-4"><label class="form-label">Marketing</label><select name="marketing_notifications" class="form-select"><option value="1" {{ (int)($n['marketing_notifications'] ?? 0) === 1 ? 'selected' : '' }}>Enabled</option><option value="0" {{ (int)($n['marketing_notifications'] ?? 0) === 0 ? 'selected' : '' }}>Disabled</option></select></div>
                    </div>
                    <button class="btn btn-primary mt-3" type="submit">حفظ إعدادات الإشعارات</button>
                </form>
            </div>
        </div>

        <div class="tab-pane fade" id="security-settings">
            <div class="card-box">
                <h3 class="card-title">Security Settings</h3>
                <p class="card-subtitle">سياسات كلمة المرور والجلسة ومحاولات تسجيل الدخول</p>
                @php $s = $settings['security'] ?? []; @endphp
                <form action="{{ route('admin.settings.security') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Password Min Length</label>
                            <input type="number" min="6" max="32" class="form-control" name="password_min_length" value="{{ old('password_min_length', $s['password_min_length'] ?? 8) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Require Complexity</label>
                            <select name="password_require_complexity" class="form-select">
                                <option value="1" {{ (int)($s['password_require_complexity'] ?? 0) === 1 ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ (int)($s['password_require_complexity'] ?? 0) === 0 ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Session Timeout (minutes)</label>
                            <input type="number" min="5" max="1440" class="form-control" name="session_timeout_minutes" value="{{ old('session_timeout_minutes', $s['session_timeout_minutes'] ?? 120) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Login Attempt Limit</label>
                            <input type="number" min="3" max="20" class="form-control" name="login_attempt_limit" value="{{ old('login_attempt_limit', $s['login_attempt_limit'] ?? 5) }}">
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3" type="submit">حفظ إعدادات الأمان</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const activeTab = @json(session('active_admin_settings_tab'));
    if (!activeTab) return;
    const trigger = document.querySelector('.settings-nav [data-bs-target="#' + activeTab + '"]');
    if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
});
</script>
@endsection
