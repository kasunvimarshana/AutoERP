<?php

namespace App\Providers;

use App\Contracts\Pricing\PricingEngineInterface;
use App\Services\PricingEngine;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        // General API rate limit: 120 req/min per user (or IP for guests)
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute((int) env('API_RATE_LIMIT', 120))->by($request->user()->id)
                : Limit::perMinute(30)->by($request->ip());
        });

        // Stricter limit on authentication endpoints to prevent brute-force
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
