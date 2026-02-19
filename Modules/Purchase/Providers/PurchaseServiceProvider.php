<?php

declare(strict_types=1);

namespace Modules\Purchase\Providers;

use Illuminate\Support\ServiceProvider;

class PurchaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register configuration
        $this->mergeConfigFrom(
            __DIR__.'/../Config/purchase.php',
            'purchase'
        );

        // Register repositories
        $this->app->bind(
            \Modules\Purchase\Repositories\VendorRepository::class,
            fn ($app) => new \Modules\Purchase\Repositories\VendorRepository
        );

        $this->app->bind(
            \Modules\Purchase\Repositories\PurchaseOrderRepository::class,
            fn ($app) => new \Modules\Purchase\Repositories\PurchaseOrderRepository
        );

        $this->app->bind(
            \Modules\Purchase\Repositories\GoodsReceiptRepository::class,
            fn ($app) => new \Modules\Purchase\Repositories\GoodsReceiptRepository
        );

        $this->app->bind(
            \Modules\Purchase\Repositories\BillRepository::class,
            fn ($app) => new \Modules\Purchase\Repositories\BillRepository
        );

        // Register services
        $this->app->singleton(
            \Modules\Purchase\Services\VendorService::class,
            fn ($app) => new \Modules\Purchase\Services\VendorService(
                $app->make(\Modules\Purchase\Repositories\VendorRepository::class)
            )
        );

        $this->app->singleton(
            \Modules\Purchase\Services\PurchaseOrderService::class,
            fn ($app) => new \Modules\Purchase\Services\PurchaseOrderService(
                $app->make(\Modules\Purchase\Repositories\PurchaseOrderRepository::class),
                $app->make(\Modules\Purchase\Repositories\VendorRepository::class)
            )
        );

        $this->app->singleton(
            \Modules\Purchase\Services\GoodsReceiptService::class,
            fn ($app) => new \Modules\Purchase\Services\GoodsReceiptService(
                $app->make(\Modules\Purchase\Repositories\GoodsReceiptRepository::class),
                $app->make(\Modules\Purchase\Repositories\PurchaseOrderRepository::class)
            )
        );

        $this->app->singleton(
            \Modules\Purchase\Services\BillService::class,
            fn ($app) => new \Modules\Purchase\Services\BillService(
                $app->make(\Modules\Purchase\Repositories\BillRepository::class),
                $app->make(\Modules\Purchase\Repositories\PurchaseOrderRepository::class),
                $app->make(\Modules\Purchase\Repositories\GoodsReceiptRepository::class),
                $app->make(\Modules\Purchase\Repositories\VendorRepository::class)
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

        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../Config/purchase.php' => config_path('purchase.php'),
            ], 'purchase-config');
        }
    }
}
