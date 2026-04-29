<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Vehicle\Application\Contracts\CloseVehicleRentalServiceInterface;
use Modules\Vehicle\Application\Contracts\CreateVehicleJobCardServiceInterface;
use Modules\Vehicle\Application\Contracts\CreateVehicleRentalServiceInterface;
use Modules\Vehicle\Application\Contracts\CreateVehicleServiceInterface;
use Modules\Vehicle\Application\Contracts\FindVehicleServiceInterface;
use Modules\Vehicle\Application\Contracts\UpdateVehicleStatusServiceInterface;
use Modules\Vehicle\Application\Contracts\VehicleDashboardServiceInterface;
use Modules\Vehicle\Application\Services\CloseVehicleRentalService;
use Modules\Vehicle\Application\Services\CreateVehicleJobCardService;
use Modules\Vehicle\Application\Services\CreateVehicleRentalService;
use Modules\Vehicle\Application\Services\CreateVehicleService;
use Modules\Vehicle\Application\Services\DeleteVehicleService;
use Modules\Vehicle\Application\Services\FindVehicleService;
use Modules\Vehicle\Application\Services\UpdateVehicleStatusService;
use Modules\Vehicle\Application\Services\VehicleDashboardService;
use Modules\Vehicle\Domain\RepositoryInterfaces\VehicleDocumentRepositoryInterface;
use Modules\Vehicle\Domain\RepositoryInterfaces\VehicleJobCardRepositoryInterface;
use Modules\Vehicle\Domain\RepositoryInterfaces\VehicleRentalRepositoryInterface;
use Modules\Vehicle\Domain\RepositoryInterfaces\VehicleRepositoryInterface;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleDocumentRepository;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleJobCardRepository;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRentalRepository;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRepository;

class VehicleServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(VehicleRepositoryInterface::class, EloquentVehicleRepository::class);
        $this->app->bind(VehicleJobCardRepositoryInterface::class, EloquentVehicleJobCardRepository::class);
        $this->app->bind(VehicleRentalRepositoryInterface::class, EloquentVehicleRentalRepository::class);
        $this->app->bind(VehicleDocumentRepositoryInterface::class, EloquentVehicleDocumentRepository::class);

        $this->app->bind(CreateVehicleServiceInterface::class, CreateVehicleService::class);
        $this->app->bind(FindVehicleServiceInterface::class, FindVehicleService::class);
        $this->app->bind(DeleteVehicleService::class, DeleteVehicleService::class);
        $this->app->bind(UpdateVehicleStatusServiceInterface::class, UpdateVehicleStatusService::class);
        $this->app->bind(CreateVehicleJobCardServiceInterface::class, CreateVehicleJobCardService::class);
        $this->app->bind(CreateVehicleRentalServiceInterface::class, CreateVehicleRentalService::class);
        $this->app->bind(CloseVehicleRentalServiceInterface::class, CloseVehicleRentalService::class);
        $this->app->bind(VehicleDashboardServiceInterface::class, VehicleDashboardService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}
