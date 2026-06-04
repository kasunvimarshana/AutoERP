<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

final class InvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/invoice.php', 'invoice');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');
    }
}
