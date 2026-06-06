<?php

declare(strict_types=1);

namespace Modules\Invoice\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
use Modules\Invoice\Contracts\InvoiceSettlementServiceInterface;
use Modules\Invoice\Services\InvoiceBalanceProvider;
use Modules\Invoice\Services\InvoiceSettlementService;

final class InvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InvoiceBalanceProviderInterface::class, InvoiceBalanceProvider::class);
        $this->app->singleton(InvoiceSettlementServiceInterface::class, InvoiceSettlementService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
