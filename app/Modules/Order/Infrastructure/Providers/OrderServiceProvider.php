<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Order\Application\Contracts\OrderLineServiceInterface;
use Modules\Order\Application\Contracts\PurchaseOrderServiceInterface;
use Modules\Order\Application\Contracts\SalesOrderServiceInterface;
use Modules\Order\Application\Services\OrderLineService;
use Modules\Order\Application\Services\PurchaseOrderService;
use Modules\Order\Application\Services\SalesOrderService;
use Modules\Order\Domain\RepositoryInterfaces\OrderLineRepositoryInterface;
use Modules\Order\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;
use Modules\Order\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrderLineRepository;
use Modules\Order\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseOrderRepository;
use Modules\Order\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesOrderRepository;

class OrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PurchaseOrderRepositoryInterface::class, EloquentPurchaseOrderRepository::class);
        $this->app->bind(SalesOrderRepositoryInterface::class, EloquentSalesOrderRepository::class);
        $this->app->bind(OrderLineRepositoryInterface::class, EloquentOrderLineRepository::class);

        $this->app->bind(PurchaseOrderServiceInterface::class, PurchaseOrderService::class);
        $this->app->bind(SalesOrderServiceInterface::class, SalesOrderService::class);
        $this->app->bind(OrderLineServiceInterface::class, OrderLineService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
