<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Product\Application\Contracts\CreateProductBrandServiceInterface;
use Modules\Product\Application\Contracts\CreateProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\CreateProductIdentifierServiceInterface;
use Modules\Product\Application\Contracts\CreateProductServiceInterface;
use Modules\Product\Application\Contracts\CreateProductVariantServiceInterface;
use Modules\Product\Application\Contracts\CreateUnitOfMeasureServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductBrandServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductIdentifierServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductVariantServiceInterface;
use Modules\Product\Application\Contracts\DeleteUnitOfMeasureServiceInterface;
use Modules\Product\Application\Contracts\DeleteUomConversionServiceInterface;
use Modules\Product\Application\Contracts\FindProductBrandServiceInterface;
use Modules\Product\Application\Contracts\FindProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\FindProductIdentifierServiceInterface;
use Modules\Product\Application\Contracts\FindProductServiceInterface;
use Modules\Product\Application\Contracts\FindProductVariantServiceInterface;
use Modules\Product\Application\Contracts\FindUnitOfMeasureServiceInterface;
use Modules\Product\Application\Contracts\FindUomConversionServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductBrandServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductIdentifierServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductVariantServiceInterface;
use Modules\Product\Application\Contracts\UpdateUnitOfMeasureServiceInterface;
use Modules\Product\Application\Contracts\UpdateUomConversionServiceInterface;
use Modules\Product\Application\Contracts\CreateUomConversionServiceInterface;
use Modules\Product\Application\Services\CreateProductBrandService;
use Modules\Product\Application\Services\CreateProductCategoryService;
use Modules\Product\Application\Services\CreateProductIdentifierService;
use Modules\Product\Application\Services\CreateProductService;
use Modules\Product\Application\Services\CreateProductVariantService;
use Modules\Product\Application\Services\CreateUnitOfMeasureService;
use Modules\Product\Application\Services\DeleteProductBrandService;
use Modules\Product\Application\Services\DeleteProductCategoryService;
use Modules\Product\Application\Services\DeleteProductIdentifierService;
use Modules\Product\Application\Services\DeleteProductService;
use Modules\Product\Application\Services\DeleteProductVariantService;
use Modules\Product\Application\Services\DeleteUnitOfMeasureService;
use Modules\Product\Application\Services\DeleteUomConversionService;
use Modules\Product\Application\Services\FindProductBrandService;
use Modules\Product\Application\Services\FindProductCategoryService;
use Modules\Product\Application\Services\FindProductIdentifierService;
use Modules\Product\Application\Services\FindProductService;
use Modules\Product\Application\Services\FindProductVariantService;
use Modules\Product\Application\Services\FindUnitOfMeasureService;
use Modules\Product\Application\Services\FindUomConversionService;
use Modules\Product\Application\Services\UpdateProductBrandService;
use Modules\Product\Application\Services\UpdateProductCategoryService;
use Modules\Product\Application\Services\UpdateProductIdentifierService;
use Modules\Product\Application\Services\UpdateProductService;
use Modules\Product\Application\Services\UpdateProductVariantService;
use Modules\Product\Application\Services\UpdateUnitOfMeasureService;
use Modules\Product\Application\Services\UpdateUomConversionService;
use Modules\Product\Application\Services\CreateUomConversionService;
use Modules\Product\Domain\RepositoryInterfaces\ProductBrandRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductCategoryRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductIdentifierRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductBrandModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductCategoryModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductIdentifierModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductVariantModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\UomConversionModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductBrandRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductCategoryRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductIdentifierRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductVariantRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentUnitOfMeasureRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentUomConversionRepository;

class ProductServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(ProductBrandRepositoryInterface::class, function ($app) {
            return new EloquentProductBrandRepository($app->make(ProductBrandModel::class));
        });

        $this->app->bind(ProductCategoryRepositoryInterface::class, function ($app) {
            return new EloquentProductCategoryRepository($app->make(ProductCategoryModel::class));
        });

        $this->app->bind(ProductIdentifierRepositoryInterface::class, function ($app) {
            return new EloquentProductIdentifierRepository($app->make(ProductIdentifierModel::class));
        });

        $this->app->bind(ProductRepositoryInterface::class, function ($app) {
            return new EloquentProductRepository($app->make(ProductModel::class));
        });

        $this->app->bind(ProductVariantRepositoryInterface::class, function ($app) {
            return new EloquentProductVariantRepository($app->make(ProductVariantModel::class));
        });

        $this->app->bind(UnitOfMeasureRepositoryInterface::class, function ($app) {
            return new EloquentUnitOfMeasureRepository($app->make(UnitOfMeasureModel::class));
        });

        $this->app->bind(UomConversionRepositoryInterface::class, function ($app) {
            return new EloquentUomConversionRepository($app->make(UomConversionModel::class));
        });

        $this->app->bind(CreateProductBrandServiceInterface::class, function ($app) {
            return new CreateProductBrandService($app->make(ProductBrandRepositoryInterface::class));
        });

        $this->app->bind(FindProductBrandServiceInterface::class, function ($app) {
            return new FindProductBrandService($app->make(ProductBrandRepositoryInterface::class));
        });

        $this->app->bind(UpdateProductBrandServiceInterface::class, function ($app) {
            return new UpdateProductBrandService($app->make(ProductBrandRepositoryInterface::class));
        });

        $this->app->bind(DeleteProductBrandServiceInterface::class, function ($app) {
            return new DeleteProductBrandService($app->make(ProductBrandRepositoryInterface::class));
        });

        $this->app->bind(CreateProductCategoryServiceInterface::class, function ($app) {
            return new CreateProductCategoryService($app->make(ProductCategoryRepositoryInterface::class));
        });

        $this->app->bind(FindProductCategoryServiceInterface::class, function ($app) {
            return new FindProductCategoryService($app->make(ProductCategoryRepositoryInterface::class));
        });

        $this->app->bind(UpdateProductCategoryServiceInterface::class, function ($app) {
            return new UpdateProductCategoryService($app->make(ProductCategoryRepositoryInterface::class));
        });

        $this->app->bind(DeleteProductCategoryServiceInterface::class, function ($app) {
            return new DeleteProductCategoryService($app->make(ProductCategoryRepositoryInterface::class));
        });

        $this->app->bind(CreateProductIdentifierServiceInterface::class, function ($app) {
            return new CreateProductIdentifierService($app->make(ProductIdentifierRepositoryInterface::class));
        });

        $this->app->bind(FindProductIdentifierServiceInterface::class, function ($app) {
            return new FindProductIdentifierService($app->make(ProductIdentifierRepositoryInterface::class));
        });

        $this->app->bind(UpdateProductIdentifierServiceInterface::class, function ($app) {
            return new UpdateProductIdentifierService($app->make(ProductIdentifierRepositoryInterface::class));
        });

        $this->app->bind(DeleteProductIdentifierServiceInterface::class, function ($app) {
            return new DeleteProductIdentifierService($app->make(ProductIdentifierRepositoryInterface::class));
        });

        $this->app->bind(CreateProductVariantServiceInterface::class, function ($app) {
            return new CreateProductVariantService($app->make(ProductVariantRepositoryInterface::class));
        });

        $this->app->bind(FindProductVariantServiceInterface::class, function ($app) {
            return new FindProductVariantService($app->make(ProductVariantRepositoryInterface::class));
        });

        $this->app->bind(UpdateProductVariantServiceInterface::class, function ($app) {
            return new UpdateProductVariantService($app->make(ProductVariantRepositoryInterface::class));
        });

        $this->app->bind(DeleteProductVariantServiceInterface::class, function ($app) {
            return new DeleteProductVariantService($app->make(ProductVariantRepositoryInterface::class));
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

        $this->app->bind(CreateProductServiceInterface::class, function ($app) {
            return new CreateProductService($app->make(ProductRepositoryInterface::class));
        });

        $this->app->bind(FindProductServiceInterface::class, function ($app) {
            return new FindProductService($app->make(ProductRepositoryInterface::class));
        });

        $this->app->bind(UpdateProductServiceInterface::class, function ($app) {
            return new UpdateProductService($app->make(ProductRepositoryInterface::class));
        });

        $this->app->bind(DeleteProductServiceInterface::class, function ($app) {
            return new DeleteProductService($app->make(ProductRepositoryInterface::class));
        });
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}
