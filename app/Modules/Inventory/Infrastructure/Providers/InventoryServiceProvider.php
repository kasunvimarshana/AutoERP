<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Application\Contracts\CycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\StockLevelServiceInterface;
use Modules\Inventory\Application\Contracts\StockMovementServiceInterface;
use Modules\Inventory\Application\Services\CycleCountService;
use Modules\Inventory\Application\Services\StockLevelService;
use Modules\Inventory\Application\Services\StockMovementService;
use Modules\Inventory\Domain\RepositoryInterfaces\CycleCountLineRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\CycleCountRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\StockLevelRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentCycleCountLineRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentCycleCountRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockLevelRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockMovementRepository;

class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StockLevelRepositoryInterface::class, EloquentStockLevelRepository::class);
        $this->app->bind(StockMovementRepositoryInterface::class, EloquentStockMovementRepository::class);
        $this->app->bind(CycleCountRepositoryInterface::class, EloquentCycleCountRepository::class);
        $this->app->bind(CycleCountLineRepositoryInterface::class, EloquentCycleCountLineRepository::class);

        $this->app->bind(StockLevelServiceInterface::class, StockLevelService::class);
        $this->app->bind(StockMovementServiceInterface::class, StockMovementService::class);
        $this->app->bind(CycleCountServiceInterface::class, CycleCountService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
