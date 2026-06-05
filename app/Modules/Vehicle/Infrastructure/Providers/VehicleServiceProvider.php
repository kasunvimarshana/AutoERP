<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Vehicle\Application\Services\VehicleService;

final class VehicleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/vehicle.php', 'vehicle');
        $this->app->singleton(VehicleService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');
    }
}
