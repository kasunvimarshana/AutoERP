<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Order\Application\Contracts\PurchaseOrderServiceInterface;
use Modules\Order\Application\Contracts\ReturnOrderServiceInterface;
use Modules\Order\Application\Contracts\SalesOrderServiceInterface;
use Modules\Order\Application\Services\PurchaseOrderService;
use Modules\Order\Application\Services\ReturnOrderService;
use Modules\Order\Application\Services\SalesOrderService;
use Modules\Order\Domain\Contracts\Repositories\PurchaseOrderRepositoryInterface;
use Modules\Order\Domain\Contracts\Repositories\ReturnOrderRepositoryInterface;
use Modules\Order\Domain\Contracts\Repositories\SalesOrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;
use Modules\Order\Infrastructure\Persistence\Eloquent\Models\ReturnOrderModel;
use Modules\Order\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel;
use Modules\Order\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseOrderRepository;
use Modules\Order\Infrastructure\Persistence\Eloquent\Repositories\EloquentReturnOrderRepository;
use Modules\Order\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesOrderRepository;

class OrderServiceProvider extends ServiceProvider
{
    /**
     * Register Order module bindings.
     */
    public function register(): void
    {
        // Repositories
        $this->app->bind(SalesOrderRepositoryInterface::class, function ($app) {
            return new EloquentSalesOrderRepository($app->make(SalesOrderModel::class));
        });

        $this->app->bind(PurchaseOrderRepositoryInterface::class, function ($app) {
            return new EloquentPurchaseOrderRepository($app->make(PurchaseOrderModel::class));
        });

        $this->app->bind(ReturnOrderRepositoryInterface::class, function ($app) {
            return new EloquentReturnOrderRepository($app->make(ReturnOrderModel::class));
        });

        // Services
        $this->app->bind(SalesOrderServiceInterface::class, function ($app) {
            return new SalesOrderService($app->make(SalesOrderRepositoryInterface::class));
        });

        $this->app->bind(PurchaseOrderServiceInterface::class, function ($app) {
            return new PurchaseOrderService($app->make(PurchaseOrderRepositoryInterface::class));
        });

        $this->app->bind(ReturnOrderServiceInterface::class, function ($app) {
            return new ReturnOrderService($app->make(ReturnOrderRepositoryInterface::class));
        });
    }

    /**
     * Boot the Order service provider.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
