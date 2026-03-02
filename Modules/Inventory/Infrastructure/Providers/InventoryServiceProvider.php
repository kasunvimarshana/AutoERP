<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Application\Services\InventoryService;
use Modules\Inventory\Domain\Contracts\InventoryRepositoryContract;
use Modules\Inventory\Domain\Contracts\InventoryServiceContract;
use Modules\Inventory\Infrastructure\Repositories\InventoryRepository;

/**
 * Inventory module service provider.
 *
 * Registers repository bindings, loads migrations and routes.
 */
class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            InventoryRepositoryContract::class,
            InventoryRepository::class,
        );

        $this->app->bind(
            InventoryServiceContract::class,
            InventoryService::class,
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
