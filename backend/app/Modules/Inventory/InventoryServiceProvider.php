<?php

namespace App\Modules\Inventory;

use Illuminate\Support\ServiceProvider;

class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register repositories
        $this->app->bind(
            \App\Modules\Inventory\Repositories\ProductRepository::class,
            function ($app) {
                return new \App\Modules\Inventory\Repositories\ProductRepository(
                    new \App\Modules\Inventory\Models\Product()
                );
            }
        );

        $this->app->bind(
            \App\Modules\Inventory\Repositories\SKURepository::class,
            function ($app) {
                return new \App\Modules\Inventory\Repositories\SKURepository(
                    new \App\Modules\Inventory\Models\SKU()
                );
            }
        );

        $this->app->bind(
            \App\Modules\Inventory\Repositories\BatchRepository::class,
            function ($app) {
                return new \App\Modules\Inventory\Repositories\BatchRepository(
                    new \App\Modules\Inventory\Models\Batch()
                );
            }
        );

        $this->app->bind(
            \App\Modules\Inventory\Repositories\StockMovementRepository::class,
            function ($app) {
                return new \App\Modules\Inventory\Repositories\StockMovementRepository(
                    new \App\Modules\Inventory\Models\StockMovement()
                );
            }
        );

        // Register services
        $this->app->singleton(
            \App\Modules\Inventory\Services\ProductService::class,
            function ($app) {
                return new \App\Modules\Inventory\Services\ProductService(
                    $app->make(\App\Modules\Inventory\Repositories\ProductRepository::class)
                );
            }
        );

        $this->app->singleton(
            \App\Modules\Inventory\Services\SKUService::class,
            function ($app) {
                return new \App\Modules\Inventory\Services\SKUService(
                    $app->make(\App\Modules\Inventory\Repositories\SKURepository::class)
                );
            }
        );

        $this->app->singleton(
            \App\Modules\Inventory\Services\BatchService::class,
            function ($app) {
                return new \App\Modules\Inventory\Services\BatchService(
                    $app->make(\App\Modules\Inventory\Repositories\BatchRepository::class)
                );
            }
        );

        $this->app->singleton(
            \App\Modules\Inventory\Services\StockMovementService::class,
            function ($app) {
                return new \App\Modules\Inventory\Services\StockMovementService(
                    $app->make(\App\Modules\Inventory\Repositories\StockMovementRepository::class)
                );
            }
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
