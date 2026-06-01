<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Sales\Application\Contracts\Services\SalesAmountCalculatorInterface;
use Modules\Sales\Application\Contracts\Services\SalesIntegrationServiceInterface;
use Modules\Sales\Application\Contracts\Services\SalesManagementServiceInterface;
use Modules\Sales\Application\Contracts\Services\SalesWorkflowServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\GdnHeaders\CreateGdnHeaderServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\GdnHeaders\DeleteGdnHeaderServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\GdnHeaders\GetGdnHeaderServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\GdnHeaders\ListGdnHeadersServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\GdnHeaders\UpdateGdnHeaderServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\GdnLines\CreateGdnLineServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\GdnLines\DeleteGdnLineServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\GdnLines\GetGdnLineServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\GdnLines\ListGdnLinesServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\GdnLines\UpdateGdnLineServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesOrderLines\CreateSalesOrderLineServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesOrderLines\DeleteSalesOrderLineServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesOrderLines\GetSalesOrderLineServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesOrderLines\ListSalesOrderLinesServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesOrderLines\UpdateSalesOrderLineServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesOrders\CreateSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesOrders\DeleteSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesOrders\GetSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesOrders\ListSalesOrdersServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesOrders\UpdateSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesReturnLines\CreateSalesReturnLineServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesReturnLines\DeleteSalesReturnLineServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesReturnLines\GetSalesReturnLineServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesReturnLines\ListSalesReturnLinesServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesReturnLines\UpdateSalesReturnLineServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesReturns\CreateSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesReturns\DeleteSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesReturns\GetSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesReturns\ListSalesReturnsServiceInterface;
use Modules\Sales\Application\Contracts\UseCases\SalesReturns\UpdateSalesReturnServiceInterface;
use Modules\Sales\Application\Repositories\GdnHeaderRepositoryInterface;
use Modules\Sales\Application\Repositories\GdnLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesDocumentLinkRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesOrderLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesOrderRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesPaymentAllocationRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesReturnLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesReturnRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesSettingRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesStatusHistoryRepositoryInterface;
use Modules\Sales\Application\Services\SalesAmountCalculator;
use Modules\Sales\Application\Services\SalesIntegrationService;
use Modules\Sales\Application\Services\SalesManagementService;
use Modules\Sales\Application\Services\SalesWorkflowService;
use Modules\Sales\Application\UseCases\GdnHeaders\CreateGdnHeaderService;
use Modules\Sales\Application\UseCases\GdnHeaders\DeleteGdnHeaderService;
use Modules\Sales\Application\UseCases\GdnHeaders\GetGdnHeaderService;
use Modules\Sales\Application\UseCases\GdnHeaders\ListGdnHeadersService;
use Modules\Sales\Application\UseCases\GdnHeaders\UpdateGdnHeaderService;
use Modules\Sales\Application\UseCases\GdnLines\CreateGdnLineService;
use Modules\Sales\Application\UseCases\GdnLines\DeleteGdnLineService;
use Modules\Sales\Application\UseCases\GdnLines\GetGdnLineService;
use Modules\Sales\Application\UseCases\GdnLines\ListGdnLinesService;
use Modules\Sales\Application\UseCases\GdnLines\UpdateGdnLineService;
use Modules\Sales\Application\UseCases\SalesOrderLines\CreateSalesOrderLineService;
use Modules\Sales\Application\UseCases\SalesOrderLines\DeleteSalesOrderLineService;
use Modules\Sales\Application\UseCases\SalesOrderLines\GetSalesOrderLineService;
use Modules\Sales\Application\UseCases\SalesOrderLines\ListSalesOrderLinesService;
use Modules\Sales\Application\UseCases\SalesOrderLines\UpdateSalesOrderLineService;
use Modules\Sales\Application\UseCases\SalesOrders\CreateSalesOrderService;
use Modules\Sales\Application\UseCases\SalesOrders\DeleteSalesOrderService;
use Modules\Sales\Application\UseCases\SalesOrders\GetSalesOrderService;
use Modules\Sales\Application\UseCases\SalesOrders\ListSalesOrdersService;
use Modules\Sales\Application\UseCases\SalesOrders\UpdateSalesOrderService;
use Modules\Sales\Application\UseCases\SalesReturnLines\CreateSalesReturnLineService;
use Modules\Sales\Application\UseCases\SalesReturnLines\DeleteSalesReturnLineService;
use Modules\Sales\Application\UseCases\SalesReturnLines\GetSalesReturnLineService;
use Modules\Sales\Application\UseCases\SalesReturnLines\ListSalesReturnLinesService;
use Modules\Sales\Application\UseCases\SalesReturnLines\UpdateSalesReturnLineService;
use Modules\Sales\Application\UseCases\SalesReturns\CreateSalesReturnService;
use Modules\Sales\Application\UseCases\SalesReturns\DeleteSalesReturnService;
use Modules\Sales\Application\UseCases\SalesReturns\GetSalesReturnService;
use Modules\Sales\Application\UseCases\SalesReturns\ListSalesReturnsService;
use Modules\Sales\Application\UseCases\SalesReturns\UpdateSalesReturnService;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\GdnHeaderModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\GdnLineModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesDocumentLinkModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesOrderLineModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesPaymentAllocationModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesReturnLineModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesReturnModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesSettingModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesStatusHistoryModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentGdnHeaderRepository;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentGdnLineRepository;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesDocumentLinkRepository;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesOrderLineRepository;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesOrderRepository;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesPaymentAllocationRepository;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesReturnLineRepository;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesReturnRepository;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesSettingRepository;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesStatusHistoryRepository;

final class SalesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/sales.php', 'sales');

        foreach (
            [
                ListSalesOrdersServiceInterface::class => ListSalesOrdersService::class,
                GetSalesOrderServiceInterface::class => GetSalesOrderService::class,
                CreateSalesOrderServiceInterface::class => CreateSalesOrderService::class,
                UpdateSalesOrderServiceInterface::class => UpdateSalesOrderService::class,
                DeleteSalesOrderServiceInterface::class => DeleteSalesOrderService::class,
                ListSalesOrderLinesServiceInterface::class => ListSalesOrderLinesService::class,
                GetSalesOrderLineServiceInterface::class => GetSalesOrderLineService::class,
                CreateSalesOrderLineServiceInterface::class => CreateSalesOrderLineService::class,
                UpdateSalesOrderLineServiceInterface::class => UpdateSalesOrderLineService::class,
                DeleteSalesOrderLineServiceInterface::class => DeleteSalesOrderLineService::class,
                ListGdnHeadersServiceInterface::class => ListGdnHeadersService::class,
                GetGdnHeaderServiceInterface::class => GetGdnHeaderService::class,
                CreateGdnHeaderServiceInterface::class => CreateGdnHeaderService::class,
                UpdateGdnHeaderServiceInterface::class => UpdateGdnHeaderService::class,
                DeleteGdnHeaderServiceInterface::class => DeleteGdnHeaderService::class,
                ListGdnLinesServiceInterface::class => ListGdnLinesService::class,
                GetGdnLineServiceInterface::class => GetGdnLineService::class,
                CreateGdnLineServiceInterface::class => CreateGdnLineService::class,
                UpdateGdnLineServiceInterface::class => UpdateGdnLineService::class,
                DeleteGdnLineServiceInterface::class => DeleteGdnLineService::class,
                ListSalesReturnsServiceInterface::class => ListSalesReturnsService::class,
                GetSalesReturnServiceInterface::class => GetSalesReturnService::class,
                CreateSalesReturnServiceInterface::class => CreateSalesReturnService::class,
                UpdateSalesReturnServiceInterface::class => UpdateSalesReturnService::class,
                DeleteSalesReturnServiceInterface::class => DeleteSalesReturnService::class,
                ListSalesReturnLinesServiceInterface::class => ListSalesReturnLinesService::class,
                GetSalesReturnLineServiceInterface::class => GetSalesReturnLineService::class,
                CreateSalesReturnLineServiceInterface::class => CreateSalesReturnLineService::class,
                UpdateSalesReturnLineServiceInterface::class => UpdateSalesReturnLineService::class,
                DeleteSalesReturnLineServiceInterface::class => DeleteSalesReturnLineService::class,
                SalesAmountCalculatorInterface::class => SalesAmountCalculator::class,
                SalesManagementServiceInterface::class => SalesManagementService::class,
                SalesIntegrationServiceInterface::class => SalesIntegrationService::class,
                SalesWorkflowServiceInterface::class => SalesWorkflowService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(SalesOrderRepositoryInterface::class, function (): SalesOrderRepositoryInterface {
            return new EloquentSalesOrderRepository(new SalesOrderModel);
        });
        $this->app->singleton(SalesOrderLineRepositoryInterface::class, function (): SalesOrderLineRepositoryInterface {
            return new EloquentSalesOrderLineRepository(new SalesOrderLineModel);
        });
        $this->app->singleton(GdnHeaderRepositoryInterface::class, function (): GdnHeaderRepositoryInterface {
            return new EloquentGdnHeaderRepository(new GdnHeaderModel);
        });
        $this->app->singleton(GdnLineRepositoryInterface::class, function (): GdnLineRepositoryInterface {
            return new EloquentGdnLineRepository(new GdnLineModel);
        });
        $this->app->singleton(SalesReturnRepositoryInterface::class, function (): SalesReturnRepositoryInterface {
            return new EloquentSalesReturnRepository(new SalesReturnModel);
        });
        $this->app->singleton(
            SalesReturnLineRepositoryInterface::class,
            function (): SalesReturnLineRepositoryInterface {
                return new EloquentSalesReturnLineRepository(new SalesReturnLineModel);
            }
        );
        $this->app->singleton(SalesSettingRepositoryInterface::class, function (): SalesSettingRepositoryInterface {
            return new EloquentSalesSettingRepository(new SalesSettingModel);
        });
        $this->app->singleton(
            SalesDocumentLinkRepositoryInterface::class,
            function (): SalesDocumentLinkRepositoryInterface {
                return new EloquentSalesDocumentLinkRepository(new SalesDocumentLinkModel);
            }
        );
        $this->app->singleton(
            SalesPaymentAllocationRepositoryInterface::class,
            function (): SalesPaymentAllocationRepositoryInterface {
                return new EloquentSalesPaymentAllocationRepository(new SalesPaymentAllocationModel);
            }
        );
        $this->app->singleton(
            SalesStatusHistoryRepositoryInterface::class,
            function (): SalesStatusHistoryRepositoryInterface {
                return new EloquentSalesStatusHistoryRepository(new SalesStatusHistoryModel);
            }
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');
    }
}
