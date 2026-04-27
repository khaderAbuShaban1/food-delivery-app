<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerViewNamespaces();
    }

    public function boot(): void
    {
        //
    }

    protected function registerViewNamespaces(): void
    {
        $modules = [
            'restaurant' => base_path('resources/views/restaurant'),
            'admin' => base_path('resources/views/admin'),
            'layouts' => base_path('resources/views/layouts'),
        ];

        foreach ($modules as $name => $path) {
            if (is_dir($path)) {
                $this->loadViewsFrom($path, $name);
            }
        }
    }
}