<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\VehicleRental\Console\Commands\VehicleFinanceRefreshDueStatusesCommand;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;

final class VehicleRentalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Services use constructor injection and require no manual bindings.
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('vehicle-rental', VehicleRentalAuthorizationService::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                VehicleFinanceRefreshDueStatusesCommand::class,
            ]);
        }
    }
}
