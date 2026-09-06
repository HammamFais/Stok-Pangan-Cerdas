<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // Railway menangani TLS di lapisan luar, jadi container menerima
        // request sebagai http biasa. Tanpa ini, redirect dan URL yang
        // dihasilkan Laravel (misalnya /api -> /docs) memakai skema http
        // meski aplikasi diakses lewat https. Dibatasi ke production supaya
        // pengembangan lokal di http://127.0.0.1:8000 tetap berjalan.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
