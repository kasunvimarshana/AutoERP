<?php
namespace Modules\StockMovement\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\StockMovement\Application\Contracts\CreateStockMovementServiceInterface;
use Modules\StockMovement\Application\Contracts\TransferStockServiceInterface;
use Modules\StockMovement\Application\Services\CreateStockMovementService;
use Modules\StockMovement\Application\Services\TransferStockService;
use Modules\StockMovement\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;
use Modules\StockMovement\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockMovementRepository;

class StockMovementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StockMovementRepositoryInterface::class, EloquentStockMovementRepository::class);
        $this->app->bind(CreateStockMovementServiceInterface::class, CreateStockMovementService::class);
        $this->app->bind(TransferStockServiceInterface::class, TransferStockService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
