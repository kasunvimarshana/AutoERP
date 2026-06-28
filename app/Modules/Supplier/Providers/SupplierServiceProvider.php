<?php

declare(strict_types=1);

namespace Modules\Supplier\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Vehicle\Contracts\VehicleOwnerResolverInterface;
use Modules\Supplier\Services\SupplierVehicleOwnerResolver;
use Modules\Supplier\Services\SupplierTaxPartyResolver;
use Modules\Tax\Contracts\TaxPartyResolverInterface;
use Modules\Supplier\Services\SupplierAuthorizationService;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;

final class SupplierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([SupplierVehicleOwnerResolver::class], VehicleOwnerResolverInterface::TAG);
        $this->app->tag([SupplierTaxPartyResolver::class], TaxPartyResolverInterface::TAG);
        //
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('supplier', SupplierAuthorizationService::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
