<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\BrevoMailService;

class BrevoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BrevoMailService::class, function ($app) {
            return new BrevoMailService();
        });
    }

    public function boot(): void
    {
        //
    }
}
