<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Supplier\Application\Contracts\SupplierServiceInterface;
use Modules\Supplier\Application\Services\SupplierService;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;
use Modules\Supplier\Infrastructure\Http\Controllers\SupplierController;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierRepository;

final class SupplierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SupplierRepositoryInterface::class,
            static fn ($app) => new EloquentSupplierRepository($app->make(SupplierModel::class))
        );

        $this->app->singleton(
            SupplierServiceInterface::class,
            static fn ($app) => new SupplierService(
                $app->make(SupplierRepositoryInterface::class)
            )
        );

        $this->mergeConfigFrom(__DIR__ . '/../../config/supplier.php', 'supplier');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->registerRoutes();

        $this->publishes([
            __DIR__ . '/../../config/supplier.php' => config_path('supplier.php'),
        ], 'supplier-config');

        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'supplier-migrations');
    }

    private function registerRoutes(): void
    {
        Route::middleware(['api', 'auth:api'])
            ->prefix('api/supplier')
            ->group(static function (): void {
                Route::apiResource('suppliers', SupplierController::class);
            });
    }
}
