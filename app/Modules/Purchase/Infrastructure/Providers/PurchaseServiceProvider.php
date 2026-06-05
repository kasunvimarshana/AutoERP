<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Purchase\Application\Services\PurchaseService;

final class PurchaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/purchase.php', 'purchase');
        $this->app->singleton(PurchaseService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
