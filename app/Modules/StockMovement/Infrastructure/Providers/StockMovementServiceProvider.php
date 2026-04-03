<?php

declare(strict_types=1);

namespace Modules\StockMovement\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\StockMovement\Application\Contracts\ConfirmStockMovementServiceInterface;
use Modules\StockMovement\Application\Contracts\CreateStockMovementServiceInterface;
use Modules\StockMovement\Application\Contracts\DeleteStockMovementServiceInterface;
use Modules\StockMovement\Application\Contracts\FindStockMovementServiceInterface;
use Modules\StockMovement\Application\Contracts\TransferStockServiceInterface;
use Modules\StockMovement\Application\Contracts\UpdateStockMovementServiceInterface;
use Modules\StockMovement\Application\Services\ConfirmStockMovementService;
use Modules\StockMovement\Application\Services\CreateStockMovementService;
use Modules\StockMovement\Application\Services\DeleteStockMovementService;
use Modules\StockMovement\Application\Services\FindStockMovementService;
use Modules\StockMovement\Application\Services\TransferStockService;
use Modules\StockMovement\Application\Services\UpdateStockMovementService;
use Modules\StockMovement\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;
use Modules\StockMovement\Infrastructure\Persistence\Eloquent\Models\StockMovementModel;
use Modules\StockMovement\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockMovementRepository;

class StockMovementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StockMovementRepositoryInterface::class, fn ($app) =>
            new EloquentStockMovementRepository($app->make(StockMovementModel::class)));

        $this->app->bind(CreateStockMovementServiceInterface::class, fn ($app) =>
            new CreateStockMovementService($app->make(StockMovementRepositoryInterface::class)));

        $this->app->bind(FindStockMovementServiceInterface::class, fn ($app) =>
            new FindStockMovementService($app->make(StockMovementRepositoryInterface::class)));

        $this->app->bind(UpdateStockMovementServiceInterface::class, fn ($app) =>
            new UpdateStockMovementService($app->make(StockMovementRepositoryInterface::class)));

        $this->app->bind(DeleteStockMovementServiceInterface::class, fn ($app) =>
            new DeleteStockMovementService($app->make(StockMovementRepositoryInterface::class)));

        $this->app->bind(ConfirmStockMovementServiceInterface::class, fn ($app) =>
            new ConfirmStockMovementService($app->make(StockMovementRepositoryInterface::class)));

        $this->app->bind(TransferStockServiceInterface::class, fn ($app) =>
            new TransferStockService($app->make(StockMovementRepositoryInterface::class)));
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
