<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

final class VehicleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/vehicle.php', 'vehicle');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
