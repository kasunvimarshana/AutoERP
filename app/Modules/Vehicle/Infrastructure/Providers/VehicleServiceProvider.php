<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Vehicle\Application\Contracts\Services\VehicleOwnershipServiceInterface;
use Modules\Vehicle\Application\Contracts\UseCases\VehicleDocuments\CreateVehicleDocumentServiceInterface;
use Modules\Vehicle\Application\Contracts\UseCases\VehicleDocuments\DeleteVehicleDocumentServiceInterface;
use Modules\Vehicle\Application\Contracts\UseCases\VehicleDocuments\GetVehicleDocumentServiceInterface;
use Modules\Vehicle\Application\Contracts\UseCases\VehicleDocuments\ListVehicleDocumentsServiceInterface;
use Modules\Vehicle\Application\Contracts\UseCases\VehicleDocuments\UpdateVehicleDocumentServiceInterface;
use Modules\Vehicle\Application\Contracts\UseCases\Vehicles\CreateVehicleServiceInterface;
use Modules\Vehicle\Application\Contracts\UseCases\Vehicles\DeleteVehicleServiceInterface;
use Modules\Vehicle\Application\Contracts\UseCases\Vehicles\GetVehicleServiceInterface;
use Modules\Vehicle\Application\Contracts\UseCases\Vehicles\ListVehiclesServiceInterface;
use Modules\Vehicle\Application\Contracts\UseCases\Vehicles\UpdateVehicleServiceInterface;
use Modules\Vehicle\Application\Repositories\VehicleDocumentRepositoryInterface;
use Modules\Vehicle\Application\Repositories\VehicleOwnershipRepositoryInterface;
use Modules\Vehicle\Application\Repositories\VehicleRepositoryInterface;
use Modules\Vehicle\Application\Services\VehicleOwnershipService;
use Modules\Vehicle\Application\UseCases\VehicleDocuments\CreateVehicleDocumentService;
use Modules\Vehicle\Application\UseCases\VehicleDocuments\DeleteVehicleDocumentService;
use Modules\Vehicle\Application\UseCases\VehicleDocuments\GetVehicleDocumentService;
use Modules\Vehicle\Application\UseCases\VehicleDocuments\ListVehicleDocumentsService;
use Modules\Vehicle\Application\UseCases\VehicleDocuments\UpdateVehicleDocumentService;
use Modules\Vehicle\Application\UseCases\Vehicles\CreateVehicleService;
use Modules\Vehicle\Application\UseCases\Vehicles\DeleteVehicleService;
use Modules\Vehicle\Application\UseCases\Vehicles\GetVehicleService;
use Modules\Vehicle\Application\UseCases\Vehicles\ListVehiclesService;
use Modules\Vehicle\Application\UseCases\Vehicles\UpdateVehicleService;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleDocumentModel;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleModel;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleOwnershipModel;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleDocumentRepository;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleOwnershipRepository;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRepository;

final class VehicleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/vehicle.php', 'vehicle');

        foreach (
            [
                ListVehiclesServiceInterface::class => ListVehiclesService::class,
                GetVehicleServiceInterface::class => GetVehicleService::class,
                CreateVehicleServiceInterface::class => CreateVehicleService::class,
                UpdateVehicleServiceInterface::class => UpdateVehicleService::class,
                DeleteVehicleServiceInterface::class => DeleteVehicleService::class,
                ListVehicleDocumentsServiceInterface::class => ListVehicleDocumentsService::class,
                GetVehicleDocumentServiceInterface::class => GetVehicleDocumentService::class,
                CreateVehicleDocumentServiceInterface::class => CreateVehicleDocumentService::class,
                UpdateVehicleDocumentServiceInterface::class => UpdateVehicleDocumentService::class,
                DeleteVehicleDocumentServiceInterface::class => DeleteVehicleDocumentService::class,
                VehicleOwnershipServiceInterface::class => VehicleOwnershipService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(VehicleRepositoryInterface::class, function (): VehicleRepositoryInterface {
            return new EloquentVehicleRepository(new VehicleModel());
        });

        $this->app->singleton(
            VehicleDocumentRepositoryInterface::class,
            function (): VehicleDocumentRepositoryInterface {
                return new EloquentVehicleDocumentRepository(new VehicleDocumentModel());
            },
        );

        $this->app->singleton(
            VehicleOwnershipRepositoryInterface::class,
            function (): VehicleOwnershipRepositoryInterface {
                return new EloquentVehicleOwnershipRepository(new VehicleOwnershipModel());
            },
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
