<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class SystemSettingsService
{
    private const CACHE_KEY = 'system_settings.snapshot';

    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $settings = $this->all();

        return $settings[$group][$key] ?? $default;
    }

    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $items = SystemSetting::query()->get(['group', 'key', 'value']);
            $settings = [];

            foreach ($items as $item) {
                $settings[$item->group][$item->key] = $item->value;
            }

            return $settings;
        });
    }

    public function setGroup(string $group, array $values): void
    {
        foreach ($values as $key => $value) {
            SystemSetting::query()->updateOrCreate(
                ['group' => $group, 'key' => $key],
                ['value' => $value]
            );
        }

        $this->clearCache();
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function seedDefaults(): void
    {
        $this->setMissingDefaults('general', [
            'site_name' => 'Food Delivery',
            'logo' => null,
            'favicon' => null,
            'default_language' => 'ar',
        ]);

        $this->setMissingDefaults('platform', [
            'registration_enabled' => true,
            'restaurants_enabled' => true,
            'orders_enabled' => true,
            'platform_open' => true,
        ]);

        $this->setMissingDefaults('payment', [
            'cash_enabled' => true,
            'online_enabled' => false,
            'transaction_fee_percent' => 0,
            'auto_capture' => false,
        ]);

        $this->setMissingDefaults('notifications', [
            'email_enabled' => true,
            'sms_enabled' => false,
            'push_enabled' => false,
            'new_order_notifications' => true,
            'status_update_notifications' => true,
            'marketing_notifications' => false,
        ]);

        $this->setMissingDefaults('security', [
            'password_min_length' => 8,
            'password_require_complexity' => false,
            'session_timeout_minutes' => 120,
            'login_attempt_limit' => 5,
        ]);
    }

    private function setMissingDefaults(string $group, array $defaults): void
    {
        foreach ($defaults as $key => $value) {
            SystemSetting::query()->firstOrCreate(
                ['group' => $group, 'key' => $key],
                ['value' => $value]
            );
        }

        $this->clearCache();
    }
}
