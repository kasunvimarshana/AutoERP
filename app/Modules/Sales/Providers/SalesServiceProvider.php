<?php

declare(strict_types=1);

namespace Modules\Sales\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Invoice\Contracts\InvoiceSourceCancellationHandlerInterface;
use Modules\Sales\Services\Invoice\SalesInvoiceCancellationHandler;
use Modules\Sales\Services\SalesAuthorizationService;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;

final class SalesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag(
            [SalesInvoiceCancellationHandler::class],
            InvoiceSourceCancellationHandlerInterface::TAG,
        );
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('sales', SalesAuthorizationService::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
