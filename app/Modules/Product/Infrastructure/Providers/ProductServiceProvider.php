<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Product\Application\Contracts\ArchiveProductServiceInterface;
use Modules\Product\Application\Contracts\ConvertUomServiceInterface;
use Modules\Product\Application\Contracts\CreateAttributeGroupServiceInterface;
use Modules\Product\Application\Contracts\CreateAttributeServiceInterface;
use Modules\Product\Application\Contracts\CreateAttributeValueServiceInterface;
use Modules\Product\Application\Contracts\CreateBatchServiceInterface;
use Modules\Product\Application\Contracts\CreateComboItemServiceInterface;
use Modules\Product\Application\Contracts\CreateProductAttachmentServiceInterface;
use Modules\Product\Application\Contracts\CreateProductBrandServiceInterface;
use Modules\Product\Application\Contracts\CreateProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\CreateProductIdentifierServiceInterface;
use Modules\Product\Application\Contracts\CreateProductServiceInterface;
use Modules\Product\Application\Contracts\CreateProductSupplierPriceServiceInterface;
use Modules\Product\Application\Contracts\CreateProductVariantServiceInterface;
use Modules\Product\Application\Contracts\CreateSerialServiceInterface;
use Modules\Product\Application\Contracts\CreateUnitOfMeasureServiceInterface;
use Modules\Product\Application\Contracts\CreateUomConversionServiceInterface;
use Modules\Product\Application\Contracts\DeleteAttributeGroupServiceInterface;
use Modules\Product\Application\Contracts\DeleteAttributeServiceInterface;
use Modules\Product\Application\Contracts\DeleteAttributeValueServiceInterface;
use Modules\Product\Application\Contracts\DeleteBatchServiceInterface;
use Modules\Product\Application\Contracts\DeleteComboItemServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductAttachmentServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductBrandServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductIdentifierServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductSupplierPriceServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductVariantServiceInterface;
use Modules\Product\Application\Contracts\DeleteSerialServiceInterface;
use Modules\Product\Application\Contracts\DeleteUnitOfMeasureServiceInterface;
use Modules\Product\Application\Contracts\DeleteUomConversionServiceInterface;
use Modules\Product\Application\Contracts\DiscontinueProductServiceInterface;
use Modules\Product\Application\Contracts\DraftProductServiceInterface;
use Modules\Product\Application\Contracts\FindAttributeGroupServiceInterface;
use Modules\Product\Application\Contracts\FindAttributeServiceInterface;
use Modules\Product\Application\Contracts\FindAttributeValueServiceInterface;
use Modules\Product\Application\Contracts\FindBatchServiceInterface;
use Modules\Product\Application\Contracts\FindComboItemServiceInterface;
use Modules\Product\Application\Contracts\FindProductAttachmentServiceInterface;
use Modules\Product\Application\Contracts\FindProductBrandServiceInterface;
use Modules\Product\Application\Contracts\FindProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\FindProductIdentifierServiceInterface;
use Modules\Product\Application\Contracts\FindProductServiceInterface;
use Modules\Product\Application\Contracts\FindProductSupplierPriceServiceInterface;
use Modules\Product\Application\Contracts\FindProductVariantServiceInterface;
use Modules\Product\Application\Contracts\FindSerialServiceInterface;
use Modules\Product\Application\Contracts\FindUnitOfMeasureServiceInterface;
use Modules\Product\Application\Contracts\FindUomConversionServiceInterface;
use Modules\Product\Application\Contracts\PublishProductServiceInterface;
use Modules\Product\Application\Contracts\UpdateAttributeGroupServiceInterface;
use Modules\Product\Application\Contracts\UpdateAttributeServiceInterface;
use Modules\Product\Application\Contracts\UpdateAttributeValueServiceInterface;
use Modules\Product\Application\Contracts\UpdateBatchServiceInterface;
use Modules\Product\Application\Contracts\UpdateComboItemServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductAttachmentServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductBrandServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductIdentifierServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductSupplierPriceServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductVariantServiceInterface;
use Modules\Product\Application\Contracts\UpdateSerialServiceInterface;
use Modules\Product\Application\Contracts\UpdateUnitOfMeasureServiceInterface;
use Modules\Product\Application\Contracts\UpdateUomConversionServiceInterface;
use Modules\Product\Application\Services\ArchiveProductService;
use Modules\Product\Application\Services\ConvertUomService;
use Modules\Product\Application\Services\CreateAttributeGroupService;
use Modules\Product\Application\Services\CreateAttributeService;
use Modules\Product\Application\Services\CreateAttributeValueService;
use Modules\Product\Application\Services\CreateBatchService;
use Modules\Product\Application\Services\CreateComboItemService;
use Modules\Product\Application\Services\CreateProductAttachmentService;
use Modules\Product\Application\Services\CreateProductBrandService;
use Modules\Product\Application\Services\CreateProductCategoryService;
use Modules\Product\Application\Services\CreateProductIdentifierService;
use Modules\Product\Application\Services\CreateProductService;
use Modules\Product\Application\Services\CreateProductSupplierPriceService;
use Modules\Product\Application\Services\CreateProductVariantService;
use Modules\Product\Application\Services\CreateSerialService;
use Modules\Product\Application\Services\CreateUnitOfMeasureService;
use Modules\Product\Application\Services\CreateUomConversionService;
use Modules\Product\Application\Services\DeleteAttributeGroupService;
use Modules\Product\Application\Services\DeleteAttributeService;
use Modules\Product\Application\Services\DeleteAttributeValueService;
use Modules\Product\Application\Services\DeleteBatchService;
use Modules\Product\Application\Services\DeleteComboItemService;
use Modules\Product\Application\Services\DeleteProductAttachmentService;
use Modules\Product\Application\Services\DeleteProductBrandService;
use Modules\Product\Application\Services\DeleteProductCategoryService;
use Modules\Product\Application\Services\DeleteProductIdentifierService;
use Modules\Product\Application\Services\DeleteProductService;
use Modules\Product\Application\Services\DeleteProductSupplierPriceService;
use Modules\Product\Application\Services\DeleteProductVariantService;
use Modules\Product\Application\Services\DeleteSerialService;
use Modules\Product\Application\Services\DeleteUnitOfMeasureService;
use Modules\Product\Application\Services\DeleteUomConversionService;
use Modules\Product\Application\Services\DiscontinueProductService;
use Modules\Product\Application\Services\DraftProductService;
use Modules\Product\Application\Services\FindAttributeGroupService;
use Modules\Product\Application\Services\FindAttributeService;
use Modules\Product\Application\Services\FindAttributeValueService;
use Modules\Product\Application\Services\FindBatchService;
use Modules\Product\Application\Services\FindComboItemService;
use Modules\Product\Application\Services\FindProductAttachmentService;
use Modules\Product\Application\Services\FindProductBrandService;
use Modules\Product\Application\Services\FindProductCategoryService;
use Modules\Product\Application\Services\FindProductIdentifierService;
use Modules\Product\Application\Services\FindProductService;
use Modules\Product\Application\Services\FindProductSupplierPriceService;
use Modules\Product\Application\Services\FindProductVariantService;
use Modules\Product\Application\Services\FindSerialService;
use Modules\Product\Application\Services\FindUnitOfMeasureService;
use Modules\Product\Application\Services\FindUomConversionService;
use Modules\Product\Application\Services\PublishProductService;
use Modules\Product\Application\Services\UpdateAttributeGroupService;
use Modules\Product\Application\Services\UpdateAttributeService;
use Modules\Product\Application\Services\UpdateAttributeValueService;
use Modules\Product\Application\Services\UpdateBatchService;
use Modules\Product\Application\Services\UpdateComboItemService;
use Modules\Product\Application\Services\UpdateProductAttachmentService;
use Modules\Product\Application\Services\UpdateProductBrandService;
use Modules\Product\Application\Services\UpdateProductCategoryService;
use Modules\Product\Application\Services\UpdateProductIdentifierService;
use Modules\Product\Application\Services\UpdateProductService;
use Modules\Product\Application\Services\UpdateProductSupplierPriceService;
use Modules\Product\Application\Services\UpdateProductVariantService;
use Modules\Product\Application\Services\UpdateSerialService;
use Modules\Product\Application\Services\UpdateUnitOfMeasureService;
use Modules\Product\Application\Services\UpdateUomConversionService;
use Modules\Product\Domain\RepositoryInterfaces\AttributeGroupRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\AttributeRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\AttributeValueRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\BatchRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ComboItemRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttachmentRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductBrandRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductCategoryRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductIdentifierRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductSupplierPriceRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\SerialRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentAttributeGroupRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentAttributeRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentAttributeValueRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentBatchRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentComboItemRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductAttachmentRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductBrandRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductCategoryRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductIdentifierRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductSupplierPriceRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductVariantRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentSerialRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentUnitOfMeasureRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentUomConversionRepository;

class ProductServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $repositoryBindings = [
            AttributeGroupRepositoryInterface::class => EloquentAttributeGroupRepository::class,
            AttributeRepositoryInterface::class => EloquentAttributeRepository::class,
            AttributeValueRepositoryInterface::class => EloquentAttributeValueRepository::class,
            BatchRepositoryInterface::class => EloquentBatchRepository::class,
            ComboItemRepositoryInterface::class => EloquentComboItemRepository::class,
            ProductAttachmentRepositoryInterface::class => EloquentProductAttachmentRepository::class,
            ProductBrandRepositoryInterface::class => EloquentProductBrandRepository::class,
            ProductCategoryRepositoryInterface::class => EloquentProductCategoryRepository::class,
            ProductIdentifierRepositoryInterface::class => EloquentProductIdentifierRepository::class,
            ProductRepositoryInterface::class => EloquentProductRepository::class,
            ProductSupplierPriceRepositoryInterface::class => EloquentProductSupplierPriceRepository::class,
            ProductVariantRepositoryInterface::class => EloquentProductVariantRepository::class,
            SerialRepositoryInterface::class => EloquentSerialRepository::class,
            UnitOfMeasureRepositoryInterface::class => EloquentUnitOfMeasureRepository::class,
            UomConversionRepositoryInterface::class => EloquentUomConversionRepository::class,
        ];

        foreach ($repositoryBindings as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }

        $serviceBindings = [
            ArchiveProductServiceInterface::class => ArchiveProductService::class,
            ConvertUomServiceInterface::class => ConvertUomService::class,
            DiscontinueProductServiceInterface::class => DiscontinueProductService::class,
            DraftProductServiceInterface::class => DraftProductService::class,
            PublishProductServiceInterface::class => PublishProductService::class,
            CreateAttributeGroupServiceInterface::class => CreateAttributeGroupService::class,
            FindAttributeGroupServiceInterface::class => FindAttributeGroupService::class,
            UpdateAttributeGroupServiceInterface::class => UpdateAttributeGroupService::class,
            DeleteAttributeGroupServiceInterface::class => DeleteAttributeGroupService::class,
            CreateAttributeServiceInterface::class => CreateAttributeService::class,
            FindAttributeServiceInterface::class => FindAttributeService::class,
            UpdateAttributeServiceInterface::class => UpdateAttributeService::class,
            DeleteAttributeServiceInterface::class => DeleteAttributeService::class,
            CreateAttributeValueServiceInterface::class => CreateAttributeValueService::class,
            FindAttributeValueServiceInterface::class => FindAttributeValueService::class,
            UpdateAttributeValueServiceInterface::class => UpdateAttributeValueService::class,
            DeleteAttributeValueServiceInterface::class => DeleteAttributeValueService::class,
            CreateBatchServiceInterface::class => CreateBatchService::class,
            FindBatchServiceInterface::class => FindBatchService::class,
            UpdateBatchServiceInterface::class => UpdateBatchService::class,
            DeleteBatchServiceInterface::class => DeleteBatchService::class,
            CreateSerialServiceInterface::class => CreateSerialService::class,
            FindSerialServiceInterface::class => FindSerialService::class,
            UpdateSerialServiceInterface::class => UpdateSerialService::class,
            DeleteSerialServiceInterface::class => DeleteSerialService::class,
            CreateComboItemServiceInterface::class => CreateComboItemService::class,
            FindComboItemServiceInterface::class => FindComboItemService::class,
            UpdateComboItemServiceInterface::class => UpdateComboItemService::class,
            DeleteComboItemServiceInterface::class => DeleteComboItemService::class,
            CreateProductAttachmentServiceInterface::class => CreateProductAttachmentService::class,
            FindProductAttachmentServiceInterface::class => FindProductAttachmentService::class,
            UpdateProductAttachmentServiceInterface::class => UpdateProductAttachmentService::class,
            DeleteProductAttachmentServiceInterface::class => DeleteProductAttachmentService::class,
            CreateProductSupplierPriceServiceInterface::class => CreateProductSupplierPriceService::class,
            FindProductSupplierPriceServiceInterface::class => FindProductSupplierPriceService::class,
            UpdateProductSupplierPriceServiceInterface::class => UpdateProductSupplierPriceService::class,
            DeleteProductSupplierPriceServiceInterface::class => DeleteProductSupplierPriceService::class,
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
            CreateProductServiceInterface::class => CreateProductService::class,
            FindProductServiceInterface::class => FindProductService::class,
            UpdateProductServiceInterface::class => UpdateProductService::class,
            DeleteProductServiceInterface::class => DeleteProductService::class,
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
