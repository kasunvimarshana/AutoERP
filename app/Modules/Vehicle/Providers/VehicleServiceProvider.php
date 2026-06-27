<?php

declare(strict_types=1);

namespace Modules\Vehicle\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Vehicle\Services\CompanyVehicleOwnerResolver;
use Modules\Vehicle\Services\VehicleAuthorizationService;
use Modules\Vehicle\Services\VehicleOwnerDirectory;

final class VehicleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([CompanyVehicleOwnerResolver::class], 'vehicle.owner_resolvers');
        $this->app->when(VehicleOwnerDirectory::class)
            ->needs('$resolvers')
            ->giveTagged('vehicle.owner_resolvers');
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('vehicle', VehicleAuthorizationService::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
