<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceEngines\RecalculateInvoiceTotalsServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceEngines\TransitionInvoiceStatusServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceLines\CreateInvoiceLineServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceLines\DeleteInvoiceLineServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceLines\GetInvoiceLineServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceLines\ListInvoiceLinesServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceLines\UpdateInvoiceLineServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceReferences\CreateInvoiceReferenceServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceReferences\DeleteInvoiceReferenceServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceReferences\GetInvoiceReferenceServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceReferences\ListInvoiceReferencesServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceReferences\UpdateInvoiceReferenceServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\Invoices\CreateInvoiceServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\Invoices\DeleteInvoiceServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\Invoices\GetInvoiceServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\Invoices\ListInvoicesServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\Invoices\UpdateInvoiceServiceInterface;
use Modules\Invoice\Application\Repositories\InvoiceLineRepositoryInterface;
use Modules\Invoice\Application\Repositories\InvoiceReferenceRepositoryInterface;
use Modules\Invoice\Application\Repositories\InvoiceRepositoryInterface;
use Modules\Invoice\Application\UseCases\InvoiceLines\CreateInvoiceLineService;
use Modules\Invoice\Application\UseCases\InvoiceLines\DeleteInvoiceLineService;
use Modules\Invoice\Application\UseCases\InvoiceLines\GetInvoiceLineService;
use Modules\Invoice\Application\UseCases\InvoiceLines\ListInvoiceLinesService;
use Modules\Invoice\Application\UseCases\InvoiceLines\UpdateInvoiceLineService;
use Modules\Invoice\Application\UseCases\InvoiceReferences\CreateInvoiceReferenceService;
use Modules\Invoice\Application\UseCases\InvoiceReferences\DeleteInvoiceReferenceService;
use Modules\Invoice\Application\UseCases\InvoiceReferences\GetInvoiceReferenceService;
use Modules\Invoice\Application\UseCases\InvoiceReferences\ListInvoiceReferencesService;
use Modules\Invoice\Application\UseCases\InvoiceReferences\UpdateInvoiceReferenceService;
use Modules\Invoice\Application\UseCases\InvoiceEngines\RecalculateInvoiceTotalsService;
use Modules\Invoice\Application\UseCases\InvoiceEngines\TransitionInvoiceStatusService;
use Modules\Invoice\Application\UseCases\Invoices\CreateInvoiceService;
use Modules\Invoice\Application\UseCases\Invoices\DeleteInvoiceService;
use Modules\Invoice\Application\UseCases\Invoices\GetInvoiceService;
use Modules\Invoice\Application\UseCases\Invoices\ListInvoicesService;
use Modules\Invoice\Application\UseCases\Invoices\UpdateInvoiceService;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceLineModel;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceModel;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceReferenceModel;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Repositories\EloquentInvoiceLineRepository;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Repositories\EloquentInvoiceReferenceRepository;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Repositories\EloquentInvoiceRepository;

final class InvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/invoice.php', 'invoice');

        foreach (
            [
                ListInvoicesServiceInterface::class => ListInvoicesService::class,
                GetInvoiceServiceInterface::class => GetInvoiceService::class,
                CreateInvoiceServiceInterface::class => CreateInvoiceService::class,
                UpdateInvoiceServiceInterface::class => UpdateInvoiceService::class,
                DeleteInvoiceServiceInterface::class => DeleteInvoiceService::class,
                ListInvoiceReferencesServiceInterface::class => ListInvoiceReferencesService::class,
                GetInvoiceReferenceServiceInterface::class => GetInvoiceReferenceService::class,
                CreateInvoiceReferenceServiceInterface::class => CreateInvoiceReferenceService::class,
                UpdateInvoiceReferenceServiceInterface::class => UpdateInvoiceReferenceService::class,
                DeleteInvoiceReferenceServiceInterface::class => DeleteInvoiceReferenceService::class,
                ListInvoiceLinesServiceInterface::class => ListInvoiceLinesService::class,
                GetInvoiceLineServiceInterface::class => GetInvoiceLineService::class,
                CreateInvoiceLineServiceInterface::class => CreateInvoiceLineService::class,
                UpdateInvoiceLineServiceInterface::class => UpdateInvoiceLineService::class,
                DeleteInvoiceLineServiceInterface::class => DeleteInvoiceLineService::class,
                RecalculateInvoiceTotalsServiceInterface::class => RecalculateInvoiceTotalsService::class,
                TransitionInvoiceStatusServiceInterface::class => TransitionInvoiceStatusService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(InvoiceRepositoryInterface::class, function (): InvoiceRepositoryInterface {
            return new EloquentInvoiceRepository(new InvoiceModel());
        });
        $this->app->singleton(
            InvoiceReferenceRepositoryInterface::class,
            function (): InvoiceReferenceRepositoryInterface {
                return new EloquentInvoiceReferenceRepository(new InvoiceReferenceModel());
            }
        );
        $this->app->singleton(InvoiceLineRepositoryInterface::class, function (): InvoiceLineRepositoryInterface {
            return new EloquentInvoiceLineRepository(new InvoiceLineModel());
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Eloquent/Migrations');
    }
}
