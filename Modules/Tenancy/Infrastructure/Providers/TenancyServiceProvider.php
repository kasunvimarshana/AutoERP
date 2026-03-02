<?php

declare(strict_types=1);

namespace Modules\Tenancy\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Tenancy\Application\Services\TenancyService;
use Modules\Tenancy\Domain\Contracts\TenantRepositoryContract;
use Modules\Tenancy\Infrastructure\Repositories\TenantRepository;

/**
 * Tenancy module service provider.
 *
 * Registers tenant repository binding, loads migrations,
 * loads routes, and publishes configuration.
 */
class TenancyServiceProvider extends ServiceProvider
{
    /**
     * Register bindings.
     */
    public function register(): void
    {
        $this->app->bind(TenantRepositoryContract::class, TenantRepository::class);
        $this->app->bind(TenancyService::class, TenancyService::class);

        $this->mergeConfigFrom(
            __DIR__.'/../../config/tenancy.php',
            'tenancy'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(
            __DIR__.'/../Database/Migrations'
        );

        $this->loadRoutesFrom(
            __DIR__.'/../../routes/api.php'
        );

        $this->publishes([
            __DIR__.'/../../config/tenancy.php' => config_path('tenancy.php'),
        ], 'tenancy-config');
    }
}
