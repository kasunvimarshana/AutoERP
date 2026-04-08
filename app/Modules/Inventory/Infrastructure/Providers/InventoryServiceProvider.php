<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Application\Contracts\AllocationServiceInterface;
use Modules\Inventory\Application\Contracts\CycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\InventoryServiceInterface;
use Modules\Inventory\Application\Contracts\StockReservationServiceInterface;
use Modules\Inventory\Application\Services\Allocation\FefoAllocationStrategy;
use Modules\Inventory\Application\Services\Allocation\FifoAllocationStrategy;
use Modules\Inventory\Application\Services\Allocation\LifoAllocationStrategy;
use Modules\Inventory\Application\Services\AllocationService;
use Modules\Inventory\Application\Services\CycleCountService;
use Modules\Inventory\Application\Services\InventoryService;
use Modules\Inventory\Application\Services\StockReservationService;
use Modules\Inventory\Domain\Contracts\Repositories\BatchLotRepositoryInterface;
use Modules\Inventory\Domain\Contracts\Repositories\CycleCountRepositoryInterface;
use Modules\Inventory\Domain\Contracts\Repositories\InventoryAdjustmentRepositoryInterface;
use Modules\Inventory\Domain\Contracts\Repositories\InventoryItemRepositoryInterface;
use Modules\Inventory\Domain\Contracts\Repositories\InventoryMovementRepositoryInterface;
use Modules\Inventory\Domain\Contracts\Repositories\StockReservationRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\BatchLotModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\CycleCountModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryAdjustmentModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryItemModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryMovementModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockReservationModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentBatchLotRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentCycleCountRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryAdjustmentRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryItemRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryMovementRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockReservationRepository;

class InventoryServiceProvider extends ServiceProvider
{
    /**
     * Register Inventory module bindings.
     */
    public function register(): void
    {
        // Repositories
        $this->app->bind(InventoryItemRepositoryInterface::class, function ($app) {
            return new EloquentInventoryItemRepository($app->make(InventoryItemModel::class));
        });

        $this->app->bind(InventoryMovementRepositoryInterface::class, function ($app) {
            return new EloquentInventoryMovementRepository($app->make(InventoryMovementModel::class));
        });

        $this->app->bind(InventoryAdjustmentRepositoryInterface::class, function ($app) {
            return new EloquentInventoryAdjustmentRepository($app->make(InventoryAdjustmentModel::class));
        });

        $this->app->bind(BatchLotRepositoryInterface::class, function ($app) {
            return new EloquentBatchLotRepository($app->make(BatchLotModel::class));
        });

        $this->app->bind(CycleCountRepositoryInterface::class, function ($app) {
            return new EloquentCycleCountRepository($app->make(CycleCountModel::class));
        });

        $this->app->bind(StockReservationRepositoryInterface::class, function ($app) {
            return new EloquentStockReservationRepository($app->make(StockReservationModel::class));
        });

        // Allocation strategies (singletons — stateless)
        $this->app->singleton(FifoAllocationStrategy::class);
        $this->app->singleton(FefoAllocationStrategy::class);
        $this->app->singleton(LifoAllocationStrategy::class);

        // Services
        $this->app->bind(InventoryServiceInterface::class, function ($app) {
            return new InventoryService(
                $app->make(InventoryItemRepositoryInterface::class),
                $app->make(InventoryMovementRepositoryInterface::class),
            );
        });

        $this->app->bind(CycleCountServiceInterface::class, function ($app) {
            return new CycleCountService(
                $app->make(CycleCountRepositoryInterface::class),
                $app->make(InventoryItemRepositoryInterface::class),
            );
        });

        $this->app->bind(AllocationServiceInterface::class, function ($app) {
            return new AllocationService(
                $app->make(BatchLotRepositoryInterface::class),
                $app->make(FifoAllocationStrategy::class),
                $app->make(FefoAllocationStrategy::class),
                $app->make(LifoAllocationStrategy::class),
            );
        });

        $this->app->bind(StockReservationServiceInterface::class, function ($app) {
            return new StockReservationService(
                $app->make(StockReservationRepositoryInterface::class),
                $app->make(InventoryItemRepositoryInterface::class),
            );
        });
    }

    /**
     * Boot the Inventory service provider.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
