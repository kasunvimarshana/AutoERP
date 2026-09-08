<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Core\Tenancy\TenantFeature;
use Modules\VehicleRental\Services\RentalAuthorization;

final class VehicleRentalServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)->register(TenantFeature::VEHICLE_RENTAL, RentalAuthorization::descriptions());
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
    }
}
