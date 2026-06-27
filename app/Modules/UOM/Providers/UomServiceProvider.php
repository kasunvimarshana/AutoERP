<?php

declare(strict_types=1);

namespace Modules\UOM\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\UOM\Constants\UomPermission;
use Modules\UOM\Contracts\Services\UomConversionServiceInterface;
use Modules\UOM\Contracts\Services\UomUsageSummaryServiceInterface;
use Modules\UOM\Models\UnitOfMeasureModel;
use Modules\UOM\Models\UomConversionModel;
use Modules\UOM\Repositories\EloquentUnitOfMeasureRepository;
use Modules\UOM\Repositories\EloquentUomConversionRepository;
use Modules\UOM\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Repositories\UomConversionRepositoryInterface;
use Modules\UOM\Services\DatabaseUomUsageSummaryService;
use Modules\UOM\Services\UomConversionService;

final class UomServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/uom.php', 'uom');

        $this->app->singleton(UnitOfMeasureRepositoryInterface::class, function (): UnitOfMeasureRepositoryInterface {
            return new EloquentUnitOfMeasureRepository($this->app->make(UnitOfMeasureModel::class));
        });

        $this->app->singleton(UomConversionRepositoryInterface::class, function (): UomConversionRepositoryInterface {
            return new EloquentUomConversionRepository($this->app->make(UomConversionModel::class));
        });

        $this->app->singleton(UomConversionServiceInterface::class, UomConversionService::class);
        $this->app->singleton(UomUsageSummaryServiceInterface::class, DatabaseUomUsageSummaryService::class);
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('uom', UomPermission::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
