<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Warehouse\Application\Contracts\LocationServiceInterface;
use Modules\Warehouse\Application\Contracts\WarehouseServiceInterface;
use Modules\Warehouse\Application\Services\LocationService;
use Modules\Warehouse\Application\Services\WarehouseService;
use Modules\Warehouse\Domain\RepositoryInterfaces\LocationRepositoryInterface;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;
use Modules\Warehouse\Infrastructure\Http\Controllers\LocationController;
use Modules\Warehouse\Infrastructure\Http\Controllers\WarehouseController;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentLocationRepository;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentWarehouseRepository;

final class WarehouseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            WarehouseRepositoryInterface::class,
            static fn ($app) => new EloquentWarehouseRepository($app->make(WarehouseModel::class))
        );

        $this->app->bind(
            LocationRepositoryInterface::class,
            static fn ($app) => new EloquentLocationRepository($app->make(LocationModel::class))
        );

        $this->app->singleton(
            WarehouseServiceInterface::class,
            static fn ($app) => new WarehouseService($app->make(WarehouseRepositoryInterface::class))
        );

        $this->app->singleton(
            LocationServiceInterface::class,
            static fn ($app) => new LocationService($app->make(LocationRepositoryInterface::class))
        );

        $this->mergeConfigFrom(__DIR__ . '/../../config/warehouse.php', 'warehouse');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->registerRoutes();

        $this->publishes([
            __DIR__ . '/../../config/warehouse.php' => config_path('warehouse.php'),
        ], 'warehouse-config');

        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'warehouse-migrations');
    }

    private function registerRoutes(): void
    {
        Route::middleware(['api', 'auth:api'])
            ->prefix('api/warehouse')
            ->group(static function (): void {
                Route::get('locations/{id}/tree', [LocationController::class, 'getTree']);
                Route::apiResource('warehouses', WarehouseController::class);
                Route::apiResource('locations', LocationController::class);
            });
    }
}
