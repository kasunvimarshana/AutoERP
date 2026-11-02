<?php

declare(strict_types=1);

namespace Modules\Vehicle\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Vehicle\Contracts\VehicleOwnerResolverInterface;
use Modules\Vehicle\Services\Ownership\VehicleOwnerResolverRegistry;
use Modules\Vehicle\Services\VehicleAuthorizationService;

final class VehicleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(VehicleOwnerResolverRegistry::class,
            fn ($app): VehicleOwnerResolverRegistry => new VehicleOwnerResolverRegistry(
                $app->tagged(VehicleOwnerResolverInterface::TAG),
            ),
        );
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('vehicle', VehicleAuthorizationService::descriptions());
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
