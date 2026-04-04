<?php
namespace Modules\UoM\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\UoM\Application\Contracts\ConvertUomServiceInterface;
use Modules\UoM\Application\Contracts\CreateProductUomSettingServiceInterface;
use Modules\UoM\Application\Contracts\CreateUnitOfMeasureServiceInterface;
use Modules\UoM\Application\Contracts\CreateUomCategoryServiceInterface;
use Modules\UoM\Application\Contracts\CreateUomConversionServiceInterface;
use Modules\UoM\Application\Contracts\DeleteUnitOfMeasureServiceInterface;
use Modules\UoM\Application\Contracts\DeleteUomCategoryServiceInterface;
use Modules\UoM\Application\Contracts\DeleteUomConversionServiceInterface;
use Modules\UoM\Application\Contracts\UpdateProductUomSettingServiceInterface;
use Modules\UoM\Application\Contracts\UpdateUnitOfMeasureServiceInterface;
use Modules\UoM\Application\Contracts\UpdateUomCategoryServiceInterface;
use Modules\UoM\Application\Contracts\UpdateUomConversionServiceInterface;
use Modules\UoM\Application\Services\ConvertUomService;
use Modules\UoM\Application\Services\CreateProductUomSettingService;
use Modules\UoM\Application\Services\CreateUnitOfMeasureService;
use Modules\UoM\Application\Services\CreateUomCategoryService;
use Modules\UoM\Application\Services\CreateUomConversionService;
use Modules\UoM\Application\Services\DeleteUnitOfMeasureService;
use Modules\UoM\Application\Services\DeleteUomCategoryService;
use Modules\UoM\Application\Services\DeleteUomConversionService;
use Modules\UoM\Application\Services\UpdateProductUomSettingService;
use Modules\UoM\Application\Services\UpdateUnitOfMeasureService;
use Modules\UoM\Application\Services\UpdateUomCategoryService;
use Modules\UoM\Application\Services\UpdateUomConversionService;
use Modules\UoM\Domain\RepositoryInterfaces\ProductUomSettingRepositoryInterface;
use Modules\UoM\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;
use Modules\UoM\Domain\RepositoryInterfaces\UomCategoryRepositoryInterface;
use Modules\UoM\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductUomSettingRepository;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories\EloquentUnitOfMeasureRepository;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories\EloquentUomCategoryRepository;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories\EloquentUomConversionRepository;

class UomServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repositories
        $this->app->bind(UomCategoryRepositoryInterface::class, EloquentUomCategoryRepository::class);
        $this->app->bind(UnitOfMeasureRepositoryInterface::class, EloquentUnitOfMeasureRepository::class);
        $this->app->bind(UomConversionRepositoryInterface::class, EloquentUomConversionRepository::class);
        $this->app->bind(ProductUomSettingRepositoryInterface::class, EloquentProductUomSettingRepository::class);

        // Services
        $this->app->bind(CreateUomCategoryServiceInterface::class, CreateUomCategoryService::class);
        $this->app->bind(UpdateUomCategoryServiceInterface::class, UpdateUomCategoryService::class);
        $this->app->bind(DeleteUomCategoryServiceInterface::class, DeleteUomCategoryService::class);
        $this->app->bind(CreateUnitOfMeasureServiceInterface::class, CreateUnitOfMeasureService::class);
        $this->app->bind(UpdateUnitOfMeasureServiceInterface::class, UpdateUnitOfMeasureService::class);
        $this->app->bind(DeleteUnitOfMeasureServiceInterface::class, DeleteUnitOfMeasureService::class);
        $this->app->bind(CreateUomConversionServiceInterface::class, CreateUomConversionService::class);
        $this->app->bind(UpdateUomConversionServiceInterface::class, UpdateUomConversionService::class);
        $this->app->bind(DeleteUomConversionServiceInterface::class, DeleteUomConversionService::class);
        $this->app->bind(CreateProductUomSettingServiceInterface::class, CreateProductUomSettingService::class);
        $this->app->bind(UpdateProductUomSettingServiceInterface::class, UpdateProductUomSettingService::class);
        $this->app->bind(ConvertUomServiceInterface::class, ConvertUomService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
