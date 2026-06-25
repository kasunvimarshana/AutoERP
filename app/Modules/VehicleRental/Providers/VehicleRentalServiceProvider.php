<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Providers;

use Illuminate\Support\ServiceProvider;

final class VehicleRentalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Services use constructor injection and require no manual bindings.
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
