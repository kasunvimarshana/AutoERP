<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Application\Contracts\AdjustInventoryServiceInterface;
use Modules\Inventory\Application\Contracts\CreateInventoryBatchServiceInterface;
use Modules\Inventory\Application\Contracts\CreateInventoryCycleCountLineServiceInterface;
use Modules\Inventory\Application\Contracts\CreateInventoryCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\CreateInventoryLevelServiceInterface;
use Modules\Inventory\Application\Contracts\CreateInventoryLocationServiceInterface;
use Modules\Inventory\Application\Contracts\CreateInventorySerialNumberServiceInterface;
use Modules\Inventory\Application\Contracts\CreateInventorySettingServiceInterface;
use Modules\Inventory\Application\Contracts\CreateInventoryValuationLayerServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteInventoryBatchServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteInventoryCycleCountLineServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteInventoryCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteInventoryLevelServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteInventoryLocationServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteInventorySerialNumberServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteInventorySettingServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteInventoryValuationLayerServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventoryBatchServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventoryCycleCountLineServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventoryCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventoryLevelServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventoryLocationServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventorySerialNumberServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventorySettingServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventoryValuationLayerServiceInterface;
use Modules\Inventory\Application\Contracts\ReconcileInventoryServiceInterface;
use Modules\Inventory\Application\Contracts\ReleaseStockServiceInterface;
use Modules\Inventory\Application\Contracts\ReserveStockServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventoryBatchServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventoryCycleCountLineServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventoryCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventoryLevelServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventoryLocationServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventorySerialNumberServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventorySettingServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventoryValuationLayerServiceInterface;
use Modules\Inventory\Application\Services\AdjustInventoryService;
use Modules\Inventory\Application\Services\CreateInventoryBatchService;
use Modules\Inventory\Application\Services\CreateInventoryCycleCountLineService;
use Modules\Inventory\Application\Services\CreateInventoryCycleCountService;
use Modules\Inventory\Application\Services\CreateInventoryLevelService;
use Modules\Inventory\Application\Services\CreateInventoryLocationService;
use Modules\Inventory\Application\Services\CreateInventorySerialNumberService;
use Modules\Inventory\Application\Services\CreateInventorySettingService;
use Modules\Inventory\Application\Services\CreateInventoryValuationLayerService;
use Modules\Inventory\Application\Services\DeleteInventoryBatchService;
use Modules\Inventory\Application\Services\DeleteInventoryCycleCountLineService;
use Modules\Inventory\Application\Services\DeleteInventoryCycleCountService;
use Modules\Inventory\Application\Services\DeleteInventoryLevelService;
use Modules\Inventory\Application\Services\DeleteInventoryLocationService;
use Modules\Inventory\Application\Services\DeleteInventorySerialNumberService;
use Modules\Inventory\Application\Services\DeleteInventorySettingService;
use Modules\Inventory\Application\Services\DeleteInventoryValuationLayerService;
use Modules\Inventory\Application\Services\FindInventoryBatchService;
use Modules\Inventory\Application\Services\FindInventoryCycleCountLineService;
use Modules\Inventory\Application\Services\FindInventoryCycleCountService;
use Modules\Inventory\Application\Services\FindInventoryLevelService;
use Modules\Inventory\Application\Services\FindInventoryLocationService;
use Modules\Inventory\Application\Services\FindInventorySerialNumberService;
use Modules\Inventory\Application\Services\FindInventorySettingService;
use Modules\Inventory\Application\Services\FindInventoryValuationLayerService;
use Modules\Inventory\Application\Services\ReconcileInventoryService;
use Modules\Inventory\Application\Services\ReleaseStockService;
use Modules\Inventory\Application\Services\ReserveStockService;
use Modules\Inventory\Application\Services\UpdateInventoryBatchService;
use Modules\Inventory\Application\Services\UpdateInventoryCycleCountLineService;
use Modules\Inventory\Application\Services\UpdateInventoryCycleCountService;
use Modules\Inventory\Application\Services\UpdateInventoryLevelService;
use Modules\Inventory\Application\Services\UpdateInventoryLocationService;
use Modules\Inventory\Application\Services\UpdateInventorySerialNumberService;
use Modules\Inventory\Application\Services\UpdateInventorySettingService;
use Modules\Inventory\Application\Services\UpdateInventoryValuationLayerService;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryBatchRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountLineRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLocationRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySerialNumberRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySettingRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryValuationLayerRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryBatchModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCycleCountLineModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCycleCountModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryLevelModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryLocationModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventorySerialNumberModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventorySettingModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryValuationLayerModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryBatchRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryCycleCountLineRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryCycleCountRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryLevelRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryLocationRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventorySerialNumberRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventorySettingRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryValuationLayerRepository;

class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // --- Repositories ---
        $this->app->bind(InventorySettingRepositoryInterface::class, fn ($app) =>
            new EloquentInventorySettingRepository($app->make(InventorySettingModel::class)));

        $this->app->bind(InventoryLocationRepositoryInterface::class, fn ($app) =>
            new EloquentInventoryLocationRepository($app->make(InventoryLocationModel::class)));

        $this->app->bind(InventoryBatchRepositoryInterface::class, fn ($app) =>
            new EloquentInventoryBatchRepository($app->make(InventoryBatchModel::class)));

        $this->app->bind(InventorySerialNumberRepositoryInterface::class, fn ($app) =>
            new EloquentInventorySerialNumberRepository($app->make(InventorySerialNumberModel::class)));

        $this->app->bind(InventoryLevelRepositoryInterface::class, fn ($app) =>
            new EloquentInventoryLevelRepository($app->make(InventoryLevelModel::class)));

        $this->app->bind(InventoryValuationLayerRepositoryInterface::class, fn ($app) =>
            new EloquentInventoryValuationLayerRepository($app->make(InventoryValuationLayerModel::class)));

        $this->app->bind(InventoryCycleCountRepositoryInterface::class, fn ($app) =>
            new EloquentInventoryCycleCountRepository($app->make(InventoryCycleCountModel::class)));

        $this->app->bind(InventoryCycleCountLineRepositoryInterface::class, fn ($app) =>
            new EloquentInventoryCycleCountLineRepository($app->make(InventoryCycleCountLineModel::class)));

        // --- Services: InventorySetting ---
        $this->app->bind(CreateInventorySettingServiceInterface::class, fn ($app) =>
            new CreateInventorySettingService($app->make(InventorySettingRepositoryInterface::class)));
        $this->app->bind(FindInventorySettingServiceInterface::class, fn ($app) =>
            new FindInventorySettingService($app->make(InventorySettingRepositoryInterface::class)));
        $this->app->bind(UpdateInventorySettingServiceInterface::class, fn ($app) =>
            new UpdateInventorySettingService($app->make(InventorySettingRepositoryInterface::class)));
        $this->app->bind(DeleteInventorySettingServiceInterface::class, fn ($app) =>
            new DeleteInventorySettingService($app->make(InventorySettingRepositoryInterface::class)));

        // --- Services: InventoryLocation ---
        $this->app->bind(CreateInventoryLocationServiceInterface::class, fn ($app) =>
            new CreateInventoryLocationService($app->make(InventoryLocationRepositoryInterface::class)));
        $this->app->bind(FindInventoryLocationServiceInterface::class, fn ($app) =>
            new FindInventoryLocationService($app->make(InventoryLocationRepositoryInterface::class)));
        $this->app->bind(UpdateInventoryLocationServiceInterface::class, fn ($app) =>
            new UpdateInventoryLocationService($app->make(InventoryLocationRepositoryInterface::class)));
        $this->app->bind(DeleteInventoryLocationServiceInterface::class, fn ($app) =>
            new DeleteInventoryLocationService($app->make(InventoryLocationRepositoryInterface::class)));

        // --- Services: InventoryBatch ---
        $this->app->bind(CreateInventoryBatchServiceInterface::class, fn ($app) =>
            new CreateInventoryBatchService($app->make(InventoryBatchRepositoryInterface::class)));
        $this->app->bind(FindInventoryBatchServiceInterface::class, fn ($app) =>
            new FindInventoryBatchService($app->make(InventoryBatchRepositoryInterface::class)));
        $this->app->bind(UpdateInventoryBatchServiceInterface::class, fn ($app) =>
            new UpdateInventoryBatchService($app->make(InventoryBatchRepositoryInterface::class)));
        $this->app->bind(DeleteInventoryBatchServiceInterface::class, fn ($app) =>
            new DeleteInventoryBatchService($app->make(InventoryBatchRepositoryInterface::class)));

        // --- Services: InventorySerialNumber ---
        $this->app->bind(CreateInventorySerialNumberServiceInterface::class, fn ($app) =>
            new CreateInventorySerialNumberService($app->make(InventorySerialNumberRepositoryInterface::class)));
        $this->app->bind(FindInventorySerialNumberServiceInterface::class, fn ($app) =>
            new FindInventorySerialNumberService($app->make(InventorySerialNumberRepositoryInterface::class)));
        $this->app->bind(UpdateInventorySerialNumberServiceInterface::class, fn ($app) =>
            new UpdateInventorySerialNumberService($app->make(InventorySerialNumberRepositoryInterface::class)));
        $this->app->bind(DeleteInventorySerialNumberServiceInterface::class, fn ($app) =>
            new DeleteInventorySerialNumberService($app->make(InventorySerialNumberRepositoryInterface::class)));

        // --- Services: InventoryLevel ---
        $this->app->bind(CreateInventoryLevelServiceInterface::class, fn ($app) =>
            new CreateInventoryLevelService($app->make(InventoryLevelRepositoryInterface::class)));
        $this->app->bind(FindInventoryLevelServiceInterface::class, fn ($app) =>
            new FindInventoryLevelService($app->make(InventoryLevelRepositoryInterface::class)));
        $this->app->bind(UpdateInventoryLevelServiceInterface::class, fn ($app) =>
            new UpdateInventoryLevelService($app->make(InventoryLevelRepositoryInterface::class)));
        $this->app->bind(DeleteInventoryLevelServiceInterface::class, fn ($app) =>
            new DeleteInventoryLevelService($app->make(InventoryLevelRepositoryInterface::class)));
        $this->app->bind(ReserveStockServiceInterface::class, fn ($app) =>
            new ReserveStockService($app->make(InventoryLevelRepositoryInterface::class)));
        $this->app->bind(ReleaseStockServiceInterface::class, fn ($app) =>
            new ReleaseStockService($app->make(InventoryLevelRepositoryInterface::class)));
        $this->app->bind(AdjustInventoryServiceInterface::class, fn ($app) =>
            new AdjustInventoryService($app->make(InventoryLevelRepositoryInterface::class)));

        // --- Services: InventoryValuationLayer ---
        $this->app->bind(CreateInventoryValuationLayerServiceInterface::class, fn ($app) =>
            new CreateInventoryValuationLayerService($app->make(InventoryValuationLayerRepositoryInterface::class)));
        $this->app->bind(FindInventoryValuationLayerServiceInterface::class, fn ($app) =>
            new FindInventoryValuationLayerService($app->make(InventoryValuationLayerRepositoryInterface::class)));
        $this->app->bind(UpdateInventoryValuationLayerServiceInterface::class, fn ($app) =>
            new UpdateInventoryValuationLayerService($app->make(InventoryValuationLayerRepositoryInterface::class)));
        $this->app->bind(DeleteInventoryValuationLayerServiceInterface::class, fn ($app) =>
            new DeleteInventoryValuationLayerService($app->make(InventoryValuationLayerRepositoryInterface::class)));

        // --- Services: InventoryCycleCount ---
        $this->app->bind(CreateInventoryCycleCountServiceInterface::class, fn ($app) =>
            new CreateInventoryCycleCountService($app->make(InventoryCycleCountRepositoryInterface::class)));
        $this->app->bind(FindInventoryCycleCountServiceInterface::class, fn ($app) =>
            new FindInventoryCycleCountService($app->make(InventoryCycleCountRepositoryInterface::class)));
        $this->app->bind(UpdateInventoryCycleCountServiceInterface::class, fn ($app) =>
            new UpdateInventoryCycleCountService($app->make(InventoryCycleCountRepositoryInterface::class)));
        $this->app->bind(DeleteInventoryCycleCountServiceInterface::class, fn ($app) =>
            new DeleteInventoryCycleCountService($app->make(InventoryCycleCountRepositoryInterface::class)));
        $this->app->bind(ReconcileInventoryServiceInterface::class, fn ($app) =>
            new ReconcileInventoryService($app->make(InventoryCycleCountRepositoryInterface::class)));

        // --- Services: InventoryCycleCountLine ---
        $this->app->bind(CreateInventoryCycleCountLineServiceInterface::class, fn ($app) =>
            new CreateInventoryCycleCountLineService($app->make(InventoryCycleCountLineRepositoryInterface::class)));
        $this->app->bind(FindInventoryCycleCountLineServiceInterface::class, fn ($app) =>
            new FindInventoryCycleCountLineService($app->make(InventoryCycleCountLineRepositoryInterface::class)));
        $this->app->bind(UpdateInventoryCycleCountLineServiceInterface::class, fn ($app) =>
            new UpdateInventoryCycleCountLineService($app->make(InventoryCycleCountLineRepositoryInterface::class)));
        $this->app->bind(DeleteInventoryCycleCountLineServiceInterface::class, fn ($app) =>
            new DeleteInventoryCycleCountLineService($app->make(InventoryCycleCountLineRepositoryInterface::class)));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        Route::middleware(['api', 'auth:api', 'resolve.tenant'])
            ->prefix('api')
            ->group(function () {
                $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
            });
    }
}
