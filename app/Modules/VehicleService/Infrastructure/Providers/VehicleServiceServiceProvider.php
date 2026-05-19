<?php

namespace Modules\VehicleService\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

class VehicleServiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
