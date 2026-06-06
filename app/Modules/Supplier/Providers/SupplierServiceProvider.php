<?php

declare(strict_types=1);

namespace Modules\Supplier\Providers;

use Illuminate\Support\ServiceProvider;

final class SupplierServiceProvider extends ServiceProvider
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
