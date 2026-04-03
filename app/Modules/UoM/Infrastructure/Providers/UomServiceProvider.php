<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\UoM\Application\Contracts\CreateProductUomSettingServiceInterface;
use Modules\UoM\Application\Contracts\CreateUnitOfMeasureServiceInterface;
use Modules\UoM\Application\Contracts\CreateUomCategoryServiceInterface;
use Modules\UoM\Application\Contracts\CreateUomConversionServiceInterface;
use Modules\UoM\Application\Contracts\DeleteProductUomSettingServiceInterface;
use Modules\UoM\Application\Contracts\DeleteUnitOfMeasureServiceInterface;
use Modules\UoM\Application\Contracts\DeleteUomCategoryServiceInterface;
use Modules\UoM\Application\Contracts\DeleteUomConversionServiceInterface;
use Modules\UoM\Application\Contracts\FindProductUomSettingServiceInterface;
use Modules\UoM\Application\Contracts\FindUnitOfMeasureServiceInterface;
use Modules\UoM\Application\Contracts\FindUomCategoryServiceInterface;
use Modules\UoM\Application\Contracts\FindUomConversionServiceInterface;
use Modules\UoM\Application\Contracts\UpdateProductUomSettingServiceInterface;
use Modules\UoM\Application\Contracts\UpdateUnitOfMeasureServiceInterface;
use Modules\UoM\Application\Contracts\UpdateUomCategoryServiceInterface;
use Modules\UoM\Application\Contracts\UpdateUomConversionServiceInterface;
use Modules\UoM\Application\Services\CreateProductUomSettingService;
use Modules\UoM\Application\Services\CreateUnitOfMeasureService;
use Modules\UoM\Application\Services\CreateUomCategoryService;
use Modules\UoM\Application\Services\CreateUomConversionService;
use Modules\UoM\Application\Services\DeleteProductUomSettingService;
use Modules\UoM\Application\Services\DeleteUnitOfMeasureService;
use Modules\UoM\Application\Services\DeleteUomCategoryService;
use Modules\UoM\Application\Services\DeleteUomConversionService;
use Modules\UoM\Application\Services\FindProductUomSettingService;
use Modules\UoM\Application\Services\FindUnitOfMeasureService;
use Modules\UoM\Application\Services\FindUomCategoryService;
use Modules\UoM\Application\Services\FindUomConversionService;
use Modules\UoM\Application\Services\UpdateProductUomSettingService;
use Modules\UoM\Application\Services\UpdateUnitOfMeasureService;
use Modules\UoM\Application\Services\UpdateUomCategoryService;
use Modules\UoM\Application\Services\UpdateUomConversionService;
use Modules\UoM\Domain\RepositoryInterfaces\ProductUomSettingRepositoryInterface;
use Modules\UoM\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;
use Modules\UoM\Domain\RepositoryInterfaces\UomCategoryRepositoryInterface;
use Modules\UoM\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\ProductUomSettingModel;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\UomCategoryModel;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\UomConversionModel;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductUomSettingRepository;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories\EloquentUnitOfMeasureRepository;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories\EloquentUomCategoryRepository;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories\EloquentUomConversionRepository;

class UomServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UomCategoryRepositoryInterface::class, function ($app) {
            return new EloquentUomCategoryRepository($app->make(UomCategoryModel::class));
        });

        $this->app->bind(UnitOfMeasureRepositoryInterface::class, function ($app) {
            return new EloquentUnitOfMeasureRepository($app->make(UnitOfMeasureModel::class));
        });

        $this->app->bind(UomConversionRepositoryInterface::class, function ($app) {
            return new EloquentUomConversionRepository($app->make(UomConversionModel::class));
        });

        $this->app->bind(ProductUomSettingRepositoryInterface::class, function ($app) {
            return new EloquentProductUomSettingRepository($app->make(ProductUomSettingModel::class));
        });

        $this->app->bind(CreateUomCategoryServiceInterface::class, function ($app) {
            return new CreateUomCategoryService($app->make(UomCategoryRepositoryInterface::class));
        });

        $this->app->bind(FindUomCategoryServiceInterface::class, function ($app) {
            return new FindUomCategoryService($app->make(UomCategoryRepositoryInterface::class));
        });

        $this->app->bind(UpdateUomCategoryServiceInterface::class, function ($app) {
            return new UpdateUomCategoryService($app->make(UomCategoryRepositoryInterface::class));
        });

        $this->app->bind(DeleteUomCategoryServiceInterface::class, function ($app) {
            return new DeleteUomCategoryService($app->make(UomCategoryRepositoryInterface::class));
        });

        $this->app->bind(CreateUnitOfMeasureServiceInterface::class, function ($app) {
            return new CreateUnitOfMeasureService($app->make(UnitOfMeasureRepositoryInterface::class));
        });

        $this->app->bind(FindUnitOfMeasureServiceInterface::class, function ($app) {
            return new FindUnitOfMeasureService($app->make(UnitOfMeasureRepositoryInterface::class));
        });

        $this->app->bind(UpdateUnitOfMeasureServiceInterface::class, function ($app) {
            return new UpdateUnitOfMeasureService($app->make(UnitOfMeasureRepositoryInterface::class));
        });

        $this->app->bind(DeleteUnitOfMeasureServiceInterface::class, function ($app) {
            return new DeleteUnitOfMeasureService($app->make(UnitOfMeasureRepositoryInterface::class));
        });

        $this->app->bind(CreateUomConversionServiceInterface::class, function ($app) {
            return new CreateUomConversionService($app->make(UomConversionRepositoryInterface::class));
        });

        $this->app->bind(FindUomConversionServiceInterface::class, function ($app) {
            return new FindUomConversionService($app->make(UomConversionRepositoryInterface::class));
        });

        $this->app->bind(UpdateUomConversionServiceInterface::class, function ($app) {
            return new UpdateUomConversionService($app->make(UomConversionRepositoryInterface::class));
        });

        $this->app->bind(DeleteUomConversionServiceInterface::class, function ($app) {
            return new DeleteUomConversionService($app->make(UomConversionRepositoryInterface::class));
        });

        $this->app->bind(CreateProductUomSettingServiceInterface::class, function ($app) {
            return new CreateProductUomSettingService($app->make(ProductUomSettingRepositoryInterface::class));
        });

        $this->app->bind(FindProductUomSettingServiceInterface::class, function ($app) {
            return new FindProductUomSettingService($app->make(ProductUomSettingRepositoryInterface::class));
        });

        $this->app->bind(UpdateProductUomSettingServiceInterface::class, function ($app) {
            return new UpdateProductUomSettingService($app->make(ProductUomSettingRepositoryInterface::class));
        });

        $this->app->bind(DeleteProductUomSettingServiceInterface::class, function ($app) {
            return new DeleteProductUomSettingService($app->make(ProductUomSettingRepositoryInterface::class));
        });
    }

    public function boot(): void
    {
        Route::middleware('api')
             ->prefix('api')
             ->group(function () {
                 $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
             });

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
