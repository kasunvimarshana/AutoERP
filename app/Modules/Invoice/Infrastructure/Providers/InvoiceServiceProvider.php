<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

final class InvoiceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Eloquent/Migrations');
    }
}
