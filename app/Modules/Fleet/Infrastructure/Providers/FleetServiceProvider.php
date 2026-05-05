<?php

declare(strict_types=1);

namespace Modules\Fleet\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Fleet\Application\Contracts\VehicleDocumentServiceInterface;
use Modules\Fleet\Application\Contracts\VehicleServiceInterface;
use Modules\Fleet\Application\Contracts\VehicleTypeServiceInterface;
use Modules\Fleet\Application\Services\VehicleDocumentService;
use Modules\Fleet\Application\Services\VehicleService;
use Modules\Fleet\Application\Services\VehicleTypeService;
use Modules\Fleet\Domain\RepositoryInterfaces\DepreciationScheduleRepositoryInterface;
use Modules\Fleet\Domain\RepositoryInterfaces\VehicleDocumentRepositoryInterface;
use Modules\Fleet\Domain\RepositoryInterfaces\VehicleRepositoryInterface;
use Modules\Fleet\Domain\RepositoryInterfaces\VehicleTypeRepositoryInterface;
use Modules\Fleet\Domain\RepositoryInterfaces\VehicleStateLogRepositoryInterface;
use Modules\Fleet\Infrastructure\Persistence\Eloquent\Repositories\EloquentDepreciationScheduleRepository;
use Modules\Fleet\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleDocumentRepository;
use Modules\Fleet\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRepository;
use Modules\Fleet\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleTypeRepository;
use Modules\Fleet\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleStateLogRepository;

class FleetServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(VehicleRepositoryInterface::class, EloquentVehicleRepository::class);
        $this->app->bind(VehicleTypeRepositoryInterface::class, EloquentVehicleTypeRepository::class);
        $this->app->bind(VehicleDocumentRepositoryInterface::class, EloquentVehicleDocumentRepository::class);
        $this->app->bind(DepreciationScheduleRepositoryInterface::class, EloquentDepreciationScheduleRepository::class);
        $this->app->bind(VehicleStateLogRepositoryInterface::class, EloquentVehicleStateLogRepository::class);

        $this->app->bind(VehicleServiceInterface::class, VehicleService::class);
        $this->app->bind(VehicleTypeServiceInterface::class, VehicleTypeService::class);
        $this->app->bind(VehicleDocumentServiceInterface::class, VehicleDocumentService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__ . '/../../routes/api.php',
            __DIR__ . '/../../database/migrations',
        );
    }
}
