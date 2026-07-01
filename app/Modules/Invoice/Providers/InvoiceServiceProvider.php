<?php

declare(strict_types=1);

namespace Modules\Invoice\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Invoice\Constants\InvoicePermission;
use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
use Modules\Invoice\Contracts\InvoiceSettlementServiceInterface;
use Modules\Invoice\Contracts\InvoiceTaxDocumentProviderInterface;
use Modules\Invoice\Services\InvoiceBalanceProvider;
use Modules\Invoice\Services\InvoiceSettlementService;
use Modules\Invoice\Services\InvoiceSourceCancellationRegistry;
use Modules\Invoice\Services\Tax\InvoiceTaxDocumentProvider;

final class InvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InvoiceBalanceProviderInterface::class, InvoiceBalanceProvider::class);
        $this->app->singleton(InvoiceSettlementServiceInterface::class, InvoiceSettlementService::class);
        $this->app->singleton(InvoiceTaxDocumentProviderInterface::class, InvoiceTaxDocumentProvider::class);
        $this->app->singleton(InvoiceSourceCancellationRegistry::class, static fn ($app): InvoiceSourceCancellationRegistry => new InvoiceSourceCancellationRegistry(
            $app->tagged(\Modules\Invoice\Contracts\InvoiceSourceCancellationHandlerInterface::TAG),
        ));
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('invoice', InvoicePermission::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
