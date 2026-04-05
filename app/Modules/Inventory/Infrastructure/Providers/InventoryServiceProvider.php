<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Application\Contracts\AddValuationLayerServiceInterface;
use Modules\Inventory\Application\Contracts\AdjustInventoryServiceInterface;
use Modules\Inventory\Application\Contracts\AllocateStockServiceInterface;
use Modules\Inventory\Application\Contracts\ConsumeValuationLayersServiceInterface;
use Modules\Inventory\Application\Contracts\CreateCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\InventoryManagerServiceInterface;
use Modules\Inventory\Application\Contracts\IssueStockServiceInterface;
use Modules\Inventory\Application\Contracts\ReceiveStockServiceInterface;
use Modules\Inventory\Application\Contracts\ReconcileInventoryServiceInterface;
use Modules\Inventory\Application\Contracts\ReleaseStockServiceInterface;
use Modules\Inventory\Application\Contracts\ReserveStockServiceInterface;
use Modules\Inventory\Application\Services\AddValuationLayerService;
use Modules\Inventory\Application\Services\AdjustInventoryService;
use Modules\Inventory\Application\Services\AllocateStockService;
use Modules\Inventory\Application\Services\ConsumeValuationLayersService;
use Modules\Inventory\Application\Services\CreateCycleCountService;
use Modules\Inventory\Application\Services\InventoryManagerService;
use Modules\Inventory\Application\Services\IssueStockService;
use Modules\Inventory\Application\Services\ReceiveStockService;
use Modules\Inventory\Application\Services\ReconcileInventoryService;
use Modules\Inventory\Application\Services\ReleaseStockService;
use Modules\Inventory\Application\Services\ReserveStockService;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryBatchRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryValuationLayerRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryBatchModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCycleCountModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryLevelModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryValuationLayerModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryBatchRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryCycleCountRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryLevelRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryValuationLayerRepository;

class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InventoryLevelRepositoryInterface::class, fn($app) =>
            new EloquentInventoryLevelRepository($app->make(InventoryLevelModel::class))
        );
        $this->app->bind(InventoryBatchRepositoryInterface::class, fn($app) =>
            new EloquentInventoryBatchRepository($app->make(InventoryBatchModel::class))
        );
        $this->app->bind(InventoryValuationLayerRepositoryInterface::class, fn($app) =>
            new EloquentInventoryValuationLayerRepository($app->make(InventoryValuationLayerModel::class))
        );
        $this->app->bind(InventoryCycleCountRepositoryInterface::class, fn($app) =>
            new EloquentInventoryCycleCountRepository($app->make(InventoryCycleCountModel::class))
        );
        $this->app->bind(ReceiveStockServiceInterface::class, fn($app) =>
            new ReceiveStockService(
                $app->make(InventoryLevelRepositoryInterface::class),
                $app->make(InventoryBatchRepositoryInterface::class),
                $app->make(InventoryValuationLayerRepositoryInterface::class),
            )
        );
        $this->app->bind(IssueStockServiceInterface::class, fn($app) =>
            new IssueStockService(
                $app->make(InventoryLevelRepositoryInterface::class),
                $app->make(InventoryValuationLayerRepositoryInterface::class),
            )
        );
        $this->app->bind(AdjustInventoryServiceInterface::class, fn($app) =>
            new AdjustInventoryService($app->make(InventoryLevelRepositoryInterface::class))
        );
        $this->app->bind(ReserveStockServiceInterface::class, fn($app) =>
            new ReserveStockService($app->make(InventoryLevelRepositoryInterface::class))
        );
        $this->app->bind(ReleaseStockServiceInterface::class, fn($app) =>
            new ReleaseStockService($app->make(InventoryLevelRepositoryInterface::class))
        );
        $this->app->bind(AddValuationLayerServiceInterface::class, fn($app) =>
            new AddValuationLayerService($app->make(InventoryValuationLayerRepositoryInterface::class))
        );
        $this->app->bind(ConsumeValuationLayersServiceInterface::class, fn($app) =>
            new ConsumeValuationLayersService($app->make(InventoryValuationLayerRepositoryInterface::class))
        );
        $this->app->bind(AllocateStockServiceInterface::class, fn($app) =>
            new AllocateStockService(
                $app->make(InventoryLevelRepositoryInterface::class),
                $app->make(InventoryBatchRepositoryInterface::class),
            )
        );
        $this->app->bind(ReconcileInventoryServiceInterface::class, fn($app) =>
            new ReconcileInventoryService(
                $app->make(InventoryCycleCountRepositoryInterface::class),
                $app->make(InventoryLevelRepositoryInterface::class),
            )
        );
        $this->app->bind(CreateCycleCountServiceInterface::class, fn($app) =>
            new CreateCycleCountService(
                $app->make(InventoryCycleCountRepositoryInterface::class),
            )
        );
        $this->app->bind(InventoryManagerServiceInterface::class, fn($app) =>
            new InventoryManagerService(
                $app->make(ReceiveStockServiceInterface::class),
                $app->make(IssueStockServiceInterface::class),
                $app->make(AllocateStockServiceInterface::class),
            )
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
