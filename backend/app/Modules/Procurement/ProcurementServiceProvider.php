<?php

namespace App\Modules\Procurement;

use Illuminate\Support\ServiceProvider;

class ProcurementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register repositories
        $this->app->bind(
            \App\Modules\Procurement\Repositories\SupplierRepository::class,
            function ($app) {
                return new \App\Modules\Procurement\Repositories\SupplierRepository(
                    new \App\Modules\Procurement\Models\Supplier()
                );
            }
        );

        $this->app->bind(
            \App\Modules\Procurement\Repositories\PurchaseOrderRepository::class,
            function ($app) {
                return new \App\Modules\Procurement\Repositories\PurchaseOrderRepository(
                    new \App\Modules\Procurement\Models\PurchaseOrder()
                );
            }
        );

        // Register services
        $this->app->singleton(
            \App\Modules\Procurement\Services\SupplierService::class,
            function ($app) {
                return new \App\Modules\Procurement\Services\SupplierService(
                    $app->make(\App\Modules\Procurement\Repositories\SupplierRepository::class)
                );
            }
        );

        $this->app->singleton(
            \App\Modules\Procurement\Services\PurchaseOrderService::class,
            function ($app) {
                return new \App\Modules\Procurement\Services\PurchaseOrderService(
                    $app->make(\App\Modules\Procurement\Repositories\PurchaseOrderRepository::class)
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
