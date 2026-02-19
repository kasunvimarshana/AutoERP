<?php

declare(strict_types=1);

namespace Modules\Pricing\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Pricing\Models\ProductPrice;
use Modules\Pricing\Policies\ProductPricePolicy;
use Modules\Pricing\Services\PricingService;

/**
 * PricingServiceProvider
 *
 * Bootstraps the pricing module
 */
class PricingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PricingService::class, function ($app) {
            return new PricingService;
        });

        $this->mergeConfigFrom(
            __DIR__.'/../Config/pricing.php',
            'pricing'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../Config/pricing.php' => config_path('pricing.php'),
        ], 'pricing-config');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        Gate::policy(ProductPrice::class, ProductPricePolicy::class);
    }
}
