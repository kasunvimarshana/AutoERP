<?php

declare(strict_types=1);

namespace Modules\VehicleService\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Invoice\Contracts\InvoiceSourceRestorationHandlerInterface;
use Modules\Vehicle\Contracts\VehicleAvailabilityBlockerInterface;
use Modules\VehicleService\Constants\VehicleServicePermission;
use Modules\VehicleService\Services\Availability\VehicleServiceAvailabilityBlocker;
use Modules\VehicleService\Services\Invoice\VehicleServiceInvoiceRestorationHandler;

final class VehicleServiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/vehicle-service.php', 'vehicle-service');
        $this->app->tag(
            [VehicleServiceInvoiceRestorationHandler::class],
            InvoiceSourceRestorationHandlerInterface::TAG,
        );
        $this->app->tag(
            [VehicleServiceAvailabilityBlocker::class],
            VehicleAvailabilityBlockerInterface::TAG,
        );
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('vehicle-service', VehicleServicePermission::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
