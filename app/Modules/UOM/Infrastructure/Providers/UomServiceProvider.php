<?php

declare(strict_types=1);

namespace Modules\UOM\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use Modules\UOM\Application\Contracts\Services\UomUsageSummaryServiceInterface;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Application\Repositories\UomConversionRepositoryInterface;
use Modules\UOM\Application\Services\UomConversionService;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UomConversionModel;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Repositories\EloquentUnitOfMeasureRepository;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Repositories\EloquentUomConversionRepository;
use Modules\UOM\Infrastructure\Services\DatabaseUomUsageSummaryService;

final class UomServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/uom.php', 'uom');

        // Repository bindings Ã¢â‚¬â€ use container resolution for model injection
        $this->app->singleton(UnitOfMeasureRepositoryInterface::class, function (): UnitOfMeasureRepositoryInterface {
            return new EloquentUnitOfMeasureRepository($this->app->make(UnitOfMeasureModel::class));
        });

        $this->app->singleton(UomConversionRepositoryInterface::class, function (): UomConversionRepositoryInterface {
            return new EloquentUomConversionRepository($this->app->make(UomConversionModel::class));
        });

        // Reusable conversion service
        $this->app->singleton(UomConversionServiceInterface::class, UomConversionService::class);
        $this->app->singleton(UomUsageSummaryServiceInterface::class, DatabaseUomUsageSummaryService::class);

        // Use case bindings
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');
    }
}
