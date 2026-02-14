<?php

namespace App\Modules\Billing;

use Illuminate\Support\ServiceProvider;

class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register repositories
        $this->app->bind(
            \App\Modules\Billing\Repositories\InvoiceRepository::class,
            function ($app) {
                return new \App\Modules\Billing\Repositories\InvoiceRepository(
                    new \App\Modules\Billing\Models\Invoice()
                );
            }
        );

        $this->app->bind(
            \App\Modules\Billing\Repositories\InvoiceItemRepository::class,
            function ($app) {
                return new \App\Modules\Billing\Repositories\InvoiceItemRepository(
                    new \App\Modules\Billing\Models\InvoiceItem()
                );
            }
        );

        $this->app->bind(
            \App\Modules\Billing\Repositories\PaymentRepository::class,
            function ($app) {
                return new \App\Modules\Billing\Repositories\PaymentRepository(
                    new \App\Modules\Billing\Models\Payment()
                );
            }
        );

        // Register services
        $this->app->singleton(
            \App\Modules\Billing\Services\InvoiceService::class,
            function ($app) {
                return new \App\Modules\Billing\Services\InvoiceService(
                    $app->make(\App\Modules\Billing\Repositories\InvoiceRepository::class),
                    $app->make(\App\Modules\Billing\Repositories\InvoiceItemRepository::class),
                    $app->make(\App\Modules\Billing\Repositories\PaymentRepository::class)
                );
            }
        );

        $this->app->singleton(
            \App\Modules\Billing\Services\InvoiceItemService::class,
            function ($app) {
                return new \App\Modules\Billing\Services\InvoiceItemService(
                    $app->make(\App\Modules\Billing\Repositories\InvoiceItemRepository::class)
                );
            }
        );

        $this->app->singleton(
            \App\Modules\Billing\Services\PaymentService::class,
            function ($app) {
                return new \App\Modules\Billing\Services\PaymentService(
                    $app->make(\App\Modules\Billing\Repositories\PaymentRepository::class),
                    $app->make(\App\Modules\Billing\Repositories\InvoiceRepository::class)
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
