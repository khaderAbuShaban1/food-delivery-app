<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Requests\UpdateGeneralSystemSettingsRequest;
use App\Modules\Admin\Requests\UpdateNotificationSettingsRequest;
use App\Modules\Admin\Requests\UpdatePaymentSettingsRequest;
use App\Modules\Admin\Requests\UpdatePlatformSettingsRequest;
use App\Modules\Admin\Requests\UpdateSecuritySettingsRequest;
use App\Services\SystemSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly SystemSettingsService $settingsService)
    {
    }

    public function index(): View
    {
        $this->settingsService->seedDefaults();

        $settings = [
            'general' => $this->settingsService->all()['general'] ?? [],
            'platform' => $this->settingsService->all()['platform'] ?? [],
            'payment' => $this->settingsService->all()['payment'] ?? [],
            'notifications' => $this->settingsService->all()['notifications'] ?? [],
            'security' => $this->settingsService->all()['security'] ?? [],
        ];

        return view('admin::settings', compact('settings'));
    }

    public function updateGeneral(UpdateGeneralSystemSettingsRequest $request): RedirectResponse
    {
        $payload = $request->safe()->except(['logo', 'favicon']);

        if ($request->hasFile('logo')) {
            $payload['logo'] = $request->file('logo')->store('system-assets', 'public');
        }

        if ($request->hasFile('favicon')) {
            $payload['favicon'] = $request->file('favicon')->store('system-assets', 'public');
        }

        $this->settingsService->setGroup('general', $payload);

        return back()->with('success', 'تم تحديث الإعدادات العامة')->with('active_admin_settings_tab', 'general-settings');
    }

    public function updatePlatform(UpdatePlatformSettingsRequest $request): RedirectResponse
    {
        $this->settingsService->setGroup('platform', $request->validated());

        return back()->with('success', 'تم تحديث إعدادات التحكم بالمنصة')->with('active_admin_settings_tab', 'platform-settings');
    }

    public function updatePayment(UpdatePaymentSettingsRequest $request): RedirectResponse
    {
        $this->settingsService->setGroup('payment', $request->validated());

        return back()->with('success', 'تم تحديث إعدادات الدفع')->with('active_admin_settings_tab', 'payment-settings');
    }

    public function updateNotifications(UpdateNotificationSettingsRequest $request): RedirectResponse
    {
        $this->settingsService->setGroup('notifications', $request->validated());

        return back()->with('success', 'تم تحديث إعدادات الإشعارات')->with('active_admin_settings_tab', 'notification-settings');
    }

    public function updateSecurity(UpdateSecuritySettingsRequest $request): RedirectResponse
    {
        $this->settingsService->setGroup('security', $request->validated());

        return back()->with('success', 'تم تحديث إعدادات الأمان')->with('active_admin_settings_tab', 'security-settings');
    }

}
