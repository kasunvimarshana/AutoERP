<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Product\Application\Contracts\CreateProductBrandServiceInterface;
use Modules\Product\Application\Contracts\CreateProductAttributeGroupServiceInterface;
use Modules\Product\Application\Contracts\CreateProductAttributeServiceInterface;
use Modules\Product\Application\Contracts\CreateProductAttributeValueServiceInterface;
use Modules\Product\Application\Contracts\CreateComboItemServiceInterface;
use Modules\Product\Application\Contracts\CreateProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\CreateProductIdentifierServiceInterface;
use Modules\Product\Application\Contracts\CreateProductServiceInterface;
use Modules\Product\Application\Contracts\CreateProductVariantServiceInterface;
use Modules\Product\Application\Contracts\CreateUnitOfMeasureServiceInterface;
use Modules\Product\Application\Contracts\CreateUomConversionServiceInterface;
use Modules\Product\Application\Contracts\CreateVariantAttributeServiceInterface;
use Modules\Product\Application\Contracts\DeleteComboItemServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductAttributeGroupServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductAttributeServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductAttributeValueServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductBrandServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductIdentifierServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductVariantServiceInterface;
use Modules\Product\Application\Contracts\DeleteUnitOfMeasureServiceInterface;
use Modules\Product\Application\Contracts\DeleteUomConversionServiceInterface;
use Modules\Product\Application\Contracts\DeleteVariantAttributeServiceInterface;
use Modules\Product\Application\Contracts\FindComboItemServiceInterface;
use Modules\Product\Application\Contracts\FindProductAttributeGroupServiceInterface;
use Modules\Product\Application\Contracts\FindProductAttributeServiceInterface;
use Modules\Product\Application\Contracts\FindProductAttributeValueServiceInterface;
use Modules\Product\Application\Contracts\FindProductBrandServiceInterface;
use Modules\Product\Application\Contracts\FindProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\FindProductIdentifierServiceInterface;
use Modules\Product\Application\Contracts\RefreshProductSearchProjectionServiceInterface;
use Modules\Product\Application\Contracts\RebuildProductSearchProjectionServiceInterface;
use Modules\Product\Application\Contracts\SearchProductsServiceInterface;
use Modules\Product\Application\Contracts\FindProductServiceInterface;
use Modules\Product\Application\Contracts\FindProductVariantServiceInterface;
use Modules\Product\Application\Contracts\FindUnitOfMeasureServiceInterface;
use Modules\Product\Application\Contracts\FindUomConversionServiceInterface;
use Modules\Product\Application\Contracts\FindVariantAttributeServiceInterface;
use Modules\Product\Application\Contracts\UpdateComboItemServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductAttributeGroupServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductAttributeServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductAttributeValueServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductBrandServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductIdentifierServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductVariantServiceInterface;
use Modules\Product\Application\Contracts\UpdateUnitOfMeasureServiceInterface;
use Modules\Product\Application\Contracts\UpdateUomConversionServiceInterface;
use Modules\Product\Application\Contracts\UomConversionResolverServiceInterface;
use Modules\Product\Application\Contracts\UpdateVariantAttributeServiceInterface;
use Modules\Product\Application\Services\CreateComboItemService;
use Modules\Product\Application\Services\CreateProductAttributeGroupService;
use Modules\Product\Application\Services\CreateProductAttributeService;
use Modules\Product\Application\Services\CreateProductAttributeValueService;
use Modules\Product\Application\Services\CreateProductBrandService;
use Modules\Product\Application\Services\CreateProductCategoryService;
use Modules\Product\Application\Services\CreateProductIdentifierService;
use Modules\Product\Application\Services\CreateProductService;
use Modules\Product\Application\Services\CreateProductVariantService;
use Modules\Product\Application\Services\CreateUnitOfMeasureService;
use Modules\Product\Application\Services\CreateUomConversionService;
use Modules\Product\Application\Services\CreateVariantAttributeService;
use Modules\Product\Application\Services\DeleteComboItemService;
use Modules\Product\Application\Services\DeleteProductAttributeGroupService;
use Modules\Product\Application\Services\DeleteProductAttributeService;
use Modules\Product\Application\Services\DeleteProductAttributeValueService;
use Modules\Product\Application\Services\DeleteProductBrandService;
use Modules\Product\Application\Services\DeleteProductCategoryService;
use Modules\Product\Application\Services\DeleteProductIdentifierService;
use Modules\Product\Application\Services\DeleteProductService;
use Modules\Product\Application\Services\DeleteProductVariantService;
use Modules\Product\Application\Services\DeleteUnitOfMeasureService;
use Modules\Product\Application\Services\DeleteUomConversionService;
use Modules\Product\Application\Services\DeleteVariantAttributeService;
use Modules\Product\Application\Services\FindComboItemService;
use Modules\Product\Application\Services\FindProductAttributeGroupService;
use Modules\Product\Application\Services\FindProductAttributeService;
use Modules\Product\Application\Services\FindProductAttributeValueService;
use Modules\Product\Application\Services\FindProductBrandService;
use Modules\Product\Application\Services\FindProductCategoryService;
use Modules\Product\Application\Services\FindProductIdentifierService;
use Modules\Product\Application\Services\RefreshProductSearchProjectionService;
use Modules\Product\Application\Services\RebuildProductSearchProjectionService;
use Modules\Product\Application\Services\SearchProductsService;
use Modules\Product\Application\Services\FindProductService;
use Modules\Product\Application\Services\FindProductVariantService;
use Modules\Product\Application\Services\FindUnitOfMeasureService;
use Modules\Product\Application\Services\FindUomConversionService;
use Modules\Product\Application\Services\FindVariantAttributeService;
use Modules\Product\Application\Services\UpdateComboItemService;
use Modules\Product\Application\Services\UpdateProductAttributeGroupService;
use Modules\Product\Application\Services\UpdateProductAttributeService;
use Modules\Product\Application\Services\UpdateProductAttributeValueService;
use Modules\Product\Application\Services\UpdateProductBrandService;
use Modules\Product\Application\Services\UpdateProductCategoryService;
use Modules\Product\Application\Services\UpdateProductIdentifierService;
use Modules\Product\Application\Services\UpdateProductService;
use Modules\Product\Application\Services\UpdateProductVariantService;
use Modules\Product\Application\Services\UpdateUnitOfMeasureService;
use Modules\Product\Application\Services\UpdateUomConversionService;
use Modules\Product\Application\Services\UomConversionResolverService;
use Modules\Product\Application\Services\UpdateVariantAttributeService;
use Modules\Product\Domain\RepositoryInterfaces\ComboItemRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttributeGroupRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttributeRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttributeValueRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductBrandRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductCategoryRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductIdentifierRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductSearchProjectionRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\VariantAttributeRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentComboItemRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductAttributeGroupRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductAttributeRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductAttributeValueRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductBrandRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductCategoryRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductIdentifierRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductSearchProjectionRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductVariantRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentUnitOfMeasureRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentUomConversionRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentVariantAttributeRepository;

class ProductServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $repositoryBindings = [
            ProductBrandRepositoryInterface::class => EloquentProductBrandRepository::class,
            ProductCategoryRepositoryInterface::class => EloquentProductCategoryRepository::class,
            ProductIdentifierRepositoryInterface::class => EloquentProductIdentifierRepository::class,
            ProductSearchProjectionRepositoryInterface::class => EloquentProductSearchProjectionRepository::class,
            ProductRepositoryInterface::class => EloquentProductRepository::class,
            ProductVariantRepositoryInterface::class => EloquentProductVariantRepository::class,
            UnitOfMeasureRepositoryInterface::class => EloquentUnitOfMeasureRepository::class,
            UomConversionRepositoryInterface::class => EloquentUomConversionRepository::class,
            ProductAttributeGroupRepositoryInterface::class => EloquentProductAttributeGroupRepository::class,
            ProductAttributeRepositoryInterface::class => EloquentProductAttributeRepository::class,
            ProductAttributeValueRepositoryInterface::class => EloquentProductAttributeValueRepository::class,
            VariantAttributeRepositoryInterface::class => EloquentVariantAttributeRepository::class,
            ComboItemRepositoryInterface::class => EloquentComboItemRepository::class,
        ];

        foreach ($repositoryBindings as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }

        $serviceBindings = [
            CreateProductBrandServiceInterface::class => CreateProductBrandService::class,
            FindProductBrandServiceInterface::class => FindProductBrandService::class,
            UpdateProductBrandServiceInterface::class => UpdateProductBrandService::class,
            DeleteProductBrandServiceInterface::class => DeleteProductBrandService::class,
            CreateProductCategoryServiceInterface::class => CreateProductCategoryService::class,
            FindProductCategoryServiceInterface::class => FindProductCategoryService::class,
            UpdateProductCategoryServiceInterface::class => UpdateProductCategoryService::class,
            DeleteProductCategoryServiceInterface::class => DeleteProductCategoryService::class,
            CreateProductIdentifierServiceInterface::class => CreateProductIdentifierService::class,
            FindProductIdentifierServiceInterface::class => FindProductIdentifierService::class,
            SearchProductsServiceInterface::class => SearchProductsService::class,
            RebuildProductSearchProjectionServiceInterface::class => RebuildProductSearchProjectionService::class,
            RefreshProductSearchProjectionServiceInterface::class => RefreshProductSearchProjectionService::class,
            UpdateProductIdentifierServiceInterface::class => UpdateProductIdentifierService::class,
            DeleteProductIdentifierServiceInterface::class => DeleteProductIdentifierService::class,
            CreateProductVariantServiceInterface::class => CreateProductVariantService::class,
            FindProductVariantServiceInterface::class => FindProductVariantService::class,
            UpdateProductVariantServiceInterface::class => UpdateProductVariantService::class,
            DeleteProductVariantServiceInterface::class => DeleteProductVariantService::class,
            CreateUnitOfMeasureServiceInterface::class => CreateUnitOfMeasureService::class,
            FindUnitOfMeasureServiceInterface::class => FindUnitOfMeasureService::class,
            UpdateUnitOfMeasureServiceInterface::class => UpdateUnitOfMeasureService::class,
            DeleteUnitOfMeasureServiceInterface::class => DeleteUnitOfMeasureService::class,
            CreateUomConversionServiceInterface::class => CreateUomConversionService::class,
            FindUomConversionServiceInterface::class => FindUomConversionService::class,
            UpdateUomConversionServiceInterface::class => UpdateUomConversionService::class,
            DeleteUomConversionServiceInterface::class => DeleteUomConversionService::class,
            UomConversionResolverServiceInterface::class => UomConversionResolverService::class,
            CreateProductServiceInterface::class => CreateProductService::class,
            FindProductServiceInterface::class => FindProductService::class,
            UpdateProductServiceInterface::class => UpdateProductService::class,
            DeleteProductServiceInterface::class => DeleteProductService::class,
            CreateProductAttributeGroupServiceInterface::class => CreateProductAttributeGroupService::class,
            FindProductAttributeGroupServiceInterface::class => FindProductAttributeGroupService::class,
            UpdateProductAttributeGroupServiceInterface::class => UpdateProductAttributeGroupService::class,
            DeleteProductAttributeGroupServiceInterface::class => DeleteProductAttributeGroupService::class,
            CreateProductAttributeServiceInterface::class => CreateProductAttributeService::class,
            FindProductAttributeServiceInterface::class => FindProductAttributeService::class,
            UpdateProductAttributeServiceInterface::class => UpdateProductAttributeService::class,
            DeleteProductAttributeServiceInterface::class => DeleteProductAttributeService::class,
            CreateProductAttributeValueServiceInterface::class => CreateProductAttributeValueService::class,
            FindProductAttributeValueServiceInterface::class => FindProductAttributeValueService::class,
            UpdateProductAttributeValueServiceInterface::class => UpdateProductAttributeValueService::class,
            DeleteProductAttributeValueServiceInterface::class => DeleteProductAttributeValueService::class,
            CreateVariantAttributeServiceInterface::class => CreateVariantAttributeService::class,
            FindVariantAttributeServiceInterface::class => FindVariantAttributeService::class,
            UpdateVariantAttributeServiceInterface::class => UpdateVariantAttributeService::class,
            DeleteVariantAttributeServiceInterface::class => DeleteVariantAttributeService::class,
            CreateComboItemServiceInterface::class => CreateComboItemService::class,
            FindComboItemServiceInterface::class => FindComboItemService::class,
            UpdateComboItemServiceInterface::class => UpdateComboItemService::class,
            DeleteComboItemServiceInterface::class => DeleteComboItemService::class,
        ];

        foreach ($serviceBindings as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}
