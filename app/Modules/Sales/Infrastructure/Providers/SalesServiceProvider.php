<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Sales\Application\Contracts\ApproveSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\CancelSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\ConfirmSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\CreateSalesInvoiceServiceInterface;
use Modules\Sales\Application\Contracts\CreateSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\CreateSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\CreateShipmentServiceInterface;
use Modules\Sales\Application\Contracts\DeleteSalesInvoiceServiceInterface;
use Modules\Sales\Application\Contracts\DeleteSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\DeleteSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\DeleteShipmentServiceInterface;
use Modules\Sales\Application\Contracts\FindSalesInvoiceServiceInterface;
use Modules\Sales\Application\Contracts\FindSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\FindSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\FindShipmentServiceInterface;
use Modules\Sales\Application\Contracts\PostSalesInvoiceServiceInterface;
use Modules\Sales\Application\Contracts\ProcessShipmentServiceInterface;
use Modules\Sales\Application\Contracts\ReceiveSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\UpdateSalesInvoiceServiceInterface;
use Modules\Sales\Application\Contracts\UpdateSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\UpdateSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\UpdateShipmentServiceInterface;
use Modules\Sales\Application\Services\ApproveSalesReturnService;
use Modules\Sales\Application\Services\CancelSalesOrderService;
use Modules\Sales\Application\Services\ConfirmSalesOrderService;
use Modules\Sales\Application\Services\CreateSalesInvoiceService;
use Modules\Sales\Application\Services\CreateSalesOrderService;
use Modules\Sales\Application\Services\CreateSalesReturnService;
use Modules\Sales\Application\Services\CreateShipmentService;
use Modules\Sales\Application\Services\DeleteSalesInvoiceService;
use Modules\Sales\Application\Services\DeleteSalesOrderService;
use Modules\Sales\Application\Services\DeleteSalesReturnService;
use Modules\Sales\Application\Services\DeleteShipmentService;
use Modules\Sales\Application\Services\FindSalesInvoiceService;
use Modules\Sales\Application\Services\FindSalesOrderService;
use Modules\Sales\Application\Services\FindSalesReturnService;
use Modules\Sales\Application\Services\FindShipmentService;
use Modules\Sales\Application\Services\PostSalesInvoiceService;
use Modules\Sales\Application\Services\ProcessShipmentService;
use Modules\Sales\Application\Services\ReceiveSalesReturnService;
use Modules\Sales\Application\Services\UpdateSalesInvoiceService;
use Modules\Sales\Application\Services\UpdateSalesOrderService;
use Modules\Sales\Application\Services\UpdateSalesReturnService;
use Modules\Sales\Application\Services\UpdateShipmentService;
use Modules\Sales\Domain\RepositoryInterfaces\SalesInvoiceRepositoryInterface;
use Modules\Sales\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
use Modules\Sales\Domain\RepositoryInterfaces\SalesReturnRepositoryInterface;
use Modules\Sales\Domain\RepositoryInterfaces\ShipmentRepositoryInterface;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesInvoiceRepository;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesOrderRepository;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesReturnRepository;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentShipmentRepository;

class SalesServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $repositoryBindings = [
            SalesOrderRepositoryInterface::class => EloquentSalesOrderRepository::class,
            SalesInvoiceRepositoryInterface::class => EloquentSalesInvoiceRepository::class,
            ShipmentRepositoryInterface::class => EloquentShipmentRepository::class,
            SalesReturnRepositoryInterface::class => EloquentSalesReturnRepository::class,
        ];

        foreach ($repositoryBindings as $contract => $impl) {
            $this->app->bind($contract, $impl);
        }

        $serviceBindings = [
            CreateSalesOrderServiceInterface::class => CreateSalesOrderService::class,
            FindSalesOrderServiceInterface::class => FindSalesOrderService::class,
            UpdateSalesOrderServiceInterface::class => UpdateSalesOrderService::class,
            DeleteSalesOrderServiceInterface::class => DeleteSalesOrderService::class,
            ConfirmSalesOrderServiceInterface::class => ConfirmSalesOrderService::class,
            CancelSalesOrderServiceInterface::class => CancelSalesOrderService::class,
            CreateShipmentServiceInterface::class => CreateShipmentService::class,
            FindShipmentServiceInterface::class => FindShipmentService::class,
            UpdateShipmentServiceInterface::class => UpdateShipmentService::class,
            DeleteShipmentServiceInterface::class => DeleteShipmentService::class,
            ProcessShipmentServiceInterface::class => ProcessShipmentService::class,
            CreateSalesInvoiceServiceInterface::class => CreateSalesInvoiceService::class,
            FindSalesInvoiceServiceInterface::class => FindSalesInvoiceService::class,
            UpdateSalesInvoiceServiceInterface::class => UpdateSalesInvoiceService::class,
            DeleteSalesInvoiceServiceInterface::class => DeleteSalesInvoiceService::class,
            PostSalesInvoiceServiceInterface::class => PostSalesInvoiceService::class,
            CreateSalesReturnServiceInterface::class => CreateSalesReturnService::class,
            FindSalesReturnServiceInterface::class => FindSalesReturnService::class,
            UpdateSalesReturnServiceInterface::class => UpdateSalesReturnService::class,
            DeleteSalesReturnServiceInterface::class => DeleteSalesReturnService::class,
            ApproveSalesReturnServiceInterface::class => ApproveSalesReturnService::class,
            ReceiveSalesReturnServiceInterface::class => ReceiveSalesReturnService::class,
        ];

        foreach ($serviceBindings as $contract => $impl) {
            $this->app->bind($contract, $impl);
        }
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}
