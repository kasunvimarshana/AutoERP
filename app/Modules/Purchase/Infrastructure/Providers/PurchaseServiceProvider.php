<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Purchase\Application\Contracts\UseCases\GrnHeaders\CreateGrnHeaderServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\GrnHeaders\DeleteGrnHeaderServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\GrnHeaders\GetGrnHeaderServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\GrnHeaders\ListGrnHeadersServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\GrnHeaders\UpdateGrnHeaderServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\GrnLines\CreateGrnLineServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\GrnLines\DeleteGrnLineServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\GrnLines\GetGrnLineServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\GrnLines\ListGrnLinesServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\GrnLines\UpdateGrnLineServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrderLines\CreatePurchaseOrderLineServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrderLines\DeletePurchaseOrderLineServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrderLines\GetPurchaseOrderLineServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrderLines\ListPurchaseOrderLinesServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrderLines\UpdatePurchaseOrderLineServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrders\CreatePurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrders\DeletePurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrders\GetPurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrders\ListPurchaseOrdersServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrders\UpdatePurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturnLines\CreatePurchaseReturnLineServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturnLines\DeletePurchaseReturnLineServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturnLines\GetPurchaseReturnLineServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturnLines\ListPurchaseReturnLinesServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturnLines\UpdatePurchaseReturnLineServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturns\CreatePurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturns\DeletePurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturns\GetPurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturns\ListPurchaseReturnsServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturns\UpdatePurchaseReturnServiceInterface;
use Modules\Purchase\Application\Repositories\GrnHeaderRepositoryInterface;
use Modules\Purchase\Application\Repositories\GrnLineRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseOrderLineRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseOrderRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseReturnLineRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseReturnRepositoryInterface;
use Modules\Purchase\Application\UseCases\GrnHeaders\CreateGrnHeaderService;
use Modules\Purchase\Application\UseCases\GrnHeaders\DeleteGrnHeaderService;
use Modules\Purchase\Application\UseCases\GrnHeaders\GetGrnHeaderService;
use Modules\Purchase\Application\UseCases\GrnHeaders\ListGrnHeadersService;
use Modules\Purchase\Application\UseCases\GrnHeaders\UpdateGrnHeaderService;
use Modules\Purchase\Application\UseCases\GrnLines\CreateGrnLineService;
use Modules\Purchase\Application\UseCases\GrnLines\DeleteGrnLineService;
use Modules\Purchase\Application\UseCases\GrnLines\GetGrnLineService;
use Modules\Purchase\Application\UseCases\GrnLines\ListGrnLinesService;
use Modules\Purchase\Application\UseCases\GrnLines\UpdateGrnLineService;
use Modules\Purchase\Application\UseCases\PurchaseOrderLines\CreatePurchaseOrderLineService;
use Modules\Purchase\Application\UseCases\PurchaseOrderLines\DeletePurchaseOrderLineService;
use Modules\Purchase\Application\UseCases\PurchaseOrderLines\GetPurchaseOrderLineService;
use Modules\Purchase\Application\UseCases\PurchaseOrderLines\ListPurchaseOrderLinesService;
use Modules\Purchase\Application\UseCases\PurchaseOrderLines\UpdatePurchaseOrderLineService;
use Modules\Purchase\Application\UseCases\PurchaseOrders\CreatePurchaseOrderService;
use Modules\Purchase\Application\UseCases\PurchaseOrders\DeletePurchaseOrderService;
use Modules\Purchase\Application\UseCases\PurchaseOrders\GetPurchaseOrderService;
use Modules\Purchase\Application\UseCases\PurchaseOrders\ListPurchaseOrdersService;
use Modules\Purchase\Application\UseCases\PurchaseOrders\UpdatePurchaseOrderService;
use Modules\Purchase\Application\UseCases\PurchaseReturnLines\CreatePurchaseReturnLineService;
use Modules\Purchase\Application\UseCases\PurchaseReturnLines\DeletePurchaseReturnLineService;
use Modules\Purchase\Application\UseCases\PurchaseReturnLines\GetPurchaseReturnLineService;
use Modules\Purchase\Application\UseCases\PurchaseReturnLines\ListPurchaseReturnLinesService;
use Modules\Purchase\Application\UseCases\PurchaseReturnLines\UpdatePurchaseReturnLineService;
use Modules\Purchase\Application\UseCases\PurchaseReturns\CreatePurchaseReturnService;
use Modules\Purchase\Application\UseCases\PurchaseReturns\DeletePurchaseReturnService;
use Modules\Purchase\Application\UseCases\PurchaseReturns\GetPurchaseReturnService;
use Modules\Purchase\Application\UseCases\PurchaseReturns\ListPurchaseReturnsService;
use Modules\Purchase\Application\UseCases\PurchaseReturns\UpdatePurchaseReturnService;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\GrnHeaderModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\GrnLineModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderLineModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseReturnLineModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseReturnModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentGrnHeaderRepository;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentGrnLineRepository;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseOrderLineRepository;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseOrderRepository;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseReturnLineRepository;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseReturnRepository;

final class PurchaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/purchase.php', 'purchase');

        foreach (
            [
                ListPurchaseOrdersServiceInterface::class => ListPurchaseOrdersService::class,
                GetPurchaseOrderServiceInterface::class => GetPurchaseOrderService::class,
                CreatePurchaseOrderServiceInterface::class => CreatePurchaseOrderService::class,
                UpdatePurchaseOrderServiceInterface::class => UpdatePurchaseOrderService::class,
                DeletePurchaseOrderServiceInterface::class => DeletePurchaseOrderService::class,
                ListPurchaseOrderLinesServiceInterface::class => ListPurchaseOrderLinesService::class,
                GetPurchaseOrderLineServiceInterface::class => GetPurchaseOrderLineService::class,
                CreatePurchaseOrderLineServiceInterface::class => CreatePurchaseOrderLineService::class,
                UpdatePurchaseOrderLineServiceInterface::class => UpdatePurchaseOrderLineService::class,
                DeletePurchaseOrderLineServiceInterface::class => DeletePurchaseOrderLineService::class,
                ListGrnHeadersServiceInterface::class => ListGrnHeadersService::class,
                GetGrnHeaderServiceInterface::class => GetGrnHeaderService::class,
                CreateGrnHeaderServiceInterface::class => CreateGrnHeaderService::class,
                UpdateGrnHeaderServiceInterface::class => UpdateGrnHeaderService::class,
                DeleteGrnHeaderServiceInterface::class => DeleteGrnHeaderService::class,
                ListGrnLinesServiceInterface::class => ListGrnLinesService::class,
                GetGrnLineServiceInterface::class => GetGrnLineService::class,
                CreateGrnLineServiceInterface::class => CreateGrnLineService::class,
                UpdateGrnLineServiceInterface::class => UpdateGrnLineService::class,
                DeleteGrnLineServiceInterface::class => DeleteGrnLineService::class,
                ListPurchaseReturnsServiceInterface::class => ListPurchaseReturnsService::class,
                GetPurchaseReturnServiceInterface::class => GetPurchaseReturnService::class,
                CreatePurchaseReturnServiceInterface::class => CreatePurchaseReturnService::class,
                UpdatePurchaseReturnServiceInterface::class => UpdatePurchaseReturnService::class,
                DeletePurchaseReturnServiceInterface::class => DeletePurchaseReturnService::class,
                ListPurchaseReturnLinesServiceInterface::class => ListPurchaseReturnLinesService::class,
                GetPurchaseReturnLineServiceInterface::class => GetPurchaseReturnLineService::class,
                CreatePurchaseReturnLineServiceInterface::class => CreatePurchaseReturnLineService::class,
                UpdatePurchaseReturnLineServiceInterface::class => UpdatePurchaseReturnLineService::class,
                DeletePurchaseReturnLineServiceInterface::class => DeletePurchaseReturnLineService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(PurchaseOrderRepositoryInterface::class, function (): PurchaseOrderRepositoryInterface {
            return new EloquentPurchaseOrderRepository(new PurchaseOrderModel());
        });
        $this->app->singleton(PurchaseOrderLineRepositoryInterface::class, function (): PurchaseOrderLineRepositoryInterface {
            return new EloquentPurchaseOrderLineRepository(new PurchaseOrderLineModel());
        });
        $this->app->singleton(GrnHeaderRepositoryInterface::class, function (): GrnHeaderRepositoryInterface {
            return new EloquentGrnHeaderRepository(new GrnHeaderModel());
        });
        $this->app->singleton(GrnLineRepositoryInterface::class, function (): GrnLineRepositoryInterface {
            return new EloquentGrnLineRepository(new GrnLineModel());
        });
        $this->app->singleton(PurchaseReturnRepositoryInterface::class, function (): PurchaseReturnRepositoryInterface {
            return new EloquentPurchaseReturnRepository(new PurchaseReturnModel());
        });
        $this->app->singleton(PurchaseReturnLineRepositoryInterface::class, function (): PurchaseReturnLineRepositoryInterface {
            return new EloquentPurchaseReturnLineRepository(new PurchaseReturnLineModel());
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Eloquent/Migrations');
    }
}