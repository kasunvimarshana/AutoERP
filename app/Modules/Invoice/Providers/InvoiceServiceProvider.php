<?php

declare(strict_types=1);

namespace Modules\Invoice\Providers;

use Illuminate\Support\ServiceProvider;

final class InvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
