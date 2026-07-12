<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Invoice\Contracts\InvoiceSourceRestorationHandlerInterface;
use Modules\VehicleRental\Console\Commands\VehicleFinanceRefreshDueStatusesCommand;
use Modules\VehicleRental\Services\Invoice\RentalInvoiceRestorationHandler;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class VehicleRentalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag(
            [RentalInvoiceRestorationHandler::class],
            InvoiceSourceRestorationHandlerInterface::TAG,
        );
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
