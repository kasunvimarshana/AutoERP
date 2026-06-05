<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

final class PurchaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/purchase.php', 'purchase');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');
    }
}
