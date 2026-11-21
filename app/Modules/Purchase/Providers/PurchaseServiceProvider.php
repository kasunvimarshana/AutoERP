<?php

declare(strict_types=1);

namespace Modules\Purchase\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Invoice\Contracts\InvoiceSourceRestorationHandlerInterface;
use Modules\Purchase\Services\Invoice\PurchaseInvoiceRestorationHandler;
use Modules\Purchase\Services\PurchaseAuthorizationService;

final class PurchaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag(
            [PurchaseInvoiceRestorationHandler::class],
            InvoiceSourceRestorationHandlerInterface::TAG,
        );
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('purchase', PurchaseAuthorizationService::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
