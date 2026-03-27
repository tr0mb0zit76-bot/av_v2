<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OrderNumberGenerator::class, function ($app) {
            return new OrderNumberGenerator();
        });
    }

    public function boot(): void
    {
        //
    }
}