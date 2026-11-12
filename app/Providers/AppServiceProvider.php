<?php

namespace App\Providers;

use App\Support\Settings;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

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
        try {
            if (Schema::hasTable('clinic_settings')) {
                $timezone = Settings::get('timezone');
                if ($timezone) {
                    config(['app.timezone' => $timezone]);
                    date_default_timezone_set($timezone);
                }

                $lifetime = Settings::int('session_timeout_minutes');
                if ($lifetime > 0) {
                    config(['session.lifetime' => $lifetime]);
                }
            }
        } catch (Throwable) {
            // Database not ready (e.g. during migrations) — use defaults.
        }
    }
}
