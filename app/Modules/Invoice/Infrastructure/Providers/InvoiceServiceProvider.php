<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Invoice\Application\Services\InvoiceCalculationService;
use Modules\Invoice\Application\Services\InvoiceService;
use Modules\Invoice\Application\Services\InvoiceStatusService;

final class InvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InvoiceCalculationService::class);
        $this->app->singleton(InvoiceStatusService::class);
        $this->app->singleton(InvoiceService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');
    }
}
