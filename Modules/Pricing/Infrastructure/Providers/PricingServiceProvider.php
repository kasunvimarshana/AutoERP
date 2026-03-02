<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Pricing\Domain\Contracts\PricingRepositoryContract;
use Modules\Pricing\Infrastructure\Repositories\PricingRepository;

/**
 * Pricing module service provider.
 *
 * Registers repository bindings, loads migrations and routes.
 */
class PricingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PricingRepositoryContract::class,
            PricingRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(
            __DIR__.'/../Database/Migrations'
        );

        $this->loadRoutesFrom(
            __DIR__.'/../../routes/api.php'
        );
    }
}
