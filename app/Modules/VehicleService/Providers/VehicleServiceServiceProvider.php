<?php

declare(strict_types=1);

namespace Modules\VehicleService\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Invoice\Contracts\InvoiceSourceCancellationHandlerInterface;
use Modules\VehicleService\Constants\VehicleServicePermission;
use Modules\VehicleService\Services\Invoice\VehicleServiceInvoiceCancellationHandler;

final class VehicleServiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/vehicle-service.php', 'vehicle-service');
        $this->app->tag(
            [VehicleServiceInvoiceCancellationHandler::class],
            InvoiceSourceCancellationHandlerInterface::TAG,
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
