<?php

namespace App\Providers;

use App\Contracts\Pricing\PricingEngineInterface;
use App\Services\PricingEngine;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PricingEngineInterface::class, PricingEngine::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
