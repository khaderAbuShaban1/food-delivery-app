<?php

namespace App\Modules\Restaurant\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Restaurant\Requests\UpdateAccountSettingsRequest;
use App\Modules\Restaurant\Requests\UpdateGeneralSettingsRequest;
use App\Modules\Restaurant\Requests\UpdateOrderSettingsRequest;
use App\Modules\Restaurant\Requests\UpdateWorkingHoursRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingsController extends Controller
{
    private const DAYS = [
        'saturday' => 'السبت',
        'sunday' => 'الأحد',
        'monday' => 'الإثنين',
        'tuesday' => 'الثلاثاء',
        'wednesday' => 'الأربعاء',
        'thursday' => 'الخميس',
        'friday' => 'الجمعة',
    ];

    public function index(): View|RedirectResponse
    {
        $restaurant = Auth::guard('restaurant')->user();
        if (!$restaurant) {
            return redirect()->route('restaurant.login');
        }

        $workingHours = $this->normalizeWorkingHours($restaurant->working_hours ?? []);

        return view('restaurant::settings', [
            'restaurant' => $restaurant,
            'workingHours' => $workingHours,
            'days' => self::DAYS,
        ]);
    }

    public function updateGeneral(UpdateGeneralSettingsRequest $request): RedirectResponse
    {
        $restaurant = Auth::guard('restaurant')->user();
        if (!$restaurant) {
            return back()->with('error', 'غير مصرح');
        }

        $payload = $request->safe()->except(['image', 'email']);
        if ($request->hasFile('image')) {
            $payload['image'] = $request->file('image')->store('restaurant-images', 'public');
        }

        $restaurant->update($payload);

        return back()
            ->with('success', 'تم تحديث المعلومات العامة بنجاح')
            ->with('active_settings_tab', 'general-settings');
    }

    public function updateWorkingHours(UpdateWorkingHoursRequest $request): RedirectResponse
    {
        $restaurant = Auth::guard('restaurant')->user();
        if (!$restaurant) {
            return back()->with('error', 'غير مصرح');
        }

        $workingHours = $this->normalizeWorkingHours($request->validated('working_hours', []));
        $restaurant->update(['working_hours' => $workingHours]);

        return back()
            ->with('success', 'تم تحديث ساعات العمل بنجاح')
            ->with('active_settings_tab', 'working-hours-settings');
    }

    public function updateOrderSettings(UpdateOrderSettingsRequest $request): RedirectResponse
    {
        $restaurant = Auth::guard('restaurant')->user();
        if (!$restaurant) {
            return back()->with('error', 'غير مصرح');
        }

        $restaurant->update([
            'is_open' => $request->boolean('is_open'),
            'minimum_order_amount' => $request->filled('minimum_order_amount')
                ? $request->input('minimum_order_amount')
                : null,
            'delivery_available' => $request->boolean('delivery_available'),
        ]);

        return back()
            ->with('success', 'تم تحديث إعدادات الطلبات بنجاح')
            ->with('active_settings_tab', 'order-settings');
    }

    public function updateAccount(UpdateAccountSettingsRequest $request): RedirectResponse
    {
        $restaurant = Auth::guard('restaurant')->user();
        if (!$restaurant) {
            return back()->with('error', 'غير مصرح');
        }

        $payload = [];

        $newPassword = $request->input('new_password');
        if (!empty($newPassword)) {
            if (!Hash::check((string) $request->input('current_password'), (string) $restaurant->password)) {
                return back()->withErrors([
                    'current_password' => 'كلمة المرور الحالية غير صحيحة',
                ])->withInput()->with('active_settings_tab', 'account-settings');
            }

            $payload['password'] = $newPassword;
        }

        $restaurant->update($payload);

        return back()
            ->with('success', 'تم تحديث إعدادات الحساب بنجاح')
            ->with('active_settings_tab', 'account-settings');
    }

    private function normalizeWorkingHours(array $workingHours): array
    {
        $normalized = [];

        foreach (self::DAYS as $dayKey => $dayLabel) {
            $day = $workingHours[$dayKey] ?? [];
            $normalized[$dayKey] = [
                'label' => $dayLabel,
                'enabled' => (bool) ($day['enabled'] ?? false),
                'open' => $day['open'] ?? '09:00',
                'close' => $day['close'] ?? '22:00',
            ];
        }

        return $normalized;
    }
}
