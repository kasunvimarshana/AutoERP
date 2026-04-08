<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\SalesOrder\Application\Contracts\CancelSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\ConfirmSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\CreateSalesOrderLineServiceInterface;
use Modules\SalesOrder\Application\Contracts\CreateSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\DeleteSalesOrderLineServiceInterface;
use Modules\SalesOrder\Application\Contracts\DeleteSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\DeliverSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\FindSalesOrderLineServiceInterface;
use Modules\SalesOrder\Application\Contracts\FindSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\ShipSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\StartPackingSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\StartPickingSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\UpdateSalesOrderLineServiceInterface;
use Modules\SalesOrder\Application\Contracts\UpdateSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Services\CancelSalesOrderService;
use Modules\SalesOrder\Application\Services\ConfirmSalesOrderService;
use Modules\SalesOrder\Application\Services\CreateSalesOrderLineService;
use Modules\SalesOrder\Application\Services\CreateSalesOrderService;
use Modules\SalesOrder\Application\Services\DeleteSalesOrderLineService;
use Modules\SalesOrder\Application\Services\DeleteSalesOrderService;
use Modules\SalesOrder\Application\Services\DeliverSalesOrderService;
use Modules\SalesOrder\Application\Services\FindSalesOrderLineService;
use Modules\SalesOrder\Application\Services\FindSalesOrderService;
use Modules\SalesOrder\Application\Services\ShipSalesOrderService;
use Modules\SalesOrder\Application\Services\StartPackingSalesOrderService;
use Modules\SalesOrder\Application\Services\StartPickingSalesOrderService;
use Modules\SalesOrder\Application\Services\UpdateSalesOrderLineService;
use Modules\SalesOrder\Application\Services\UpdateSalesOrderService;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderLineRepositoryInterface;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
use Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Models\SalesOrderLineModel;
use Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel;
use Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesOrderLineRepository;
use Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesOrderRepository;

class SalesOrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // --- Repositories ---
        $this->app->bind(SalesOrderRepositoryInterface::class, fn ($app) =>
            new EloquentSalesOrderRepository($app->make(SalesOrderModel::class)));

        $this->app->bind(SalesOrderLineRepositoryInterface::class, fn ($app) =>
            new EloquentSalesOrderLineRepository($app->make(SalesOrderLineModel::class)));

        // --- Services: SalesOrder ---
        $this->app->bind(CreateSalesOrderServiceInterface::class, fn ($app) =>
            new CreateSalesOrderService($app->make(SalesOrderRepositoryInterface::class)));

        $this->app->bind(FindSalesOrderServiceInterface::class, fn ($app) =>
            new FindSalesOrderService($app->make(SalesOrderRepositoryInterface::class)));

        $this->app->bind(UpdateSalesOrderServiceInterface::class, fn ($app) =>
            new UpdateSalesOrderService($app->make(SalesOrderRepositoryInterface::class)));

        $this->app->bind(DeleteSalesOrderServiceInterface::class, fn ($app) =>
            new DeleteSalesOrderService($app->make(SalesOrderRepositoryInterface::class)));

        $this->app->bind(ConfirmSalesOrderServiceInterface::class, fn ($app) =>
            new ConfirmSalesOrderService($app->make(SalesOrderRepositoryInterface::class)));

        $this->app->bind(CancelSalesOrderServiceInterface::class, fn ($app) =>
            new CancelSalesOrderService($app->make(SalesOrderRepositoryInterface::class)));

        $this->app->bind(StartPickingSalesOrderServiceInterface::class, fn ($app) =>
            new StartPickingSalesOrderService($app->make(SalesOrderRepositoryInterface::class)));

        $this->app->bind(StartPackingSalesOrderServiceInterface::class, fn ($app) =>
            new StartPackingSalesOrderService($app->make(SalesOrderRepositoryInterface::class)));

        $this->app->bind(ShipSalesOrderServiceInterface::class, fn ($app) =>
            new ShipSalesOrderService($app->make(SalesOrderRepositoryInterface::class)));

        $this->app->bind(DeliverSalesOrderServiceInterface::class, fn ($app) =>
            new DeliverSalesOrderService($app->make(SalesOrderRepositoryInterface::class)));

        // --- Services: SalesOrderLine ---
        $this->app->bind(CreateSalesOrderLineServiceInterface::class, fn ($app) =>
            new CreateSalesOrderLineService($app->make(SalesOrderLineRepositoryInterface::class)));

        $this->app->bind(FindSalesOrderLineServiceInterface::class, fn ($app) =>
            new FindSalesOrderLineService($app->make(SalesOrderLineRepositoryInterface::class)));

        $this->app->bind(UpdateSalesOrderLineServiceInterface::class, fn ($app) =>
            new UpdateSalesOrderLineService($app->make(SalesOrderLineRepositoryInterface::class)));

        $this->app->bind(DeleteSalesOrderLineServiceInterface::class, fn ($app) =>
            new DeleteSalesOrderLineService($app->make(SalesOrderLineRepositoryInterface::class)));
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
