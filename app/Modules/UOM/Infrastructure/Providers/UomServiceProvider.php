<?php

declare(strict_types=1);

namespace Modules\UOM\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\UOM\Application\Contracts\UseCases\UnitOfMeasures\CreateUnitOfMeasureServiceInterface;
use Modules\UOM\Application\Contracts\UseCases\UnitOfMeasures\DeleteUnitOfMeasureServiceInterface;
use Modules\UOM\Application\Contracts\UseCases\UnitOfMeasures\GetUnitOfMeasureServiceInterface;
use Modules\UOM\Application\Contracts\UseCases\UnitOfMeasures\ListUnitOfMeasuresServiceInterface;
use Modules\UOM\Application\Contracts\UseCases\UnitOfMeasures\UpdateUnitOfMeasureServiceInterface;
use Modules\UOM\Application\Contracts\UseCases\UomConversions\CreateUomConversionServiceInterface;
use Modules\UOM\Application\Contracts\UseCases\UomConversions\DeleteUomConversionServiceInterface;
use Modules\UOM\Application\Contracts\UseCases\UomConversions\GetUomConversionServiceInterface;
use Modules\UOM\Application\Contracts\UseCases\UomConversions\ListUomConversionsServiceInterface;
use Modules\UOM\Application\Contracts\UseCases\UomConversions\UpdateUomConversionServiceInterface;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Application\Repositories\UomConversionRepositoryInterface;
use Modules\UOM\Application\UseCases\UnitOfMeasures\CreateUnitOfMeasureService;
use Modules\UOM\Application\UseCases\UnitOfMeasures\DeleteUnitOfMeasureService;
use Modules\UOM\Application\UseCases\UnitOfMeasures\GetUnitOfMeasureService;
use Modules\UOM\Application\UseCases\UnitOfMeasures\ListUnitOfMeasuresService;
use Modules\UOM\Application\UseCases\UnitOfMeasures\UpdateUnitOfMeasureService;
use Modules\UOM\Application\UseCases\UomConversions\CreateUomConversionService;
use Modules\UOM\Application\UseCases\UomConversions\DeleteUomConversionService;
use Modules\UOM\Application\UseCases\UomConversions\GetUomConversionService;
use Modules\UOM\Application\UseCases\UomConversions\ListUomConversionsService;
use Modules\UOM\Application\UseCases\UomConversions\UpdateUomConversionService;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UomConversionModel;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Repositories\EloquentUnitOfMeasureRepository;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Repositories\EloquentUomConversionRepository;

final class UomServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/uom.php', 'uom');

        foreach (
            [
                ListUnitOfMeasuresServiceInterface::class => ListUnitOfMeasuresService::class,
                GetUnitOfMeasureServiceInterface::class => GetUnitOfMeasureService::class,
                CreateUnitOfMeasureServiceInterface::class => CreateUnitOfMeasureService::class,
                UpdateUnitOfMeasureServiceInterface::class => UpdateUnitOfMeasureService::class,
                DeleteUnitOfMeasureServiceInterface::class => DeleteUnitOfMeasureService::class,
                ListUomConversionsServiceInterface::class => ListUomConversionsService::class,
                GetUomConversionServiceInterface::class => GetUomConversionService::class,
                CreateUomConversionServiceInterface::class => CreateUomConversionService::class,
                UpdateUomConversionServiceInterface::class => UpdateUomConversionService::class,
                DeleteUomConversionServiceInterface::class => DeleteUomConversionService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(UnitOfMeasureRepositoryInterface::class, function (): UnitOfMeasureRepositoryInterface {
            return new EloquentUnitOfMeasureRepository(new UnitOfMeasureModel());
        });

        $this->app->singleton(UomConversionRepositoryInterface::class, function (): UomConversionRepositoryInterface {
            return new EloquentUomConversionRepository(new UomConversionModel());
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Eloquent/Migrations');
    }
}