<?php

namespace Modules\Vehicle\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Vehicle\Application\Repositories\VehicleDocumentRepositoryInterface;
use Modules\Vehicle\Application\Repositories\VehicleRepositoryInterface;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleDocumentRepository;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRepository;

class VehicleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            VehicleDocumentRepositoryInterface::class => EloquentVehicleDocumentRepository::class,
            VehicleRepositoryInterface::class => EloquentVehicleRepository::class,
        ] as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
