<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Repository Service Provider
 *
 * Binds repositories and services to the container.
 * Enables dependency injection for clean architecture.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Tenant Module
        $this->app->bind(
            \App\Modules\Tenant\Repositories\TenantRepository::class,
            function ($app) {
                return new \App\Modules\Tenant\Repositories\TenantRepository(
                    new \App\Modules\Tenant\Models\Tenant
                );
            }
        );

        $this->app->bind(
            \App\Modules\Tenant\Services\TenantService::class,
            function ($app) {
                return new \App\Modules\Tenant\Services\TenantService(
                    $app->make(\App\Modules\Tenant\Repositories\TenantRepository::class)
                );
            }
        );

        // CRM Module
        $this->app->bind(
            \App\Modules\CRM\Repositories\CustomerRepository::class,
            function ($app) {
                return new \App\Modules\CRM\Repositories\CustomerRepository(
                    new \App\Modules\CRM\Models\Customer
                );
            }
        );

        $this->app->bind(
            \App\Modules\CRM\Services\CustomerService::class,
            function ($app) {
                return new \App\Modules\CRM\Services\CustomerService(
                    $app->make(\App\Modules\CRM\Repositories\CustomerRepository::class)
                );
            }
        );

        // Inventory Module
        $this->app->bind(
            \App\Modules\Inventory\Repositories\ProductRepository::class,
            function ($app) {
                return new \App\Modules\Inventory\Repositories\ProductRepository(
                    new \App\Modules\Inventory\Models\Product
                );
            }
        );

        $this->app->bind(
            \App\Modules\Inventory\Repositories\StockLedgerRepository::class,
            function ($app) {
                return new \App\Modules\Inventory\Repositories\StockLedgerRepository(
                    new \App\Modules\Inventory\Models\StockLedger
                );
            }
        );

        $this->app->bind(
            \App\Modules\Inventory\Services\StockManagementService::class,
            function ($app) {
                return new \App\Modules\Inventory\Services\StockManagementService(
                    $app->make(\App\Modules\Inventory\Repositories\StockLedgerRepository::class),
                    $app->make(\App\Modules\Inventory\Repositories\ProductRepository::class)
                );
            }
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
