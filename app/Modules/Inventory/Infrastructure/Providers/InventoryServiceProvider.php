<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Application\Contracts\BatchLotServiceInterface;
use Modules\Inventory\Application\Contracts\SerialNumberServiceInterface;
use Modules\Inventory\Application\Contracts\StockServiceInterface;
use Modules\Inventory\Application\Services\BatchLotService;
use Modules\Inventory\Application\Services\SerialNumberService;
use Modules\Inventory\Application\Services\StockService;
use Modules\Inventory\Domain\RepositoryInterfaces\BatchLotRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\SerialNumberRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\StockItemRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;
use Modules\Inventory\Infrastructure\Http\Controllers\BatchLotController;
use Modules\Inventory\Infrastructure\Http\Controllers\SerialNumberController;
use Modules\Inventory\Infrastructure\Http\Controllers\StockController;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\BatchLotModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\SerialNumberModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockItemModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockMovementModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentBatchLotRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentSerialNumberRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockItemRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockMovementRepository;

final class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            StockItemRepositoryInterface::class,
            static fn ($app) => new EloquentStockItemRepository($app->make(StockItemModel::class))
        );

        $this->app->bind(
            StockMovementRepositoryInterface::class,
            static fn ($app) => new EloquentStockMovementRepository($app->make(StockMovementModel::class))
        );

        $this->app->bind(
            BatchLotRepositoryInterface::class,
            static fn ($app) => new EloquentBatchLotRepository($app->make(BatchLotModel::class))
        );

        $this->app->bind(
            SerialNumberRepositoryInterface::class,
            static fn ($app) => new EloquentSerialNumberRepository($app->make(SerialNumberModel::class))
        );

        $this->app->singleton(
            StockServiceInterface::class,
            static fn ($app) => new StockService(
                $app->make(StockItemRepositoryInterface::class),
                $app->make(StockMovementRepositoryInterface::class)
            )
        );

        $this->app->singleton(
            BatchLotServiceInterface::class,
            static fn ($app) => new BatchLotService(
                $app->make(BatchLotRepositoryInterface::class)
            )
        );

        $this->app->singleton(
            SerialNumberServiceInterface::class,
            static fn ($app) => new SerialNumberService(
                $app->make(SerialNumberRepositoryInterface::class)
            )
        );

        $this->mergeConfigFrom(__DIR__ . '/../../config/inventory.php', 'inventory');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->registerRoutes();

        $this->publishes([
            __DIR__ . '/../../config/inventory.php' => config_path('inventory.php'),
        ], 'inventory-config');

        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'inventory-migrations');
    }

    private function registerRoutes(): void
    {
        Route::middleware(['api', 'auth:api'])
            ->prefix('api/inventory')
            ->group(static function (): void {
                Route::post('stock/adjust', [StockController::class, 'adjust']);
                Route::post('stock/transfer', [StockController::class, 'transfer']);
                Route::get('stock/movements', [StockController::class, 'movements']);
                Route::get('stock', [StockController::class, 'index']);

                Route::get('serial-numbers/by-serial', [SerialNumberController::class, 'findBySerial']);
                Route::apiResource('batch-lots', BatchLotController::class);
                Route::apiResource('serial-numbers', SerialNumberController::class);
            });
    }
}
