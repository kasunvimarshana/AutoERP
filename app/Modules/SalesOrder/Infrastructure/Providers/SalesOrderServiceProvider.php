<?php

namespace Modules\SalesOrder\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\SalesOrder\Application\Contracts\CancelSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\ConfirmSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\CreateSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\StartPackingSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\StartPickingSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Services\CancelSalesOrderService;
use Modules\SalesOrder\Application\Services\ConfirmSalesOrderService;
use Modules\SalesOrder\Application\Services\CreateSalesOrderService;
use Modules\SalesOrder\Application\Services\StartPackingSalesOrderService;
use Modules\SalesOrder\Application\Services\StartPickingSalesOrderService;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderLineRepositoryInterface;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
use Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesOrderLineRepository;
use Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesOrderRepository;

class SalesOrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SalesOrderRepositoryInterface::class, EloquentSalesOrderRepository::class);
        $this->app->bind(SalesOrderLineRepositoryInterface::class, EloquentSalesOrderLineRepository::class);
        $this->app->bind(CreateSalesOrderServiceInterface::class, CreateSalesOrderService::class);
        $this->app->bind(ConfirmSalesOrderServiceInterface::class, ConfirmSalesOrderService::class);
        $this->app->bind(CancelSalesOrderServiceInterface::class, CancelSalesOrderService::class);
        $this->app->bind(StartPickingSalesOrderServiceInterface::class, StartPickingSalesOrderService::class);
        $this->app->bind(StartPackingSalesOrderServiceInterface::class, StartPackingSalesOrderService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
