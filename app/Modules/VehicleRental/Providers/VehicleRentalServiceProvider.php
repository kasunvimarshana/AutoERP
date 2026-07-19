<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Vehicle\Contracts\VehicleAvailabilityBlockerInterface;
use Modules\VehicleRental\Constants\VehicleRentalPermission;
use Modules\VehicleRental\Services\Availability\RentalLegalDocumentAvailabilityBlocker;

final class VehicleRentalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/vehicle-rental.php', 'vehicle-rental');
        $this->app->tag(
            [RentalLegalDocumentAvailabilityBlocker::class],
            VehicleAvailabilityBlockerInterface::TAG,
        );
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('vehicle-rental', VehicleRentalPermission::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
