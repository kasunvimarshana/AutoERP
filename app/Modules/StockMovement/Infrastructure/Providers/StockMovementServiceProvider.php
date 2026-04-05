<?php
declare(strict_types=1);
namespace Modules\StockMovement\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;
use Modules\StockMovement\Application\Contracts\TransferStockServiceInterface;
use Modules\StockMovement\Application\Services\TransferStockService;
use Modules\StockMovement\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;
use Modules\StockMovement\Infrastructure\Persistence\Eloquent\Models\StockMovementModel;
use Modules\StockMovement\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockMovementRepository;

class StockMovementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StockMovementRepositoryInterface::class, fn($app) =>
            new EloquentStockMovementRepository($app->make(StockMovementModel::class))
        );
        $this->app->bind(TransferStockServiceInterface::class, fn($app) =>
            new TransferStockService(
                $app->make(StockMovementRepositoryInterface::class),
                $app->make(InventoryLevelRepositoryInterface::class),
            )
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
