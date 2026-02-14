<?php

namespace App\Modules\POS;

use Illuminate\Support\ServiceProvider;

class POSServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register repositories
        $this->app->bind(
            \App\Modules\POS\Repositories\POSTransactionRepository::class,
            function ($app) {
                return new \App\Modules\POS\Repositories\POSTransactionRepository(
                    new \App\Modules\POS\Models\POSTransaction()
                );
            }
        );

        $this->app->bind(
            \App\Modules\POS\Repositories\POSTransactionItemRepository::class,
            function ($app) {
                return new \App\Modules\POS\Repositories\POSTransactionItemRepository(
                    new \App\Modules\POS\Models\POSTransactionItem()
                );
            }
        );

        // Register services
        $this->app->singleton(
            \App\Modules\POS\Services\POSTransactionService::class,
            function ($app) {
                return new \App\Modules\POS\Services\POSTransactionService(
                    $app->make(\App\Modules\POS\Repositories\POSTransactionRepository::class),
                    $app->make(\App\Modules\POS\Repositories\POSTransactionItemRepository::class)
                );
            }
        );

        $this->app->singleton(
            \App\Modules\POS\Services\POSTransactionItemService::class,
            function ($app) {
                return new \App\Modules\POS\Services\POSTransactionItemService(
                    $app->make(\App\Modules\POS\Repositories\POSTransactionItemRepository::class)
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
