<?php

declare(strict_types=1);

namespace Modules\Customer\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Vehicle\Contracts\VehicleOwnerResolverInterface;
use Modules\Customer\Services\CustomerVehicleOwnerResolver;
use Modules\Customer\Services\CustomerTaxPartyResolver;
use Modules\Tax\Contracts\TaxPartyResolverInterface;
use Modules\Customer\Services\CustomerAuthorizationService;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;

final class CustomerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([CustomerVehicleOwnerResolver::class], VehicleOwnerResolverInterface::TAG);
        $this->app->tag([CustomerTaxPartyResolver::class], TaxPartyResolverInterface::TAG);
        //
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('customer', CustomerAuthorizationService::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
