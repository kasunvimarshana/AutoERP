<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\VehicleService\Application\Services\VehicleServiceInventoryService;
use Modules\VehicleService\Application\Services\VehicleServiceInvoiceService;
use Modules\VehicleService\Application\Services\VehicleServicePaymentService;
use Modules\VehicleService\Application\Services\VehicleServiceService;

final class VehicleServiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/vehicle_service.php', 'vehicle_service');
        $this->app->singleton(VehicleServiceInventoryService::class);
        $this->app->singleton(VehicleServiceInvoiceService::class);
        $this->app->singleton(VehicleServicePaymentService::class);
        $this->app->singleton(VehicleServiceService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
