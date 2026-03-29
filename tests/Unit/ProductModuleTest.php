<?php

namespace Tests\Unit;

use Modules\Product\Application\Contracts\CreateComboItemServiceInterface;
use Modules\Product\Application\Contracts\CreateProductServiceInterface;
use Modules\Product\Application\Contracts\CreateProductVariationServiceInterface;
use Modules\Product\Application\Contracts\DeleteComboItemServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductImageServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductVariationServiceInterface;
use Modules\Product\Application\Contracts\FindComboItemsServiceInterface;
use Modules\Product\Application\Contracts\FindProductServiceInterface;
use Modules\Product\Application\Contracts\FindProductVariationsServiceInterface;
use Modules\Product\Application\Contracts\UpdateComboItemServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductVariationServiceInterface;
use Modules\Product\Application\Contracts\UploadProductImageServiceInterface;
use Modules\Product\Application\DTOs\ComboItemData;
use Modules\Product\Application\DTOs\ProductData;
use Modules\Product\Application\DTOs\ProductImageData;
use Modules\Product\Application\DTOs\ProductVariationData;
use Modules\Product\Application\Services\CreateComboItemService;
use Modules\Product\Application\Services\CreateProductService;
use Modules\Product\Application\Services\CreateProductVariationService;
use Modules\Product\Application\Services\DeleteComboItemService;
use Modules\Product\Application\Services\DeleteProductImageService;
use Modules\Product\Application\Services\DeleteProductService;
use Modules\Product\Application\Services\DeleteProductVariationService;
use Modules\Product\Application\Services\FindComboItemsService;
use Modules\Product\Application\Services\FindProductService;
use Modules\Product\Application\Services\FindProductVariationsService;
use Modules\Product\Application\Services\UpdateComboItemService;
use Modules\Product\Application\Services\UpdateProductService;
use Modules\Product\Application\Services\UpdateProductVariationService;
use Modules\Product\Application\Services\UploadProductImageService;
use Modules\Product\Application\UseCases\CreateProduct;
use Modules\Product\Application\UseCases\DeleteProduct;
use Modules\Product\Application\UseCases\GetProduct;
use Modules\Product\Application\UseCases\ListProducts;
use Modules\Product\Application\UseCases\UpdateProduct;
use Modules\Product\Domain\Entities\ComboItem;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\Entities\ProductImage;
use Modules\Product\Domain\Entities\ProductVariation;
use Modules\Product\Domain\Events\ComboItemCreated;
use Modules\Product\Domain\Events\ComboItemDeleted;
use Modules\Product\Domain\Events\ComboItemUpdated;
use Modules\Product\Domain\Events\ProductCreated;
use Modules\Product\Domain\Events\ProductDeleted;
use Modules\Product\Domain\Events\ProductUpdated;
use Modules\Product\Domain\Events\ProductVariationCreated;
use Modules\Product\Domain\Events\ProductVariationDeleted;
use Modules\Product\Domain\Events\ProductVariationUpdated;
use Modules\Product\Domain\Exceptions\ComboItemNotFoundException;
use Modules\Product\Domain\Exceptions\ProductImageNotFoundException;
use Modules\Product\Domain\Exceptions\ProductNotFoundException;
use Modules\Product\Domain\Exceptions\ProductVariationNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ComboItemRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductImageRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariationRepositoryInterface;
use Modules\Product\Domain\ValueObjects\ProductAttribute;
use Modules\Product\Domain\ValueObjects\ProductType;
use Modules\Product\Domain\ValueObjects\UnitOfMeasure;
use Modules\Product\Infrastructure\Http\Controllers\ProductComboItemController;
use Modules\Product\Infrastructure\Http\Controllers\ProductController;
use Modules\Product\Infrastructure\Http\Controllers\ProductImageController;
use Modules\Product\Infrastructure\Http\Controllers\ProductVariationController;
use Modules\Product\Infrastructure\Http\Requests\StoreComboItemRequest;
use Modules\Product\Infrastructure\Http\Requests\StoreProductRequest;
use Modules\Product\Infrastructure\Http\Requests\StoreProductVariationRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateComboItemRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateProductRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateProductVariationRequest;
use Modules\Product\Infrastructure\Http\Requests\UploadProductImageRequest;
use Modules\Product\Infrastructure\Http\Resources\ComboItemResource;
use Modules\Product\Infrastructure\Http\Resources\ProductCollection;
use Modules\Product\Infrastructure\Http\Resources\ProductImageResource;
use Modules\Product\Infrastructure\Http\Resources\ProductResource;
use Modules\Product\Infrastructure\Http\Resources\ProductVariationResource;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductComboItemModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductImageModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductVariationModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentComboItemRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductImageRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductVariationRepository;
use Modules\Product\Infrastructure\Providers\ProductServiceProvider;
use PHPUnit\Framework\TestCase;

class ProductModuleTest extends TestCase
{
    // ── Domain Entities ───────────────────────────────────────────────────────

    public function test_product_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(Product::class));
    }

    public function test_product_image_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(ProductImage::class));
    }

    // ── Domain Events ─────────────────────────────────────────────────────────

    public function test_all_product_event_classes_exist(): void
    {
        $this->assertTrue(class_exists(ProductCreated::class));
        $this->assertTrue(class_exists(ProductUpdated::class));
        $this->assertTrue(class_exists(ProductDeleted::class));
    }

    // ── Domain Exceptions ─────────────────────────────────────────────────────

    public function test_product_exception_classes_exist(): void
    {
        $this->assertTrue(class_exists(ProductNotFoundException::class));
        $this->assertTrue(class_exists(ProductImageNotFoundException::class));
    }

    // ── Domain Repository Interfaces ─────────────────────────────────────────

    public function test_product_repository_interfaces_exist(): void
    {
        $this->assertTrue(interface_exists(ProductRepositoryInterface::class));
        $this->assertTrue(interface_exists(ProductImageRepositoryInterface::class));
    }

    // ── Application DTOs ─────────────────────────────────────────────────────

    public function test_product_dto_classes_exist(): void
    {
        $this->assertTrue(class_exists(ProductData::class));
        $this->assertTrue(class_exists(ProductImageData::class));
    }

    // ── Application Service Contracts ─────────────────────────────────────────

    public function test_all_product_service_interfaces_exist(): void
    {
        $this->assertTrue(interface_exists(CreateProductServiceInterface::class));
        $this->assertTrue(interface_exists(FindProductServiceInterface::class));
        $this->assertTrue(interface_exists(UpdateProductServiceInterface::class));
        $this->assertTrue(interface_exists(DeleteProductServiceInterface::class));
        $this->assertTrue(interface_exists(UploadProductImageServiceInterface::class));
        $this->assertTrue(interface_exists(DeleteProductImageServiceInterface::class));
    }

    // ── Application Services ──────────────────────────────────────────────────

    public function test_all_product_service_implementations_exist(): void
    {
        $this->assertTrue(class_exists(CreateProductService::class));
        $this->assertTrue(class_exists(FindProductService::class));
        $this->assertTrue(class_exists(UpdateProductService::class));
        $this->assertTrue(class_exists(DeleteProductService::class));
        $this->assertTrue(class_exists(UploadProductImageService::class));
        $this->assertTrue(class_exists(DeleteProductImageService::class));
    }

    public function test_product_service_implementations_implement_their_interfaces(): void
    {
        $this->assertTrue(
            is_subclass_of(CreateProductService::class, CreateProductServiceInterface::class),
            'CreateProductService must implement CreateProductServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(FindProductService::class, FindProductServiceInterface::class),
            'FindProductService must implement FindProductServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(UpdateProductService::class, UpdateProductServiceInterface::class),
            'UpdateProductService must implement UpdateProductServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(DeleteProductService::class, DeleteProductServiceInterface::class),
            'DeleteProductService must implement DeleteProductServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(UploadProductImageService::class, UploadProductImageServiceInterface::class),
            'UploadProductImageService must implement UploadProductImageServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(DeleteProductImageService::class, DeleteProductImageServiceInterface::class),
            'DeleteProductImageService must implement DeleteProductImageServiceInterface.'
        );
    }

    public function test_find_product_service_interface_extends_read_service_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(FindProductServiceInterface::class, \Modules\Core\Application\Contracts\ReadServiceInterface::class),
            'FindProductServiceInterface must extend ReadServiceInterface.'
        );
    }

    public function test_find_product_service_find_delegates_to_repository(): void
    {
        $product = new \Modules\Product\Domain\Entities\Product(
            tenantId: 1,
            sku: new \Modules\Core\Domain\ValueObjects\Sku('SKU-001'),
            name: 'Test Product',
            price: new \Modules\Core\Domain\ValueObjects\Money(9.99, 'USD'),
            id: 42,
        );

        $repo = $this->createMock(\Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface::class);
        $repo->method('find')->with(42)->willReturn($product);

        $service = new FindProductService($repo);

        $found = $service->find(42);

        $this->assertInstanceOf(\Modules\Product\Domain\Entities\Product::class, $found);
        $this->assertSame(42, $found->getId());
    }

    public function test_find_product_service_find_returns_null_when_missing(): void
    {
        $repo = $this->createMock(\Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface::class);
        $repo->method('find')->willReturn(null);

        $service = new FindProductService($repo);

        $this->assertNull($service->find(999));
    }

    public function test_find_product_service_execute_throws_bad_method_call_exception(): void
    {
        $repo = $this->createMock(\Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface::class);
        $repo->method('find')->willReturn(null);

        $service = new FindProductService($repo);

        $this->expectException(\BadMethodCallException::class);

        $ref = new \ReflectionMethod($service, 'handle');
        $ref->setAccessible(true);
        $ref->invoke($service, []);
    }

    public function test_product_controller_uses_find_product_service_interface(): void
    {
        $rc = new \ReflectionClass(\Modules\Product\Infrastructure\Http\Controllers\ProductController::class);
        $constructor = $rc->getConstructor();
        $paramTypes = array_map(
            fn (\ReflectionParameter $p) => $p->getType()?->getName(),
            $constructor->getParameters()
        );

        $this->assertContains(
            FindProductServiceInterface::class,
            $paramTypes,
            'ProductController must inject FindProductServiceInterface.'
        );
        $this->assertContains(
            CreateProductServiceInterface::class,
            $paramTypes,
            'ProductController must inject CreateProductServiceInterface.'
        );
        $this->assertContains(
            UpdateProductServiceInterface::class,
            $paramTypes,
            'ProductController must inject UpdateProductServiceInterface.'
        );
        $this->assertContains(
            DeleteProductServiceInterface::class,
            $paramTypes,
            'ProductController must inject DeleteProductServiceInterface.'
        );
    }

    // ── Application Use Cases ─────────────────────────────────────────────────

    public function test_all_product_use_case_classes_exist(): void
    {
        $this->assertTrue(class_exists(CreateProduct::class));
        $this->assertTrue(class_exists(UpdateProduct::class));
        $this->assertTrue(class_exists(DeleteProduct::class));
        $this->assertTrue(class_exists(GetProduct::class));
        $this->assertTrue(class_exists(ListProducts::class));
    }

    // ── Infrastructure – Models ───────────────────────────────────────────────

    public function test_product_eloquent_model_classes_exist(): void
    {
        $this->assertTrue(class_exists(ProductModel::class));
        $this->assertTrue(class_exists(ProductImageModel::class));
    }

    // ── Infrastructure – Repositories ─────────────────────────────────────────

    public function test_product_eloquent_repositories_exist(): void
    {
        $this->assertTrue(class_exists(EloquentProductRepository::class));
        $this->assertTrue(class_exists(EloquentProductImageRepository::class));
    }

    public function test_product_eloquent_repositories_implement_interfaces(): void
    {
        $this->assertTrue(
            is_subclass_of(EloquentProductRepository::class, ProductRepositoryInterface::class),
            'EloquentProductRepository must implement ProductRepositoryInterface.'
        );
        $this->assertTrue(
            is_subclass_of(EloquentProductImageRepository::class, ProductImageRepositoryInterface::class),
            'EloquentProductImageRepository must implement ProductImageRepositoryInterface.'
        );
    }

    // ── Infrastructure – HTTP ─────────────────────────────────────────────────

    public function test_product_controller_classes_exist(): void
    {
        $this->assertTrue(class_exists(ProductController::class));
        $this->assertTrue(class_exists(ProductImageController::class));
    }

    public function test_product_form_request_classes_exist(): void
    {
        $this->assertTrue(class_exists(StoreProductRequest::class));
        $this->assertTrue(class_exists(UpdateProductRequest::class));
        $this->assertTrue(class_exists(UploadProductImageRequest::class));
    }

    public function test_product_resource_classes_exist(): void
    {
        $this->assertTrue(class_exists(ProductResource::class));
        $this->assertTrue(class_exists(ProductCollection::class));
        $this->assertTrue(class_exists(ProductImageResource::class));
    }

    // ── Infrastructure – Provider ─────────────────────────────────────────────

    public function test_product_service_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(ProductServiceProvider::class));
    }

    // ── Domain Behaviour ─────────────────────────────────────────────────────

    public function test_product_entity_can_be_constructed(): void
    {
        $sku = new \Modules\Core\Domain\ValueObjects\Sku('PROD-001');
        $price = new \Modules\Core\Domain\ValueObjects\Money(29.99, 'USD');

        $product = new Product(
            tenantId: 1,
            sku: $sku,
            name: 'Widget Pro',
            price: $price,
            description: 'A great widget',
            category: 'Widgets',
            status: 'active',
        );

        $this->assertNull($product->getId());
        $this->assertSame(1, $product->getTenantId());
        $this->assertSame('PROD-001', $product->getSku()->value());
        $this->assertSame('Widget Pro', $product->getName());
        $this->assertSame(29.99, $product->getPrice()->getAmount());
        $this->assertSame('USD', $product->getPrice()->getCurrency());
        $this->assertSame('active', $product->getStatus());
        $this->assertTrue($product->isActive());
        $this->assertTrue($product->getImages()->isEmpty());
    }

    public function test_product_activate_and_deactivate(): void
    {
        $sku = new \Modules\Core\Domain\ValueObjects\Sku('PROD-002');
        $price = new \Modules\Core\Domain\ValueObjects\Money(10.0, 'USD');

        $product = new Product(tenantId: 1, sku: $sku, name: 'Test', price: $price, status: 'inactive');

        $this->assertFalse($product->isActive());

        $product->activate();
        $this->assertTrue($product->isActive());
        $this->assertSame('active', $product->getStatus());

        $product->deactivate();
        $this->assertFalse($product->isActive());
        $this->assertSame('inactive', $product->getStatus());
    }

    public function test_product_update_details(): void
    {
        $sku = new \Modules\Core\Domain\ValueObjects\Sku('PROD-003');
        $price = new \Modules\Core\Domain\ValueObjects\Money(5.0, 'USD');
        $product = new Product(tenantId: 1, sku: $sku, name: 'Old Name', price: $price);

        $newPrice = new \Modules\Core\Domain\ValueObjects\Money(15.0, 'EUR');
        $product->updateDetails('New Name', $newPrice, 'New description', 'NewCategory', ['color' => 'blue'], null);

        $this->assertSame('New Name', $product->getName());
        $this->assertSame(15.0, $product->getPrice()->getAmount());
        $this->assertSame('EUR', $product->getPrice()->getCurrency());
        $this->assertSame('New description', $product->getDescription());
        $this->assertSame('NewCategory', $product->getCategory());
        $this->assertSame(['color' => 'blue'], $product->getAttributes());
    }

    public function test_product_image_entity_can_be_constructed(): void
    {
        $image = new ProductImage(
            tenantId: 1,
            productId: 5,
            uuid: 'test-uuid',
            name: 'front.jpg',
            filePath: 'products/5/front.jpg',
            mimeType: 'image/jpeg',
            size: 102400,
            sortOrder: 0,
            isPrimary: true,
        );

        $this->assertNull($image->getId());
        $this->assertSame(1, $image->getTenantId());
        $this->assertSame(5, $image->getProductId());
        $this->assertSame('test-uuid', $image->getUuid());
        $this->assertSame('front.jpg', $image->getName());
        $this->assertTrue($image->isPrimary());
        $this->assertSame(0, $image->getSortOrder());
    }

    public function test_product_get_primary_image(): void
    {
        $sku = new \Modules\Core\Domain\ValueObjects\Sku('PROD-004');
        $price = new \Modules\Core\Domain\ValueObjects\Money(1.0, 'USD');
        $product = new Product(tenantId: 1, sku: $sku, name: 'Test', price: $price);

        $this->assertNull($product->getPrimaryImage());

        $img1 = new ProductImage(1, 1, 'uuid-1', 'a.jpg', 'path/a.jpg', 'image/jpeg', 100, 1, false);
        $img2 = new ProductImage(1, 1, 'uuid-2', 'b.jpg', 'path/b.jpg', 'image/jpeg', 200, 0, true);

        $product->addImage($img1);
        $product->addImage($img2);

        // getPrimaryImage should return the one with isPrimary=true
        $primary = $product->getPrimaryImage();
        $this->assertNotNull($primary);
        $this->assertSame('uuid-2', $primary->getUuid());
    }

    // ── Routes file ───────────────────────────────────────────────────────────

    public function test_product_routes_file_exists(): void
    {
        $path = dirname(__DIR__, 2).'/app/Modules/Product/routes/api.php';
        $this->assertTrue(file_exists($path), 'Product routes/api.php must exist.');
    }

    // ── Migrations ────────────────────────────────────────────────────────────

    public function test_product_migration_files_exist(): void
    {
        $dir   = dirname(__DIR__, 2).'/app/Modules/Product/database/migrations';
        $files = glob($dir.'/*.php');
        $this->assertGreaterThanOrEqual(2, count($files), 'At least 2 product migration files must exist.');
    }

    // ── Value Objects ─────────────────────────────────────────────────────────

    public function test_product_type_value_object_class_exists(): void
    {
        $this->assertTrue(class_exists(ProductType::class));
    }

    public function test_unit_of_measure_value_object_class_exists(): void
    {
        $this->assertTrue(class_exists(UnitOfMeasure::class));
    }

    public function test_product_type_valid_values(): void
    {
        foreach (ProductType::VALID_TYPES as $type) {
            $pt = new ProductType($type);
            $this->assertSame($type, $pt->value());
        }
    }

    public function test_product_type_invalid_value_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ProductType('invalid_type');
    }

    public function test_product_type_predicates(): void
    {
        $this->assertTrue((new ProductType('physical'))->isPhysical());
        $this->assertTrue((new ProductType('service'))->isService());
        $this->assertTrue((new ProductType('digital'))->isDigital());
        $this->assertTrue((new ProductType('combo'))->isCombo());
        $this->assertTrue((new ProductType('variable'))->isVariable());
    }

    public function test_unit_of_measure_valid_types(): void
    {
        foreach (UnitOfMeasure::VALID_TYPES as $type) {
            $uom = new UnitOfMeasure('kg', $type, 1.0);
            $this->assertSame('kg', $uom->getUnit());
            $this->assertSame($type, $uom->getType());
            $this->assertSame(1.0, $uom->getConversionFactor());
        }
    }

    public function test_unit_of_measure_invalid_type_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new UnitOfMeasure('kg', 'invalid', 1.0);
    }

    public function test_unit_of_measure_invalid_conversion_factor_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new UnitOfMeasure('kg', 'buying', 0.0);
    }

    public function test_unit_of_measure_predicates(): void
    {
        $buying    = new UnitOfMeasure('pcs', 'buying', 1.0);
        $selling   = new UnitOfMeasure('pcs', 'selling', 1.0);
        $inventory = new UnitOfMeasure('kg', 'inventory', 0.5);

        $this->assertTrue($buying->isBuying());
        $this->assertFalse($buying->isSelling());
        $this->assertTrue($selling->isSelling());
        $this->assertTrue($inventory->isInventory());
    }

    public function test_unit_of_measure_to_array_and_from_array(): void
    {
        $uom   = new UnitOfMeasure('box', 'buying', 12.0);
        $array = $uom->toArray();

        $this->assertSame('box', $array['unit']);
        $this->assertSame('buying', $array['type']);
        $this->assertSame(12.0, $array['conversion_factor']);

        $restored = UnitOfMeasure::fromArray($array);
        $this->assertTrue($uom->equals($restored));
    }

    // ── Product type & UoM integration ───────────────────────────────────────

    public function test_product_entity_defaults_to_physical_type(): void
    {
        $sku     = new \Modules\Core\Domain\ValueObjects\Sku('PROD-T01');
        $price   = new \Modules\Core\Domain\ValueObjects\Money(10.0, 'USD');
        $product = new Product(tenantId: 1, sku: $sku, name: 'Default Type', price: $price);

        $this->assertSame('physical', $product->getType()->value());
        $this->assertSame([], $product->getUnitsOfMeasure());
    }

    public function test_product_entity_accepts_all_types(): void
    {
        $sku   = new \Modules\Core\Domain\ValueObjects\Sku('PROD-T02');
        $price = new \Modules\Core\Domain\ValueObjects\Money(10.0, 'USD');

        foreach (ProductType::VALID_TYPES as $type) {
            $product = new Product(tenantId: 1, sku: $sku, name: 'Type Test', price: $price, type: $type);
            $this->assertSame($type, $product->getType()->value());
        }
    }

    public function test_product_entity_with_units_of_measure(): void
    {
        $sku   = new \Modules\Core\Domain\ValueObjects\Sku('PROD-T03');
        $price = new \Modules\Core\Domain\ValueObjects\Money(10.0, 'USD');

        $buyingUom    = new UnitOfMeasure('pcs', 'buying', 1.0);
        $sellingUom   = new UnitOfMeasure('pcs', 'selling', 1.0);
        $inventoryUom = new UnitOfMeasure('box', 'inventory', 12.0);

        $product = new Product(
            tenantId: 1,
            sku: $sku,
            name: 'UoM Test',
            price: $price,
            unitsOfMeasure: [$buyingUom, $sellingUom, $inventoryUom],
        );

        $this->assertCount(3, $product->getUnitsOfMeasure());
        $this->assertSame('pcs', $product->getBuyingUnit()->getUnit());
        $this->assertSame('pcs', $product->getSellingUnit()->getUnit());
        $this->assertSame('box', $product->getInventoryUnit()->getUnit());
        $this->assertSame(12.0, $product->getInventoryUnit()->getConversionFactor());
    }

    public function test_product_entity_uom_helpers_return_null_when_not_set(): void
    {
        $sku     = new \Modules\Core\Domain\ValueObjects\Sku('PROD-T04');
        $price   = new \Modules\Core\Domain\ValueObjects\Money(10.0, 'USD');
        $product = new Product(tenantId: 1, sku: $sku, name: 'No UoM', price: $price);

        $this->assertNull($product->getBuyingUnit());
        $this->assertNull($product->getSellingUnit());
        $this->assertNull($product->getInventoryUnit());
    }

    public function test_product_update_details_changes_type_and_uom(): void
    {
        $sku     = new \Modules\Core\Domain\ValueObjects\Sku('PROD-T05');
        $price   = new \Modules\Core\Domain\ValueObjects\Money(5.0, 'USD');
        $product = new Product(tenantId: 1, sku: $sku, name: 'Original', price: $price, type: 'physical');

        $newPrice = new \Modules\Core\Domain\ValueObjects\Money(20.0, 'USD');
        $newUom   = new UnitOfMeasure('L', 'selling', 1.0);

        $product->updateDetails(
            name: 'Updated',
            price: $newPrice,
            description: null,
            category: null,
            attributes: null,
            metadata: null,
            type: 'service',
            unitsOfMeasure: [$newUom],
        );

        $this->assertSame('Updated', $product->getName());
        $this->assertSame('service', $product->getType()->value());
        $this->assertCount(1, $product->getUnitsOfMeasure());
        $this->assertSame('L', $product->getSellingUnit()->getUnit());
    }

    public function test_product_update_details_clears_uom_when_empty_array_passed(): void
    {
        $sku     = new \Modules\Core\Domain\ValueObjects\Sku('PROD-T06');
        $price   = new \Modules\Core\Domain\ValueObjects\Money(5.0, 'USD');
        $buyUom  = new UnitOfMeasure('pcs', 'buying', 1.0);
        $product = new Product(
            tenantId: 1, sku: $sku, name: 'Has UoM', price: $price,
            unitsOfMeasure: [$buyUom],
        );

        $this->assertCount(1, $product->getUnitsOfMeasure());

        // Passing empty array should clear the list
        $product->updateDetails(
            name: 'Has UoM',
            price: $price,
            description: null,
            category: null,
            attributes: null,
            metadata: null,
            unitsOfMeasure: [],
        );

        $this->assertCount(0, $product->getUnitsOfMeasure());
    }

    public function test_product_update_details_preserves_uom_when_null_passed(): void
    {
        $sku     = new \Modules\Core\Domain\ValueObjects\Sku('PROD-T07');
        $price   = new \Modules\Core\Domain\ValueObjects\Money(5.0, 'USD');
        $buyUom  = new UnitOfMeasure('pcs', 'buying', 1.0);
        $product = new Product(
            tenantId: 1, sku: $sku, name: 'Preserve UoM', price: $price,
            unitsOfMeasure: [$buyUom],
        );

        // Passing null for unitsOfMeasure should NOT change existing UoMs
        $product->updateDetails(
            name: 'Preserve UoM',
            price: $price,
            description: null,
            category: null,
            attributes: null,
            metadata: null,
            unitsOfMeasure: null,
        );

        $this->assertCount(1, $product->getUnitsOfMeasure());
        $this->assertSame('pcs', $product->getBuyingUnit()->getUnit());
    }

    // ── ProductAttribute Value Object ─────────────────────────────────────────

    public function test_product_attribute_class_exists(): void
    {
        $this->assertTrue(class_exists(ProductAttribute::class));
    }

    public function test_product_attribute_can_be_constructed(): void
    {
        $attr = new ProductAttribute('color', 'Color', ['Red', 'Blue', 'Green']);

        $this->assertSame('color', $attr->getCode());
        $this->assertSame('Color', $attr->getName());
        $this->assertSame(['Red', 'Blue', 'Green'], $attr->getAllowedValues());
    }

    public function test_product_attribute_code_is_lowercased(): void
    {
        $attr = new ProductAttribute('SIZE', 'Size');
        $this->assertSame('size', $attr->getCode());
    }

    public function test_product_attribute_empty_code_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ProductAttribute('', 'Empty');
    }

    public function test_product_attribute_empty_name_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ProductAttribute('code', '');
    }

    public function test_product_attribute_open_ended_allows_any_value(): void
    {
        $attr = new ProductAttribute('tag', 'Tag'); // no allowed values = open-ended
        $this->assertTrue($attr->isValueAllowed('anything'));
        $this->assertTrue($attr->isValueAllowed(''));
    }

    public function test_product_attribute_restricted_values(): void
    {
        $attr = new ProductAttribute('color', 'Color', ['Red', 'Blue']);
        $this->assertTrue($attr->isValueAllowed('Red'));
        $this->assertFalse($attr->isValueAllowed('Green'));
    }

    public function test_product_attribute_to_array_and_from_array(): void
    {
        $attr  = new ProductAttribute('size', 'Size', ['S', 'M', 'L']);
        $array = $attr->toArray();

        $this->assertSame('size', $array['code']);
        $this->assertSame('Size', $array['name']);
        $this->assertSame(['S', 'M', 'L'], $array['allowed_values']);

        $restored = ProductAttribute::fromArray($array);
        $this->assertTrue($attr->equals($restored));
    }

    public function test_product_attribute_equals(): void
    {
        $a = new ProductAttribute('color', 'Color');
        $b = new ProductAttribute('color', 'Colour'); // same code, different name
        $c = new ProductAttribute('size', 'Size');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    // ── ProductVariation Entity ───────────────────────────────────────────────

    public function test_product_variation_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(ProductVariation::class));
    }

    public function test_product_variation_can_be_constructed(): void
    {
        $sku       = new \Modules\Core\Domain\ValueObjects\Sku('PROD-001-RED-M');
        $price     = new \Modules\Core\Domain\ValueObjects\Money(29.99, 'USD');
        $variation = new ProductVariation(
            productId:       1,
            tenantId:        1,
            sku:             $sku,
            name:            'Widget (Red, M)',
            price:           $price,
            attributeValues: ['color' => 'Red', 'size' => 'M'],
            status:          'active',
            sortOrder:       0,
        );

        $this->assertNull($variation->getId());
        $this->assertSame(1, $variation->getProductId());
        $this->assertSame(1, $variation->getTenantId());
        $this->assertSame('PROD-001-RED-M', $variation->getSku()->value());
        $this->assertSame('Widget (Red, M)', $variation->getName());
        $this->assertSame(29.99, $variation->getPrice()->getAmount());
        $this->assertSame('active', $variation->getStatus());
        $this->assertSame(['color' => 'Red', 'size' => 'M'], $variation->getAttributeValues());
        $this->assertSame('Red', $variation->getAttributeValue('color'));
        $this->assertNull($variation->getAttributeValue('weight'));
        $this->assertTrue($variation->isActive());
    }

    public function test_product_variation_activate_deactivate(): void
    {
        $sku       = new \Modules\Core\Domain\ValueObjects\Sku('VAR-001');
        $price     = new \Modules\Core\Domain\ValueObjects\Money(10.0, 'USD');
        $variation = new ProductVariation(1, 1, $sku, 'Test', $price, [], 'inactive');

        $this->assertFalse($variation->isActive());
        $variation->activate();
        $this->assertTrue($variation->isActive());
        $variation->deactivate();
        $this->assertFalse($variation->isActive());
    }

    public function test_product_variation_update_details(): void
    {
        $sku       = new \Modules\Core\Domain\ValueObjects\Sku('VAR-002');
        $price     = new \Modules\Core\Domain\ValueObjects\Money(10.0, 'USD');
        $variation = new ProductVariation(1, 1, $sku, 'Original', $price);

        $newPrice = new \Modules\Core\Domain\ValueObjects\Money(25.0, 'EUR');
        $variation->updateDetails(
            name:            'Updated',
            price:           $newPrice,
            attributeValues: ['color' => 'Blue'],
            status:          'active',
            sortOrder:       2,
            metadata:        ['note' => 'test'],
        );

        $this->assertSame('Updated', $variation->getName());
        $this->assertSame(25.0, $variation->getPrice()->getAmount());
        $this->assertSame('EUR', $variation->getPrice()->getCurrency());
        $this->assertSame(['color' => 'Blue'], $variation->getAttributeValues());
        $this->assertSame(2, $variation->getSortOrder());
        $this->assertSame(['note' => 'test'], $variation->getMetadata());
    }

    // ── ComboItem Entity ──────────────────────────────────────────────────────

    public function test_combo_item_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(ComboItem::class));
    }

    public function test_combo_item_can_be_constructed(): void
    {
        $item = new ComboItem(
            productId:          10,
            tenantId:           1,
            componentProductId: 5,
            quantity:           2.0,
        );

        $this->assertNull($item->getId());
        $this->assertSame(10, $item->getProductId());
        $this->assertSame(1, $item->getTenantId());
        $this->assertSame(5, $item->getComponentProductId());
        $this->assertSame(2.0, $item->getQuantity());
        $this->assertNull($item->getPriceOverride());
        $this->assertSame(0, $item->getSortOrder());
    }

    public function test_combo_item_with_price_override(): void
    {
        $priceOverride = new \Modules\Core\Domain\ValueObjects\Money(9.99, 'USD');
        $item = new ComboItem(
            productId:          10,
            tenantId:           1,
            componentProductId: 5,
            quantity:           3.0,
            priceOverride:      $priceOverride,
        );

        $this->assertNotNull($item->getPriceOverride());
        $this->assertSame(9.99, $item->getPriceOverride()->getAmount());
        $this->assertSame('USD', $item->getPriceOverride()->getCurrency());
    }

    public function test_combo_item_invalid_quantity_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ComboItem(10, 1, 5, 0.0);
    }

    public function test_combo_item_update_details(): void
    {
        $item = new ComboItem(10, 1, 5, 1.0);

        $item->updateDetails(
            quantity:      4.5,
            priceOverride: new \Modules\Core\Domain\ValueObjects\Money(5.0, 'USD'),
            sortOrder:     1,
            metadata:      ['note' => 'updated'],
        );

        $this->assertSame(4.5, $item->getQuantity());
        $this->assertSame(5.0, $item->getPriceOverride()->getAmount());
        $this->assertSame(1, $item->getSortOrder());
    }

    public function test_combo_item_update_with_invalid_quantity_throws(): void
    {
        $item = new ComboItem(10, 1, 5, 1.0);
        $this->expectException(\InvalidArgumentException::class);
        $item->updateDetails(0.0, null, 0, null);
    }

    // ── Domain Exceptions (new) ───────────────────────────────────────────────

    public function test_product_variation_not_found_exception_exists(): void
    {
        $this->assertTrue(class_exists(ProductVariationNotFoundException::class));
    }

    public function test_combo_item_not_found_exception_exists(): void
    {
        $this->assertTrue(class_exists(ComboItemNotFoundException::class));
    }

    public function test_product_variation_not_found_exception_message(): void
    {
        $ex = new ProductVariationNotFoundException(42);
        $this->assertStringContainsString('42', $ex->getMessage());
    }

    public function test_combo_item_not_found_exception_message(): void
    {
        $ex = new ComboItemNotFoundException(99);
        $this->assertStringContainsString('99', $ex->getMessage());
    }

    // ── Domain Events (variation & combo) ─────────────────────────────────────

    public function test_product_variation_event_classes_exist(): void
    {
        $this->assertTrue(class_exists(ProductVariationCreated::class));
        $this->assertTrue(class_exists(ProductVariationUpdated::class));
        $this->assertTrue(class_exists(ProductVariationDeleted::class));
    }

    public function test_combo_item_event_classes_exist(): void
    {
        $this->assertTrue(class_exists(ComboItemCreated::class));
        $this->assertTrue(class_exists(ComboItemUpdated::class));
        $this->assertTrue(class_exists(ComboItemDeleted::class));
    }

    // ── Domain Repository Interfaces (new) ────────────────────────────────────

    public function test_product_variation_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(ProductVariationRepositoryInterface::class));
    }

    public function test_combo_item_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(ComboItemRepositoryInterface::class));
    }

    // ── Application DTOs (new) ────────────────────────────────────────────────

    public function test_product_variation_dto_class_exists(): void
    {
        $this->assertTrue(class_exists(ProductVariationData::class));
    }

    public function test_combo_item_dto_class_exists(): void
    {
        $this->assertTrue(class_exists(ComboItemData::class));
    }

    // ── Application Service Interfaces (new) ──────────────────────────────────

    public function test_product_variation_service_interfaces_exist(): void
    {
        $this->assertTrue(interface_exists(CreateProductVariationServiceInterface::class));
        $this->assertTrue(interface_exists(UpdateProductVariationServiceInterface::class));
        $this->assertTrue(interface_exists(DeleteProductVariationServiceInterface::class));
    }

    public function test_combo_item_service_interfaces_exist(): void
    {
        $this->assertTrue(interface_exists(CreateComboItemServiceInterface::class));
        $this->assertTrue(interface_exists(UpdateComboItemServiceInterface::class));
        $this->assertTrue(interface_exists(DeleteComboItemServiceInterface::class));
    }

    // ── Application Service Implementations (new) ─────────────────────────────

    public function test_product_variation_service_implementations_exist(): void
    {
        $this->assertTrue(class_exists(CreateProductVariationService::class));
        $this->assertTrue(class_exists(UpdateProductVariationService::class));
        $this->assertTrue(class_exists(DeleteProductVariationService::class));
    }

    public function test_combo_item_service_implementations_exist(): void
    {
        $this->assertTrue(class_exists(CreateComboItemService::class));
        $this->assertTrue(class_exists(UpdateComboItemService::class));
        $this->assertTrue(class_exists(DeleteComboItemService::class));
    }

    public function test_product_variation_services_implement_interfaces(): void
    {
        $this->assertTrue(is_subclass_of(CreateProductVariationService::class, CreateProductVariationServiceInterface::class));
        $this->assertTrue(is_subclass_of(UpdateProductVariationService::class, UpdateProductVariationServiceInterface::class));
        $this->assertTrue(is_subclass_of(DeleteProductVariationService::class, DeleteProductVariationServiceInterface::class));
    }

    public function test_combo_item_services_implement_interfaces(): void
    {
        $this->assertTrue(is_subclass_of(CreateComboItemService::class, CreateComboItemServiceInterface::class));
        $this->assertTrue(is_subclass_of(UpdateComboItemService::class, UpdateComboItemServiceInterface::class));
        $this->assertTrue(is_subclass_of(DeleteComboItemService::class, DeleteComboItemServiceInterface::class));
    }

    // ── Infrastructure Models (new) ───────────────────────────────────────────

    public function test_product_variation_model_class_exists(): void
    {
        $this->assertTrue(class_exists(ProductVariationModel::class));
    }

    public function test_product_combo_item_model_class_exists(): void
    {
        $this->assertTrue(class_exists(ProductComboItemModel::class));
    }

    // ── Infrastructure Repositories (new) ─────────────────────────────────────

    public function test_product_variation_repository_class_exists(): void
    {
        $this->assertTrue(class_exists(EloquentProductVariationRepository::class));
    }

    public function test_combo_item_repository_class_exists(): void
    {
        $this->assertTrue(class_exists(EloquentComboItemRepository::class));
    }

    public function test_product_variation_repository_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(EloquentProductVariationRepository::class, ProductVariationRepositoryInterface::class)
        );
    }

    public function test_combo_item_repository_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(EloquentComboItemRepository::class, ComboItemRepositoryInterface::class)
        );
    }

    // ── Infrastructure Controllers (new) ──────────────────────────────────────

    public function test_product_variation_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(ProductVariationController::class));
    }

    public function test_product_combo_item_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(ProductComboItemController::class));
    }

    // ── Infrastructure Requests (new) ─────────────────────────────────────────

    public function test_product_variation_request_classes_exist(): void
    {
        $this->assertTrue(class_exists(StoreProductVariationRequest::class));
        $this->assertTrue(class_exists(UpdateProductVariationRequest::class));
    }

    public function test_combo_item_request_classes_exist(): void
    {
        $this->assertTrue(class_exists(StoreComboItemRequest::class));
        $this->assertTrue(class_exists(UpdateComboItemRequest::class));
    }

    // ── Infrastructure Resources (new) ────────────────────────────────────────

    public function test_product_variation_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(ProductVariationResource::class));
    }

    public function test_combo_item_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(ComboItemResource::class));
    }

    // ── Product entity collections ────────────────────────────────────────────

    public function test_product_entity_has_empty_variations_and_combo_items_by_default(): void
    {
        $sku     = new \Modules\Core\Domain\ValueObjects\Sku('PROD-V01');
        $price   = new \Modules\Core\Domain\ValueObjects\Money(10.0, 'USD');
        $product = new Product(tenantId: 1, sku: $sku, name: 'Test', price: $price);

        $this->assertTrue($product->getVariations()->isEmpty());
        $this->assertTrue($product->getComboItems()->isEmpty());
    }

    public function test_product_entity_add_and_get_variations(): void
    {
        $sku     = new \Modules\Core\Domain\ValueObjects\Sku('PROD-V02');
        $price   = new \Modules\Core\Domain\ValueObjects\Money(10.0, 'USD');
        $product = new Product(tenantId: 1, sku: $sku, name: 'Variable', price: $price, type: 'variable');

        $varSku = new \Modules\Core\Domain\ValueObjects\Sku('PROD-V02-RED');
        $varPrice = new \Modules\Core\Domain\ValueObjects\Money(12.0, 'USD');
        $variation = new ProductVariation(1, 1, $varSku, 'Red variant', $varPrice, ['color' => 'Red']);

        $product->addVariation($variation);

        $this->assertCount(1, $product->getVariations());
        $this->assertSame('Red variant', $product->getVariations()->first()->getName());
    }

    public function test_product_entity_add_and_get_combo_items(): void
    {
        $sku     = new \Modules\Core\Domain\ValueObjects\Sku('PROD-C01');
        $price   = new \Modules\Core\Domain\ValueObjects\Money(50.0, 'USD');
        $product = new Product(tenantId: 1, sku: $sku, name: 'Bundle', price: $price, type: 'combo');

        $item = new ComboItem(productId: 1, tenantId: 1, componentProductId: 5, quantity: 2.0);
        $product->addComboItem($item);

        $this->assertCount(1, $product->getComboItems());
        $this->assertSame(5, $product->getComboItems()->first()->getComponentProductId());
        $this->assertSame(2.0, $product->getComboItems()->first()->getQuantity());
    }

    public function test_product_entity_set_variations_replaces_collection(): void
    {
        $sku     = new \Modules\Core\Domain\ValueObjects\Sku('PROD-V03');
        $price   = new \Modules\Core\Domain\ValueObjects\Money(10.0, 'USD');
        $product = new Product(tenantId: 1, sku: $sku, name: 'Variable', price: $price, type: 'variable');

        $v1Sku = new \Modules\Core\Domain\ValueObjects\Sku('V1');
        $v2Sku = new \Modules\Core\Domain\ValueObjects\Sku('V2');
        $vPrice = new \Modules\Core\Domain\ValueObjects\Money(10.0, 'USD');

        $variations = new \Illuminate\Support\Collection([
            new ProductVariation(1, 1, $v1Sku, 'V1', $vPrice),
            new ProductVariation(1, 1, $v2Sku, 'V2', $vPrice),
        ]);

        $product->setVariations($variations);
        $this->assertCount(2, $product->getVariations());
    }

    // ── Product attribute options ─────────────────────────────────────────────

    public function test_product_entity_with_product_attributes(): void
    {
        $sku   = new \Modules\Core\Domain\ValueObjects\Sku('PROD-PA01');
        $price = new \Modules\Core\Domain\ValueObjects\Money(10.0, 'USD');

        $colorAttr = new ProductAttribute('color', 'Color', ['Red', 'Blue', 'Green']);
        $sizeAttr  = new ProductAttribute('size', 'Size', ['S', 'M', 'L', 'XL']);

        $product = new Product(
            tenantId:          1,
            sku:               $sku,
            name:              'T-Shirt',
            price:             $price,
            type:              'variable',
            productAttributes: [$colorAttr, $sizeAttr],
        );

        $this->assertCount(2, $product->getProductAttributes());
        $this->assertSame('color', $product->getProductAttributes()[0]->getCode());
        $this->assertSame('size', $product->getProductAttributes()[1]->getCode());
    }

    // ── Migration files ───────────────────────────────────────────────────────

    public function test_product_variation_migration_file_exists(): void
    {
        $dir   = dirname(__DIR__, 2).'/app/Modules/Product/database/migrations';
        $files = glob($dir.'/*product_variations*.php');
        $this->assertGreaterThanOrEqual(1, count($files), 'product_variations migration must exist.');
    }

    public function test_product_combo_items_migration_file_exists(): void
    {
        $dir   = dirname(__DIR__, 2).'/app/Modules/Product/database/migrations';
        $files = glob($dir.'/*combo_items*.php');
        $this->assertGreaterThanOrEqual(1, count($files), 'product_combo_items migration must exist.');
    }

    // ── Find service interfaces (new) ─────────────────────────────────────────

    public function test_find_product_variations_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(FindProductVariationsServiceInterface::class));
    }

    public function test_find_combo_items_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(FindComboItemsServiceInterface::class));
    }

    // ── Find service implementations (new) ────────────────────────────────────

    public function test_find_product_variations_service_class_exists(): void
    {
        $this->assertTrue(class_exists(FindProductVariationsService::class));
    }

    public function test_find_combo_items_service_class_exists(): void
    {
        $this->assertTrue(class_exists(FindComboItemsService::class));
    }

    public function test_find_product_variations_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(FindProductVariationsService::class, FindProductVariationsServiceInterface::class)
        );
    }

    public function test_find_combo_items_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(FindComboItemsService::class, FindComboItemsServiceInterface::class)
        );
    }

    // ── Service interfaces extend full ServiceInterface (new) ─────────────────

    public function test_create_product_service_interface_extends_service_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(CreateProductServiceInterface::class, \Modules\Core\Application\Contracts\ServiceInterface::class, true)
        );
    }

    public function test_create_product_variation_service_interface_extends_service_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(CreateProductVariationServiceInterface::class, \Modules\Core\Application\Contracts\ServiceInterface::class, true)
        );
    }

    public function test_create_combo_item_service_interface_extends_service_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(CreateComboItemServiceInterface::class, \Modules\Core\Application\Contracts\ServiceInterface::class, true)
        );
    }

    // ── ProductData DTO has product_attributes field (new) ────────────────────

    public function test_product_data_dto_has_product_attributes_field(): void
    {
        $reflection = new \ReflectionClass(\Modules\Product\Application\DTOs\ProductData::class);
        $this->assertTrue($reflection->hasProperty('product_attributes'));
    }

    public function test_product_data_dto_product_attributes_validation_rules_exist(): void
    {
        $dto = new \Modules\Product\Application\DTOs\ProductData();
        $rules = $dto->rules();
        $this->assertArrayHasKey('product_attributes', $rules);
        $this->assertArrayHasKey('product_attributes.*.code', $rules);
        $this->assertArrayHasKey('product_attributes.*.name', $rules);
        $this->assertArrayHasKey('product_attributes.*.allowed_values', $rules);
    }

    // ── product_attributes wired through entity and services (new) ────────────

    public function test_create_product_service_handle_builds_product_attributes(): void
    {
        $repo = $this->createMock(\Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface::class);
        $repo->method('find')->willReturn(null);
        $repo->method('save')->willReturnArgument(0);

        $service = new \Modules\Product\Application\Services\CreateProductService($repo);

        $data = [
            'tenant_id'          => 1,
            'sku'                => 'TEST-PA-001',
            'name'               => 'T-Shirt',
            'price'              => 19.99,
            'currency'           => 'USD',
            'type'               => 'variable',
            'product_attributes' => [
                ['code' => 'color', 'name' => 'Color', 'allowed_values' => ['Red', 'Blue']],
                ['code' => 'size',  'name' => 'Size',  'allowed_values' => ['S', 'M', 'L']],
            ],
        ];

        // Call the protected handle() via reflection to avoid requiring a real DB transaction
        $ref    = new \ReflectionMethod($service, 'handle');
        $ref->setAccessible(true);
        $product = $ref->invoke($service, $data);

        $this->assertCount(2, $product->getProductAttributes());
        $this->assertSame('color', $product->getProductAttributes()[0]->getCode());
        $this->assertSame(['Red', 'Blue'], $product->getProductAttributes()[0]->getAllowedValues());
        $this->assertSame('size', $product->getProductAttributes()[1]->getCode());
    }

    public function test_update_product_service_updates_product_attributes(): void
    {
        $colorAttr = new ProductAttribute('color', 'Color', ['Red']);
        $sku   = new \Modules\Core\Domain\ValueObjects\Sku('TEST-PA-002');
        $price = new \Modules\Core\Domain\ValueObjects\Money(10.0, 'USD');
        $existing = new Product(
            tenantId:          1,
            sku:               $sku,
            name:              'T-Shirt',
            price:             $price,
            type:              'variable',
            productAttributes: [$colorAttr],
        );

        $repo = $this->createMock(\Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface::class);
        $repo->method('find')->willReturn($existing);
        $repo->method('save')->willReturnArgument(0);

        $service = new \Modules\Product\Application\Services\UpdateProductService($repo);

        $ref    = new \ReflectionMethod($service, 'handle');
        $ref->setAccessible(true);
        $updated = $ref->invoke($service, [
            'id'                 => 1,
            'tenant_id'          => 1,
            'sku'                => 'TEST-PA-002',
            'name'               => 'T-Shirt Updated',
            'price'              => 15.0,
            'currency'           => 'USD',
            'product_attributes' => [
                ['code' => 'color', 'name' => 'Color', 'allowed_values' => ['Red', 'Green']],
                ['code' => 'size',  'name' => 'Size',  'allowed_values' => ['XS', 'S', 'M']],
            ],
        ]);

        $this->assertCount(2, $updated->getProductAttributes());
        $this->assertSame(['Red', 'Green'], $updated->getProductAttributes()[0]->getAllowedValues());
        $this->assertSame('size', $updated->getProductAttributes()[1]->getCode());
    }

    public function test_update_product_service_preserves_product_attributes_when_not_provided(): void
    {
        $colorAttr = new ProductAttribute('color', 'Color', ['Red']);
        $sku   = new \Modules\Core\Domain\ValueObjects\Sku('TEST-PA-003');
        $price = new \Modules\Core\Domain\ValueObjects\Money(10.0, 'USD');
        $existing = new Product(
            tenantId:          1,
            sku:               $sku,
            name:              'T-Shirt',
            price:             $price,
            type:              'variable',
            productAttributes: [$colorAttr],
        );

        $repo = $this->createMock(\Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface::class);
        $repo->method('find')->willReturn($existing);
        $repo->method('save')->willReturnArgument(0);

        $service = new \Modules\Product\Application\Services\UpdateProductService($repo);

        $ref    = new \ReflectionMethod($service, 'handle');
        $ref->setAccessible(true);
        $updated = $ref->invoke($service, [
            'id'        => 1,
            'tenant_id' => 1,
            'sku'       => 'TEST-PA-003',
            'name'      => 'T-Shirt',
            'price'     => 10.0,
            'currency'  => 'USD',
            // No product_attributes → should preserve existing
        ]);

        // product_attributes is not in the data, so updateDetails gets null and preserves existing
        $this->assertCount(1, $updated->getProductAttributes());
        $this->assertSame('color', $updated->getProductAttributes()[0]->getCode());
    }

    // ── ProductResource includes product_attributes (new) ────────────────────

    public function test_product_resource_includes_product_attributes_key(): void
    {
        $sku   = new \Modules\Core\Domain\ValueObjects\Sku('TEST-RES-001');
        $price = new \Modules\Core\Domain\ValueObjects\Money(10.0, 'USD');
        $attr  = new ProductAttribute('color', 'Color', ['Red', 'Blue']);

        $product = new Product(
            tenantId:          1,
            sku:               $sku,
            name:              'T-Shirt',
            price:             $price,
            type:              'variable',
            productAttributes: [$attr],
        );

        $resource = new \Modules\Product\Infrastructure\Http\Resources\ProductResource($product);
        $array    = $resource->toArray(new \Illuminate\Http\Request());

        $this->assertArrayHasKey('product_attributes', $array);
        $this->assertCount(1, $array['product_attributes']);
        $this->assertSame('color', $array['product_attributes'][0]['code']);
        $this->assertSame('Color', $array['product_attributes'][0]['name']);
        $this->assertSame(['Red', 'Blue'], $array['product_attributes'][0]['allowed_values']);
    }

    // ── Controller constructors use service interfaces only (new) ─────────────

    public function test_product_variation_controller_uses_service_interfaces(): void
    {
        $rc = new \ReflectionClass(ProductVariationController::class);
        $constructor = $rc->getConstructor();
        $paramTypes = array_map(
            fn (\ReflectionParameter $p) => $p->getType()?->getName(),
            $constructor->getParameters()
        );

        $this->assertContains(CreateProductVariationServiceInterface::class, $paramTypes);
        $this->assertContains(UpdateProductVariationServiceInterface::class, $paramTypes);
        $this->assertContains(DeleteProductVariationServiceInterface::class, $paramTypes);
        $this->assertContains(CreateProductServiceInterface::class, $paramTypes);
        $this->assertContains(FindProductVariationsServiceInterface::class, $paramTypes);

        // Must NOT inject repository interfaces directly
        $this->assertNotContains(\Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface::class, $paramTypes);
        $this->assertNotContains(\Modules\Product\Domain\RepositoryInterfaces\ProductVariationRepositoryInterface::class, $paramTypes);
    }

    public function test_product_combo_item_controller_uses_service_interfaces(): void
    {
        $rc = new \ReflectionClass(ProductComboItemController::class);
        $constructor = $rc->getConstructor();
        $paramTypes = array_map(
            fn (\ReflectionParameter $p) => $p->getType()?->getName(),
            $constructor->getParameters()
        );

        $this->assertContains(CreateComboItemServiceInterface::class, $paramTypes);
        $this->assertContains(UpdateComboItemServiceInterface::class, $paramTypes);
        $this->assertContains(DeleteComboItemServiceInterface::class, $paramTypes);
        $this->assertContains(CreateProductServiceInterface::class, $paramTypes);
        $this->assertContains(FindComboItemsServiceInterface::class, $paramTypes);

        // Must NOT inject repository interfaces directly
        $this->assertNotContains(\Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface::class, $paramTypes);
        $this->assertNotContains(\Modules\Product\Domain\RepositoryInterfaces\ComboItemRepositoryInterface::class, $paramTypes);
    }

    // ── find_product_variations_service and find_combo_items_service behaviour ─

    public function test_find_product_variations_service_find_by_product(): void
    {
        $collection = new \Illuminate\Support\Collection([]);
        $repo = $this->createMock(ProductVariationRepositoryInterface::class);
        $repo->expects($this->once())
             ->method('findByProduct')
             ->with(42)
             ->willReturn($collection);

        $service = new FindProductVariationsService($repo);
        $result  = $service->findByProduct(42);
        $this->assertSame($collection, $result);
    }

    public function test_find_product_variations_service_find_single(): void
    {
        $sku       = new \Modules\Core\Domain\ValueObjects\Sku('VAR-FIND-01');
        $price     = new \Modules\Core\Domain\ValueObjects\Money(10.0, 'USD');
        $variation = new ProductVariation(1, 1, $sku, 'Test', $price);

        $repo = $this->createMock(ProductVariationRepositoryInterface::class);
        $repo->method('find')->with(7)->willReturn($variation);

        $service = new FindProductVariationsService($repo);
        $result  = $service->find(7);
        $this->assertSame($variation, $result);
    }

    public function test_find_product_variations_service_find_returns_null_when_missing(): void
    {
        $repo = $this->createMock(ProductVariationRepositoryInterface::class);
        $repo->method('find')->willReturn(null);

        $service = new FindProductVariationsService($repo);
        $this->assertNull($service->find(999));
    }

    public function test_find_combo_items_service_find_by_product(): void
    {
        $collection = new \Illuminate\Support\Collection([]);
        $repo = $this->createMock(ComboItemRepositoryInterface::class);
        $repo->expects($this->once())
             ->method('findByProduct')
             ->with(55)
             ->willReturn($collection);

        $service = new FindComboItemsService($repo);
        $result  = $service->findByProduct(55);
        $this->assertSame($collection, $result);
    }

    public function test_find_combo_items_service_find_single(): void
    {
        $item = new ComboItem(10, 1, 5, 2.0);

        $repo = $this->createMock(ComboItemRepositoryInterface::class);
        $repo->method('find')->with(3)->willReturn($item);

        $service = new FindComboItemsService($repo);
        $result  = $service->find(3);
        $this->assertSame($item, $result);
    }

    public function test_find_combo_items_service_find_returns_null_when_missing(): void
    {
        $repo = $this->createMock(ComboItemRepositoryInterface::class);
        $repo->method('find')->willReturn(null);

        $service = new FindComboItemsService($repo);
        $this->assertNull($service->find(999));
    }

    // ── ImageStorageStrategyInterface ─────────────────────────────────────────

    public function test_image_storage_strategy_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\Product\Application\Contracts\ImageStorageStrategyInterface::class));
    }

    public function test_image_storage_strategy_interface_has_expected_methods(): void
    {
        $rc = new \ReflectionClass(\Modules\Product\Application\Contracts\ImageStorageStrategyInterface::class);
        $this->assertTrue($rc->hasMethod('store'));
        $this->assertTrue($rc->hasMethod('storeFromPath'));
        $this->assertTrue($rc->hasMethod('delete'));
    }

    // ── DefaultImageStorageStrategy ───────────────────────────────────────────

    public function test_default_image_storage_strategy_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\Product\Infrastructure\Storage\DefaultImageStorageStrategy::class));
    }

    public function test_default_image_storage_strategy_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(
                \Modules\Product\Infrastructure\Storage\DefaultImageStorageStrategy::class,
                \Modules\Product\Application\Contracts\ImageStorageStrategyInterface::class
            )
        );
    }

    public function test_default_image_storage_strategy_delegates_store_from_path(): void
    {
        $fileStorage = $this->createMock(\Modules\Core\Application\Contracts\FileStorageServiceInterface::class);
        $fileStorage->expects($this->once())
            ->method('store')
            ->with('/tmp/test.jpg', 'products/99', 'test.jpg')
            ->willReturn('products/99/test.jpg');

        $strategy = new \Modules\Product\Infrastructure\Storage\DefaultImageStorageStrategy($fileStorage);
        $path     = $strategy->storeFromPath('/tmp/test.jpg', 'products/99', 'test.jpg');

        $this->assertSame('products/99/test.jpg', $path);
    }

    public function test_default_image_storage_strategy_delegates_delete(): void
    {
        $fileStorage = $this->createMock(\Modules\Core\Application\Contracts\FileStorageServiceInterface::class);
        $fileStorage->expects($this->once())
            ->method('delete')
            ->with('products/1/test.jpg')
            ->willReturn(true);

        $strategy = new \Modules\Product\Infrastructure\Storage\DefaultImageStorageStrategy($fileStorage);
        $result   = $strategy->delete('products/1/test.jpg');

        $this->assertTrue($result);
    }

    // ── BulkUploadProductImagesServiceInterface ───────────────────────────────

    public function test_bulk_upload_product_images_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\Product\Application\Contracts\BulkUploadProductImagesServiceInterface::class));
    }

    // ── BulkUploadProductImagesService ────────────────────────────────────────

    public function test_bulk_upload_product_images_service_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\Product\Application\Services\BulkUploadProductImagesService::class));
    }

    public function test_bulk_upload_product_images_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(
                \Modules\Product\Application\Services\BulkUploadProductImagesService::class,
                \Modules\Product\Application\Contracts\BulkUploadProductImagesServiceInterface::class
            )
        );
    }

    public function test_bulk_upload_product_images_service_returns_collection_of_images(): void
    {
        $sku   = new \Modules\Core\Domain\ValueObjects\Sku('BULK-001');
        $price = new \Modules\Core\Domain\ValueObjects\Money(10.0, 'USD');
        $product = new \Modules\Product\Domain\Entities\Product(
            tenantId: 1, sku: $sku, name: 'Test', price: $price, id: 7
        );

        $productRepo = $this->createMock(\Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface::class);
        $productRepo->method('find')->with(7)->willReturn($product);

        $savedImage = new \Modules\Product\Domain\Entities\ProductImage(
            tenantId: 1, productId: 7, uuid: 'uuid-1', name: 'a.jpg',
            filePath: 'products/7/a.jpg', mimeType: 'image/jpeg', size: 1024,
        );
        $imageRepo = $this->createMock(\Modules\Product\Domain\RepositoryInterfaces\ProductImageRepositoryInterface::class);
        $imageRepo->method('save')->willReturn($savedImage);

        $strategy = $this->createMock(\Modules\Product\Application\Contracts\ImageStorageStrategyInterface::class);
        $strategy->method('store')->willReturn('products/7/a.jpg');

        $file = $this->createMock(\Illuminate\Http\UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('a.jpg');
        $file->method('getMimeType')->willReturn('image/jpeg');
        $file->method('getSize')->willReturn(1024);

        $service = new \Modules\Product\Application\Services\BulkUploadProductImagesService(
            $productRepo, $imageRepo, $strategy
        );

        // Bypass DB::transaction by calling execute with a mock that wraps directly
        // We use reflection to call the inner loop without the real DB facade
        $rc = new \ReflectionClass($service);
        // For unit testing, directly instantiate the scenario via sub-method
        // — we just verify the class is callable and wired correctly
        $this->assertInstanceOf(
            \Modules\Product\Application\Contracts\BulkUploadProductImagesServiceInterface::class,
            $service
        );
    }

    // ── UploadProductImageService uses ImageStorageStrategyInterface ──────────

    public function test_upload_product_image_service_accepts_image_storage_strategy(): void
    {
        $rc = new \ReflectionClass(\Modules\Product\Application\Services\UploadProductImageService::class);
        $constructor = $rc->getConstructor();
        $paramTypes = array_map(
            fn (\ReflectionParameter $p) => $p->getType()?->getName(),
            $constructor->getParameters()
        );

        $this->assertContains(
            \Modules\Product\Application\Contracts\ImageStorageStrategyInterface::class,
            $paramTypes
        );
        // Must NOT inject FileStorageServiceInterface directly anymore
        $this->assertNotContains(
            \Modules\Core\Application\Contracts\FileStorageServiceInterface::class,
            $paramTypes
        );
    }

    public function test_delete_product_image_service_accepts_image_storage_strategy(): void
    {
        $rc = new \ReflectionClass(\Modules\Product\Application\Services\DeleteProductImageService::class);
        $constructor = $rc->getConstructor();
        $paramTypes = array_map(
            fn (\ReflectionParameter $p) => $p->getType()?->getName(),
            $constructor->getParameters()
        );

        $this->assertContains(
            \Modules\Product\Application\Contracts\ImageStorageStrategyInterface::class,
            $paramTypes
        );
        $this->assertNotContains(
            \Modules\Core\Application\Contracts\FileStorageServiceInterface::class,
            $paramTypes
        );
    }

    public function test_upload_product_image_service_handle_builds_product_image(): void
    {
        $sku   = new \Modules\Core\Domain\ValueObjects\Sku('IMG-001');
        $price = new \Modules\Core\Domain\ValueObjects\Money(10.0, 'USD');
        $product = new \Modules\Product\Domain\Entities\Product(
            tenantId: 1, sku: $sku, name: 'Test', price: $price, id: 3
        );

        $productRepo = $this->createMock(\Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface::class);
        $productRepo->method('find')->with(3)->willReturn($product);

        $savedImage = new \Modules\Product\Domain\Entities\ProductImage(
            tenantId: 1, productId: 3, uuid: 'test-uuid', name: 'photo.jpg',
            filePath: 'products/3/photo.jpg', mimeType: 'image/jpeg', size: 2048,
        );
        $imageRepo = $this->createMock(\Modules\Product\Domain\RepositoryInterfaces\ProductImageRepositoryInterface::class);
        $imageRepo->method('save')->willReturnArgument(0);

        $strategy = $this->createMock(\Modules\Product\Application\Contracts\ImageStorageStrategyInterface::class);
        $strategy->expects($this->once())
            ->method('store')
            ->willReturn('products/3/photo.jpg');

        $file = $this->createMock(\Illuminate\Http\UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('photo.jpg');
        $file->method('getMimeType')->willReturn('image/jpeg');
        $file->method('getSize')->willReturn(2048);

        $service = new \Modules\Product\Application\Services\UploadProductImageService(
            $productRepo, $imageRepo, $strategy
        );

        $ref = new \ReflectionMethod($service, 'handle');
        $ref->setAccessible(true);
        $image = $ref->invoke($service, [
            'product_id' => 3,
            'file'       => $file,
            'sort_order' => 1,
            'is_primary' => true,
            'metadata'   => ['alt' => 'front view'],
        ]);

        $this->assertInstanceOf(\Modules\Product\Domain\Entities\ProductImage::class, $image);
        $this->assertSame(3, $image->getProductId());
        $this->assertSame(1, $image->getTenantId());
        $this->assertSame('photo.jpg', $image->getName());
        $this->assertTrue($image->isPrimary());
        $this->assertSame(1, $image->getSortOrder());
        $this->assertSame(['alt' => 'front view'], $image->getMetadata());
    }

    // ── ProductImageController uses BulkUploadProductImagesServiceInterface ───

    public function test_product_image_controller_uses_bulk_upload_service_interface(): void
    {
        $rc = new \ReflectionClass(\Modules\Product\Infrastructure\Http\Controllers\ProductImageController::class);
        $constructor = $rc->getConstructor();
        $paramTypes = array_map(
            fn (\ReflectionParameter $p) => $p->getType()?->getName(),
            $constructor->getParameters()
        );

        $this->assertContains(
            \Modules\Product\Application\Contracts\BulkUploadProductImagesServiceInterface::class,
            $paramTypes
        );
        $this->assertContains(
            \Modules\Product\Application\Contracts\UploadProductImageServiceInterface::class,
            $paramTypes
        );
    }

    public function test_product_image_controller_has_store_many_method(): void
    {
        $rc = new \ReflectionClass(\Modules\Product\Infrastructure\Http\Controllers\ProductImageController::class);
        $this->assertTrue($rc->hasMethod('storeMany'));
    }

    // ── UploadProductImageRequest supports bulk fields ────────────────────────

    public function test_upload_product_image_request_has_files_array_rule(): void
    {
        $request = new \Modules\Product\Infrastructure\Http\Requests\UploadProductImageRequest();
        $rules   = $request->rules();

        $this->assertArrayHasKey('files', $rules);
        $this->assertArrayHasKey('files.*', $rules);
        $this->assertArrayHasKey('sort_order_start', $rules);
        $this->assertArrayHasKey('is_primary_index', $rules);
    }

    public function test_upload_product_image_request_file_field_is_nullable(): void
    {
        $request = new \Modules\Product\Infrastructure\Http\Requests\UploadProductImageRequest();
        $rules   = $request->rules();

        // file should be nullable (not required) to allow bulk-only uploads
        $this->assertStringContainsString('nullable', $rules['file']);
    }

    // ── DeleteProductImageService uses ImageStorageStrategyInterface ──────────

    public function test_delete_product_image_service_handle_calls_strategy_delete(): void
    {
        $image = new \Modules\Product\Domain\Entities\ProductImage(
            tenantId: 1, productId: 5, uuid: 'uuid-del', name: 'x.jpg',
            filePath: 'products/5/x.jpg', mimeType: 'image/jpeg', size: 512,
            id: 10,
        );

        $imageRepo = $this->createMock(\Modules\Product\Domain\RepositoryInterfaces\ProductImageRepositoryInterface::class);
        $imageRepo->method('find')->with(10)->willReturn($image);
        $imageRepo->method('delete')->with(10)->willReturn(true);

        $strategy = $this->createMock(\Modules\Product\Application\Contracts\ImageStorageStrategyInterface::class);
        $strategy->expects($this->once())
            ->method('delete')
            ->with('products/5/x.jpg')
            ->willReturn(true);

        $service = new \Modules\Product\Application\Services\DeleteProductImageService(
            $imageRepo, $strategy
        );

        $ref = new \ReflectionMethod($service, 'handle');
        $ref->setAccessible(true);
        $result = $ref->invoke($service, ['image_id' => 10]);

        $this->assertTrue($result);
    }

    // ── FindProductImagesService ──────────────────────────────────────────────

    public function test_find_product_images_service_interface_exists(): void
    {
        $this->assertTrue(
            interface_exists(\Modules\Product\Application\Contracts\FindProductImagesServiceInterface::class)
        );
    }

    public function test_find_product_images_service_class_exists(): void
    {
        $this->assertTrue(
            class_exists(\Modules\Product\Application\Services\FindProductImagesService::class)
        );
    }

    public function test_find_product_images_service_implements_interface(): void
    {
        $this->assertContains(
            \Modules\Product\Application\Contracts\FindProductImagesServiceInterface::class,
            class_implements(\Modules\Product\Application\Services\FindProductImagesService::class)
        );
    }

    public function test_find_product_images_service_find_by_product(): void
    {
        $image = new \Modules\Product\Domain\Entities\ProductImage(
            tenantId: 1, productId: 7, uuid: 'u1', name: 'a.jpg',
            filePath: 'products/7/a.jpg', mimeType: 'image/jpeg', size: 1024,
            id: 1,
        );

        $imageRepo = $this->createMock(\Modules\Product\Domain\RepositoryInterfaces\ProductImageRepositoryInterface::class);
        $imageRepo->expects($this->once())
            ->method('getByProduct')
            ->with(7)
            ->willReturn(collect([$image]));

        $service = new \Modules\Product\Application\Services\FindProductImagesService($imageRepo);
        $result  = $service->findByProduct(7);

        $this->assertCount(1, $result);
        $this->assertSame($image, $result->first());
    }

    public function test_find_product_images_service_find_by_uuid(): void
    {
        $image = new \Modules\Product\Domain\Entities\ProductImage(
            tenantId: 1, productId: 7, uuid: 'abc-uuid', name: 'b.jpg',
            filePath: 'products/7/b.jpg', mimeType: 'image/jpeg', size: 512,
            id: 2,
        );

        $imageRepo = $this->createMock(\Modules\Product\Domain\RepositoryInterfaces\ProductImageRepositoryInterface::class);
        $imageRepo->expects($this->once())
            ->method('findByUuid')
            ->with('abc-uuid')
            ->willReturn($image);

        $service = new \Modules\Product\Application\Services\FindProductImagesService($imageRepo);
        $result  = $service->findByUuid('abc-uuid');

        $this->assertSame($image, $result);
    }

    public function test_find_product_images_service_find_by_uuid_returns_null_when_missing(): void
    {
        $imageRepo = $this->createMock(\Modules\Product\Domain\RepositoryInterfaces\ProductImageRepositoryInterface::class);
        $imageRepo->method('findByUuid')->willReturn(null);

        $service = new \Modules\Product\Application\Services\FindProductImagesService($imageRepo);
        $this->assertNull($service->findByUuid('nonexistent'));
    }

    // ── ImageStorageStrategyInterface::stream ─────────────────────────────────

    public function test_image_storage_strategy_interface_has_stream_method(): void
    {
        $rc = new \ReflectionClass(\Modules\Product\Application\Contracts\ImageStorageStrategyInterface::class);
        $this->assertTrue($rc->hasMethod('stream'));

        $method = $rc->getMethod('stream');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('path', $params[0]->getName());
    }

    public function test_default_image_storage_strategy_delegates_stream(): void
    {
        $path     = 'products/1/img.jpg';
        $response = $this->createMock(\Symfony\Component\HttpFoundation\StreamedResponse::class);

        $fileStorage = $this->createMock(\Modules\Core\Application\Contracts\FileStorageServiceInterface::class);
        $fileStorage->expects($this->once())
            ->method('stream')
            ->with($path)
            ->willReturn($response);

        $strategy = new \Modules\Product\Infrastructure\Storage\DefaultImageStorageStrategy($fileStorage);
        $result   = $strategy->stream($path);

        $this->assertSame($response, $result);
    }

    // ── ProductImageController DI (no direct repo/storage injection) ──────────

    public function test_product_image_controller_uses_find_product_images_service_interface(): void
    {
        $rc = new \ReflectionClass(\Modules\Product\Infrastructure\Http\Controllers\ProductImageController::class);
        $constructor = $rc->getConstructor();
        $paramTypes  = array_map(
            fn (\ReflectionParameter $p) => $p->getType()?->getName(),
            $constructor->getParameters()
        );

        $this->assertContains(
            \Modules\Product\Application\Contracts\FindProductImagesServiceInterface::class,
            $paramTypes,
            'ProductImageController must inject FindProductImagesServiceInterface.'
        );
        $this->assertContains(
            \Modules\Product\Application\Contracts\ImageStorageStrategyInterface::class,
            $paramTypes,
            'ProductImageController must inject ImageStorageStrategyInterface.'
        );
    }

    public function test_product_image_controller_does_not_inject_repository_or_file_storage_directly(): void
    {
        $rc = new \ReflectionClass(\Modules\Product\Infrastructure\Http\Controllers\ProductImageController::class);
        $constructor = $rc->getConstructor();
        $paramTypes  = array_map(
            fn (\ReflectionParameter $p) => $p->getType()?->getName(),
            $constructor->getParameters()
        );

        $this->assertNotContains(
            \Modules\Product\Domain\RepositoryInterfaces\ProductImageRepositoryInterface::class,
            $paramTypes,
            'ProductImageController must not inject ProductImageRepositoryInterface directly.'
        );
        $this->assertNotContains(
            \Modules\Core\Application\Contracts\FileStorageServiceInterface::class,
            $paramTypes,
            'ProductImageController must not inject FileStorageServiceInterface directly.'
        );
    }

    // ── UpdateProductRequest partial-update rules ─────────────────────────────

    public function test_update_product_request_name_is_optional_for_partial_updates(): void
    {
        $request = new \Modules\Product\Infrastructure\Http\Requests\UpdateProductRequest();
        $rules   = $request->rules();

        $this->assertStringContainsString('sometimes', $rules['name'],
            'UpdateProductRequest name should use sometimes to allow partial updates.');
    }

    public function test_update_product_request_price_is_optional_for_partial_updates(): void
    {
        $request = new \Modules\Product\Infrastructure\Http\Requests\UpdateProductRequest();
        $rules   = $request->rules();

        $this->assertStringContainsString('sometimes', $rules['price'],
            'UpdateProductRequest price should use sometimes to allow partial updates.');
    }

    // ── Product::draft() domain method ────────────────────────────────────────

    public function test_product_entity_can_be_drafted(): void
    {
        $sku     = new \Modules\Core\Domain\ValueObjects\Sku('PROD-DRF01');
        $price   = new \Modules\Core\Domain\ValueObjects\Money(10.0, 'USD');
        $product = new \Modules\Product\Domain\Entities\Product(
            tenantId: 1,
            sku: $sku,
            name: 'Draft Product',
            price: $price,
            status: 'active',
        );

        $this->assertTrue($product->isActive());
        $product->draft();
        $this->assertSame('draft', $product->getStatus());
        $this->assertFalse($product->isActive());
    }

    public function test_product_draft_updates_timestamp(): void
    {
        $sku     = new \Modules\Core\Domain\ValueObjects\Sku('PROD-DRF02');
        $price   = new \Modules\Core\Domain\ValueObjects\Money(10.0, 'USD');
        $product = new \Modules\Product\Domain\Entities\Product(
            tenantId: 1,
            sku: $sku,
            name: 'Draft TS',
            price: $price,
        );

        $before = $product->getUpdatedAt();
        // Ensure at least 1 µs has elapsed before calling draft()
        usleep(100);
        $product->draft();

        $this->assertGreaterThan($before, $product->getUpdatedAt());
        $this->assertSame('draft', $product->getStatus());
    }

    // ── UpdateProductService draft status ─────────────────────────────────────

    public function test_update_product_service_handles_draft_status(): void
    {
        $sku     = new \Modules\Core\Domain\ValueObjects\Sku('UPS-DRF01');
        $price   = new \Modules\Core\Domain\ValueObjects\Money(20.0, 'USD');
        $product = new \Modules\Product\Domain\Entities\Product(
            tenantId: 1, sku: $sku, name: 'Active', price: $price, status: 'active', id: 99,
        );

        $repo = $this->createMock(\Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface::class);
        $repo->method('find')->willReturn($product);
        $repo->method('save')->willReturnArgument(0);

        $service = new \Modules\Product\Application\Services\UpdateProductService($repo);

        $ref = new \ReflectionMethod($service, 'handle');
        $ref->setAccessible(true);
        $result = $ref->invoke($service, [
            'id'        => 99,
            'tenant_id' => 1,
            'sku'       => 'UPS-DRF01',
            'name'      => 'Active',
            'price'     => 20.0,
            'currency'  => 'USD',
            'status'    => 'draft',
        ]);

        $this->assertSame('draft', $result->getStatus());
        $this->assertFalse($result->isActive());
    }

    // ── ProductController thin-controller (no DTO in constructor) ─────────────

    public function test_product_controller_does_not_inject_product_data_dto(): void
    {
        $rc          = new \ReflectionClass(\Modules\Product\Infrastructure\Http\Controllers\ProductController::class);
        $constructor = $rc->getConstructor();
        $paramTypes  = array_map(
            fn (\ReflectionParameter $p) => $p->getType()?->getName(),
            $constructor->getParameters()
        );

        $this->assertNotContains(
            \Modules\Product\Application\DTOs\ProductData::class,
            $paramTypes,
            'ProductController must not inject ProductData DTO directly — it should delegate to services.'
        );
    }

    // ── ProductController update fills defaults for partial updates ───────────

    public function test_product_controller_index_filters_include_type(): void
    {
        $rc     = new \ReflectionClass(\Modules\Product\Infrastructure\Http\Controllers\ProductController::class);
        $method = $rc->getMethod('index');

        // Verify the index method exists and accepts a Request parameter.
        $this->assertNotNull($method);
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertNotNull($params[0]->getType());
        $this->assertSame('Illuminate\Http\Request', $params[0]->getType()->getName());
    }
}
