<?php

namespace Modules\UOM\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Application\Repositories\UomConversionRepositoryInterface;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Repositories\EloquentUnitOfMeasureRepository;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Repositories\EloquentUomConversionRepository;

class UOMServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/uom.php', 'uom');

        foreach ([
            UnitOfMeasureRepositoryInterface::class => EloquentUnitOfMeasureRepository::class,
            UomConversionRepositoryInterface::class => EloquentUomConversionRepository::class,
        ] as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
