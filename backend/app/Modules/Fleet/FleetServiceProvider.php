<?php

namespace App\Modules\Fleet;

use Illuminate\Support\ServiceProvider;

class FleetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register repositories
        $this->app->bind(
            \App\Modules\Fleet\Repositories\FleetVehicleRepository::class,
            function ($app) {
                return new \App\Modules\Fleet\Repositories\FleetVehicleRepository(
                    new \App\Modules\Fleet\Models\FleetVehicle()
                );
            }
        );

        $this->app->bind(
            \App\Modules\Fleet\Repositories\MaintenanceRecordRepository::class,
            function ($app) {
                return new \App\Modules\Fleet\Repositories\MaintenanceRecordRepository(
                    new \App\Modules\Fleet\Models\MaintenanceRecord()
                );
            }
        );

        // Register services
        $this->app->singleton(
            \App\Modules\Fleet\Services\FleetVehicleService::class,
            function ($app) {
                return new \App\Modules\Fleet\Services\FleetVehicleService(
                    $app->make(\App\Modules\Fleet\Repositories\FleetVehicleRepository::class)
                );
            }
        );

        $this->app->singleton(
            \App\Modules\Fleet\Services\MaintenanceRecordService::class,
            function ($app) {
                return new \App\Modules\Fleet\Services\MaintenanceRecordService(
                    $app->make(\App\Modules\Fleet\Repositories\MaintenanceRecordRepository::class),
                    $app->make(\App\Modules\Fleet\Repositories\FleetVehicleRepository::class)
                );
            }
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
