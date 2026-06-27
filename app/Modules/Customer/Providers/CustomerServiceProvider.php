<?php

declare(strict_types=1);

namespace Modules\Customer\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Customer\Services\CustomerAuthorizationService;
use Modules\Customer\Services\CustomerVehicleOwnerResolver;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;

final class CustomerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([CustomerVehicleOwnerResolver::class], 'vehicle.owner_resolvers');
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('customer', CustomerAuthorizationService::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
