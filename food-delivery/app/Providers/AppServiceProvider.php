<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Throwable;
use App\Services\SystemSettingsService;
use App\Models\Address;
use App\Models\Driver;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\RestaurantRating;
use App\Models\User;
use App\Observers\FirestoreMirrorObserver;
use App\Observers\OrderPushNotificationObserver;
use App\Observers\OrderItemObserver;
use App\Observers\MenuItemObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $hasSystemSettings = false;
        try {
            $hasSystemSettings = Schema::hasTable('system_settings');
        } catch (Throwable) {
            $hasSystemSettings = false;
        }

        if ($hasSystemSettings) {
            /** @var SystemSettingsService $settings */
            $settings = app(SystemSettingsService::class);
            $language = (string) $settings->get('general', 'default_language', config('app.locale', 'ar'));
            $siteName = (string) $settings->get('general', 'site_name', config('app.name', 'Food Delivery'));
            app()->setLocale($language);
            date_default_timezone_set((string) config('app.timezone', 'Asia/Gaza'));
            config(['app.currency_symbol' => '₪']);
            config(['app.name' => $siteName]);
        } else {
            date_default_timezone_set((string) config('app.timezone', 'Asia/Gaza'));
            config(['app.currency_symbol' => '₪']);
        }

        Blade::directive('price', function ($expression) {
            return "<?php \$currencyCode = config('app.currency_symbol', '₪'); echo '<span style=\"direction:ltr;display:inline-block;text-align:right;unicode-bidi:embed;\"><span style=\"font-size:1.1em;font-weight:bold;\">' . e(\$currencyCode) . '</span> ' . number_format($expression, 2) . '</span>'; ?>";
        });

        OrderItem::observe(OrderItemObserver::class);
        MenuItem::observe(MenuItemObserver::class);
        Order::observe(FirestoreMirrorObserver::class);
        Order::observe(OrderPushNotificationObserver::class);
        Restaurant::observe(FirestoreMirrorObserver::class);
        Address::observe(FirestoreMirrorObserver::class);
        RestaurantRating::observe(FirestoreMirrorObserver::class);
        User::observe(FirestoreMirrorObserver::class);
        Driver::observe(FirestoreMirrorObserver::class);
    }
}
