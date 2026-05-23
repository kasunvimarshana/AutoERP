<?php

namespace Modules\VehicleService\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\VehicleService\Application\Repositories\VehicleServiceDiagnosticLineRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceDiagnosticRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceInspectionLineRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceInspectionRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobCardLineRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobCardRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceLaborAssignmentRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceLaborItemRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceNonInventoryItemRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceTypeRepositoryInterface;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceDiagnosticLineRepository;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceDiagnosticRepository;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceInspectionLineRepository;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceInspectionRepository;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceJobCardLineRepository;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceJobCardRepository;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceLaborAssignmentRepository;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceLaborItemRepository;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceNonInventoryItemRepository;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceTypeRepository;

class VehicleServiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            VehicleServiceDiagnosticLineRepositoryInterface::class => EloquentVehicleServiceDiagnosticLineRepository::class,
            VehicleServiceDiagnosticRepositoryInterface::class => EloquentVehicleServiceDiagnosticRepository::class,
            VehicleServiceInspectionLineRepositoryInterface::class => EloquentVehicleServiceInspectionLineRepository::class,
            VehicleServiceInspectionRepositoryInterface::class => EloquentVehicleServiceInspectionRepository::class,
            VehicleServiceJobCardLineRepositoryInterface::class => EloquentVehicleServiceJobCardLineRepository::class,
            VehicleServiceJobCardRepositoryInterface::class => EloquentVehicleServiceJobCardRepository::class,
            VehicleServiceLaborAssignmentRepositoryInterface::class => EloquentVehicleServiceLaborAssignmentRepository::class,
            VehicleServiceLaborItemRepositoryInterface::class => EloquentVehicleServiceLaborItemRepository::class,
            VehicleServiceNonInventoryItemRepositoryInterface::class => EloquentVehicleServiceNonInventoryItemRepository::class,
            VehicleServiceTypeRepositoryInterface::class => EloquentVehicleServiceTypeRepository::class,
        ] as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
