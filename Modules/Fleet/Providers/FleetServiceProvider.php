<?php

namespace Modules\Fleet\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Fleet\Domain\Contracts\MaintenanceRecordRepositoryInterface;
use Modules\Fleet\Domain\Contracts\VehicleRepositoryInterface;
use Modules\Fleet\Infrastructure\Repositories\MaintenanceRecordRepository;
use Modules\Fleet\Infrastructure\Repositories\VehicleRepository;

class FleetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VehicleRepositoryInterface::class, VehicleRepository::class);
        $this->app->bind(MaintenanceRecordRepositoryInterface::class, MaintenanceRecordRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'fleet');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
