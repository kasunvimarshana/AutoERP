<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Purchase\Application\Contracts\ApprovePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\ConfirmPurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\CreateGrnServiceInterface;
use Modules\Purchase\Application\Contracts\CreatePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\CreatePurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\CreatePurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\DeleteGrnServiceInterface;
use Modules\Purchase\Application\Contracts\DeletePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\DeletePurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\DeletePurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\FindGrnServiceInterface;
use Modules\Purchase\Application\Contracts\FindPurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\FindPurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\FindPurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\PostGrnServiceInterface;
use Modules\Purchase\Application\Contracts\PostPurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\UpdateGrnServiceInterface;
use Modules\Purchase\Application\Contracts\UpdatePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\UpdatePurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\UpdatePurchaseReturnServiceInterface;
use Modules\Purchase\Application\Services\ApprovePurchaseInvoiceService;
use Modules\Purchase\Application\Services\ConfirmPurchaseOrderService;
use Modules\Purchase\Application\Services\CreateGrnService;
use Modules\Purchase\Application\Services\CreatePurchaseInvoiceService;
use Modules\Purchase\Application\Services\CreatePurchaseOrderService;
use Modules\Purchase\Application\Services\CreatePurchaseReturnService;
use Modules\Purchase\Application\Services\DeleteGrnService;
use Modules\Purchase\Application\Services\DeletePurchaseInvoiceService;
use Modules\Purchase\Application\Services\DeletePurchaseOrderService;
use Modules\Purchase\Application\Services\DeletePurchaseReturnService;
use Modules\Purchase\Application\Services\FindGrnService;
use Modules\Purchase\Application\Services\FindPurchaseInvoiceService;
use Modules\Purchase\Application\Services\FindPurchaseOrderService;
use Modules\Purchase\Application\Services\FindPurchaseReturnService;
use Modules\Purchase\Application\Services\PostGrnService;
use Modules\Purchase\Application\Services\PostPurchaseReturnService;
use Modules\Purchase\Application\Services\UpdateGrnService;
use Modules\Purchase\Application\Services\UpdatePurchaseInvoiceService;
use Modules\Purchase\Application\Services\UpdatePurchaseOrderService;
use Modules\Purchase\Application\Services\UpdatePurchaseReturnService;
use Modules\Purchase\Domain\RepositoryInterfaces\GrnHeaderRepositoryInterface;
use Modules\Purchase\Domain\RepositoryInterfaces\GrnLineRepositoryInterface;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseInvoiceLineRepositoryInterface;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseInvoiceRepositoryInterface;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseOrderLineRepositoryInterface;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseReturnLineRepositoryInterface;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseReturnRepositoryInterface;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentGrnHeaderRepository;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentGrnLineRepository;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseInvoiceLineRepository;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseInvoiceRepository;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseOrderLineRepository;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseOrderRepository;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseReturnLineRepository;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseReturnRepository;

class PurchaseServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $repositoryBindings = [
            PurchaseOrderRepositoryInterface::class => EloquentPurchaseOrderRepository::class,
            PurchaseOrderLineRepositoryInterface::class => EloquentPurchaseOrderLineRepository::class,
            GrnHeaderRepositoryInterface::class => EloquentGrnHeaderRepository::class,
            GrnLineRepositoryInterface::class => EloquentGrnLineRepository::class,
            PurchaseInvoiceRepositoryInterface::class => EloquentPurchaseInvoiceRepository::class,
            PurchaseInvoiceLineRepositoryInterface::class => EloquentPurchaseInvoiceLineRepository::class,
            PurchaseReturnRepositoryInterface::class => EloquentPurchaseReturnRepository::class,
            PurchaseReturnLineRepositoryInterface::class => EloquentPurchaseReturnLineRepository::class,
        ];

        foreach ($repositoryBindings as $contract => $impl) {
            $this->app->bind($contract, $impl);
        }

        $serviceBindings = [
            CreatePurchaseOrderServiceInterface::class => CreatePurchaseOrderService::class,
            FindPurchaseOrderServiceInterface::class => FindPurchaseOrderService::class,
            UpdatePurchaseOrderServiceInterface::class => UpdatePurchaseOrderService::class,
            DeletePurchaseOrderServiceInterface::class => DeletePurchaseOrderService::class,
            ConfirmPurchaseOrderServiceInterface::class => ConfirmPurchaseOrderService::class,
            CreateGrnServiceInterface::class => CreateGrnService::class,
            FindGrnServiceInterface::class => FindGrnService::class,
            UpdateGrnServiceInterface::class => UpdateGrnService::class,
            DeleteGrnServiceInterface::class => DeleteGrnService::class,
            PostGrnServiceInterface::class => PostGrnService::class,
            CreatePurchaseInvoiceServiceInterface::class => CreatePurchaseInvoiceService::class,
            FindPurchaseInvoiceServiceInterface::class => FindPurchaseInvoiceService::class,
            UpdatePurchaseInvoiceServiceInterface::class => UpdatePurchaseInvoiceService::class,
            DeletePurchaseInvoiceServiceInterface::class => DeletePurchaseInvoiceService::class,
            ApprovePurchaseInvoiceServiceInterface::class => ApprovePurchaseInvoiceService::class,
            CreatePurchaseReturnServiceInterface::class => CreatePurchaseReturnService::class,
            FindPurchaseReturnServiceInterface::class => FindPurchaseReturnService::class,
            UpdatePurchaseReturnServiceInterface::class => UpdatePurchaseReturnService::class,
            DeletePurchaseReturnServiceInterface::class => DeletePurchaseReturnService::class,
            PostPurchaseReturnServiceInterface::class => PostPurchaseReturnService::class,
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
