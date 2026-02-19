<?php

declare(strict_types=1);

namespace Modules\Inventory\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Models\StockCount;
use Modules\Inventory\Models\StockItem;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Policies\StockCountPolicy;
use Modules\Inventory\Policies\StockItemPolicy;
use Modules\Inventory\Policies\StockMovementPolicy;
use Modules\Inventory\Policies\WarehousePolicy;

class InventoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register configuration
        $this->mergeConfigFrom(
            __DIR__.'/../Config/inventory.php',
            'inventory'
        );

        // Register repositories
        $this->app->bind(
            \Modules\Inventory\Repositories\WarehouseRepository::class,
            fn ($app) => new \Modules\Inventory\Repositories\WarehouseRepository
        );

        $this->app->bind(
            \Modules\Inventory\Repositories\StockItemRepository::class,
            fn ($app) => new \Modules\Inventory\Repositories\StockItemRepository
        );

        $this->app->bind(
            \Modules\Inventory\Repositories\StockMovementRepository::class,
            fn ($app) => new \Modules\Inventory\Repositories\StockMovementRepository
        );

        $this->app->bind(
            \Modules\Inventory\Repositories\StockCountRepository::class,
            fn ($app) => new \Modules\Inventory\Repositories\StockCountRepository
        );

        $this->app->bind(
            \Modules\Inventory\Repositories\SerialNumberRepository::class,
            fn ($app) => new \Modules\Inventory\Repositories\SerialNumberRepository
        );

        // Register services
        $this->app->singleton(
            \Modules\Inventory\Services\WarehouseService::class,
            fn ($app) => new \Modules\Inventory\Services\WarehouseService(
                $app->make(\Modules\Inventory\Repositories\WarehouseRepository::class)
            )
        );

        $this->app->singleton(
            \Modules\Inventory\Services\StockMovementService::class,
            fn ($app) => new \Modules\Inventory\Services\StockMovementService(
                $app->make(\Modules\Inventory\Repositories\StockMovementRepository::class),
                $app->make(\Modules\Inventory\Repositories\StockItemRepository::class),
                $app->make(\Modules\Inventory\Repositories\WarehouseRepository::class)
            )
        );

        $this->app->singleton(
            \Modules\Inventory\Services\InventoryValuationService::class,
            fn ($app) => new \Modules\Inventory\Services\InventoryValuationService(
                $app->make(\Modules\Inventory\Repositories\StockItemRepository::class),
                $app->make(\Modules\Inventory\Repositories\StockMovementRepository::class)
            )
        );

        $this->app->singleton(
            \Modules\Inventory\Services\StockCountService::class,
            fn ($app) => new \Modules\Inventory\Services\StockCountService(
                $app->make(\Modules\Inventory\Repositories\StockCountRepository::class),
                $app->make(\Modules\Inventory\Repositories\StockItemRepository::class),
                $app->make(\Modules\Inventory\Services\StockMovementService::class)
            )
        );

        $this->app->singleton(
            \Modules\Inventory\Services\ReorderService::class,
            fn ($app) => new \Modules\Inventory\Services\ReorderService(
                $app->make(\Modules\Inventory\Repositories\StockItemRepository::class)
            )
        );

        $this->app->singleton(
            \Modules\Inventory\Services\SerialNumberService::class,
            fn ($app) => new \Modules\Inventory\Services\SerialNumberService(
                $app->make(\Modules\Inventory\Repositories\SerialNumberRepository::class)
            )
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Register policies
        Gate::policy(Warehouse::class, WarehousePolicy::class);
        Gate::policy(StockItem::class, StockItemPolicy::class);
        Gate::policy(StockMovement::class, StockMovementPolicy::class);
        Gate::policy(StockCount::class, StockCountPolicy::class);

        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../Config/inventory.php' => config_path('inventory.php'),
            ], 'inventory-config');
        }
    }
}
