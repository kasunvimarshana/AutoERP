<?php
namespace Modules\Inventory\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Application\Contracts\AddValuationLayerServiceInterface;
use Modules\Inventory\Application\Contracts\AdjustInventoryServiceInterface;
use Modules\Inventory\Application\Contracts\AllocateStockServiceInterface;
use Modules\Inventory\Application\Contracts\ConsumeValuationLayersServiceInterface;
use Modules\Inventory\Application\Contracts\CreateCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\CreateInventoryBatchServiceInterface;
use Modules\Inventory\Application\Contracts\CreateInventorySerialServiceInterface;
use Modules\Inventory\Application\Contracts\CreateInventorySettingServiceInterface;
use Modules\Inventory\Application\Contracts\IssueStockServiceInterface;
use Modules\Inventory\Application\Contracts\ReceiveStockServiceInterface;
use Modules\Inventory\Application\Contracts\ReconcileInventoryServiceInterface;
use Modules\Inventory\Application\Contracts\ReleaseStockServiceInterface;
use Modules\Inventory\Application\Contracts\ReserveStockServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventorySettingServiceInterface;
use Modules\Inventory\Application\Services\AddValuationLayerService;
use Modules\Inventory\Application\Services\AdjustInventoryService;
use Modules\Inventory\Application\Services\AllocateStockService;
use Modules\Inventory\Application\Services\ConsumeValuationLayersService;
use Modules\Inventory\Application\Services\CreateCycleCountService;
use Modules\Inventory\Application\Services\CreateInventoryBatchService;
use Modules\Inventory\Application\Services\CreateInventorySerialService;
use Modules\Inventory\Application\Services\CreateInventorySettingService;
use Modules\Inventory\Application\Services\IssueStockService;
use Modules\Inventory\Application\Services\ReceiveStockService;
use Modules\Inventory\Application\Services\ReconcileInventoryService;
use Modules\Inventory\Application\Services\ReleaseStockService;
use Modules\Inventory\Application\Services\ReserveStockService;
use Modules\Inventory\Application\Services\UpdateInventorySettingService;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryBatchRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySerialRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySettingRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryValuationLayerRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryBatchRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryCycleCountRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryLevelRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventorySerialRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventorySettingRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryValuationLayerRepository;

class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InventoryLevelRepositoryInterface::class, EloquentInventoryLevelRepository::class);
        $this->app->bind(InventoryValuationLayerRepositoryInterface::class, EloquentInventoryValuationLayerRepository::class);
        $this->app->bind(InventoryBatchRepositoryInterface::class, EloquentInventoryBatchRepository::class);
        $this->app->bind(InventorySerialRepositoryInterface::class, EloquentInventorySerialRepository::class);
        $this->app->bind(InventorySettingRepositoryInterface::class, EloquentInventorySettingRepository::class);
        $this->app->bind(InventoryCycleCountRepositoryInterface::class, EloquentInventoryCycleCountRepository::class);

        $this->app->bind(ReceiveStockServiceInterface::class, ReceiveStockService::class);
        $this->app->bind(IssueStockServiceInterface::class, IssueStockService::class);
        $this->app->bind(AddValuationLayerServiceInterface::class, AddValuationLayerService::class);
        $this->app->bind(ConsumeValuationLayersServiceInterface::class, ConsumeValuationLayersService::class);
        $this->app->bind(AllocateStockServiceInterface::class, AllocateStockService::class);
        $this->app->bind(ReserveStockServiceInterface::class, ReserveStockService::class);
        $this->app->bind(ReleaseStockServiceInterface::class, ReleaseStockService::class);
        $this->app->bind(AdjustInventoryServiceInterface::class, AdjustInventoryService::class);
        $this->app->bind(ReconcileInventoryServiceInterface::class, ReconcileInventoryService::class);
        $this->app->bind(CreateInventorySettingServiceInterface::class, CreateInventorySettingService::class);
        $this->app->bind(UpdateInventorySettingServiceInterface::class, UpdateInventorySettingService::class);
        $this->app->bind(CreateInventoryBatchServiceInterface::class, CreateInventoryBatchService::class);
        $this->app->bind(CreateInventorySerialServiceInterface::class, CreateInventorySerialService::class);
        $this->app->bind(CreateCycleCountServiceInterface::class, CreateCycleCountService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
