<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

final class VehicleServiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/vehicle_service.php', 'vehicle_service');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Eloquent/Migrations');
    }
}
