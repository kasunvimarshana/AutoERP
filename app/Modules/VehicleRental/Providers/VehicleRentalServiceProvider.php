<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Core\Tenancy\TenantFeature;
use Modules\Vehicle\Contracts\VehicleAvailabilityBlockerInterface;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\VehicleUseAvailabilityBlocker;

final class VehicleRentalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(VehicleUseAvailabilityBlocker::class);
        $this->app->tag([VehicleUseAvailabilityBlocker::class], VehicleAvailabilityBlockerInterface::TAG);
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)->register(TenantFeature::VEHICLE_RENTAL, RentalAuthorization::descriptions());
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
    }
}
