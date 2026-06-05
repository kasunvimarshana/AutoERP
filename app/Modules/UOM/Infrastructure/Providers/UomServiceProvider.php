<?php

declare(strict_types=1);

namespace Modules\UOM\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\UOM\Application\Services\UomService;

final class UomServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/uom.php', 'uom');
        $this->app->singleton(UomService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');
    }
}
