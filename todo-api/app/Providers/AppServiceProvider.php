<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Blade::directive('price', function ($expression) {
            return "<?php echo '<span style=\"direction:ltr;display:inline-block;text-align:right;unicode-bidi:embed;\"><span style=\"font-size:1.1em;font-weight:bold;\">₪</span> ' . number_format($expression, 2) . '</span>'; ?>";
        });
    }
}
