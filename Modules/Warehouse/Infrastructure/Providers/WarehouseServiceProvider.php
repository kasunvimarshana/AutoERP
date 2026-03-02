<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Warehouse\Domain\Contracts\WarehouseRepositoryContract;
use Modules\Warehouse\Infrastructure\Repositories\WarehouseRepository;

/**
 * Warehouse module service provider.
 *
 * Registers repository bindings, loads migrations and routes.
 */
class WarehouseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            WarehouseRepositoryContract::class,
            WarehouseRepository::class,
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
