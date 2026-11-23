<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Configuration\Contracts\ConfigurationDefinitionRegistryInterface;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Invoice\Constants\InvoicePermission;
use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
use Modules\Invoice\Contracts\InvoiceLayoutResolverInterface;
use Modules\Invoice\Contracts\InvoiceSettlementServiceInterface;
use Modules\Invoice\Contracts\InvoiceSourceRestorationHandlerInterface;
use Modules\Invoice\Contracts\InvoiceTaxDocumentProviderInterface;
use Modules\Invoice\Services\InvoiceBalanceProvider;
use Modules\Invoice\Services\InvoiceCustomBladeRenderer;
use Modules\Invoice\Services\InvoiceLayoutProvisioner;
use Modules\Invoice\Services\InvoiceLayoutResolver;
use Modules\Invoice\Services\InvoiceLayoutService;
use Modules\Invoice\Services\InvoiceSettlementService;
use Modules\Invoice\Services\InvoiceSourceRestorationRegistry;
use Modules\Invoice\Services\SupplierBalanceProvider;
use Modules\Invoice\Services\Tax\InvoiceTaxDocumentProvider;
use Modules\Invoice\Validators\InvoiceBladeSecurityValidator;
use Modules\Supplier\Contracts\SupplierBalanceProviderInterface;

final class InvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SupplierBalanceProviderInterface::class, SupplierBalanceProvider::class);
        $this->app->singleton(InvoiceBalanceProviderInterface::class, InvoiceBalanceProvider::class);
        $this->app->singleton(InvoiceSettlementServiceInterface::class, InvoiceSettlementService::class);
        $this->app->singleton(InvoiceTaxDocumentProviderInterface::class, InvoiceTaxDocumentProvider::class);
        $this->app->singleton(InvoiceBladeSecurityValidator::class);
        $this->app->singleton(InvoiceCustomBladeRenderer::class);
        $this->app->bind(InvoiceLayoutResolverInterface::class, InvoiceLayoutResolver::class);
        $this->app->bind(InvoiceLayoutProvisioner::class);
        $this->app->bind(InvoiceLayoutService::class);
        $this->app->singleton(
            InvoiceSourceRestorationRegistry::class,
            static fn ($app): InvoiceSourceRestorationRegistry => new InvoiceSourceRestorationRegistry(
                $app->tagged(InvoiceSourceRestorationHandlerInterface::TAG),
            ),
        );
    }

    public function boot(): void
    {
        $this->app->make(ConfigurationDefinitionRegistryInterface::class)
            ->register('Invoice', require __DIR__.'/../Config/configuration-definitions.php');
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('invoice', InvoicePermission::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
