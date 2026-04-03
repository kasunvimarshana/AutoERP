<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

// ── Value Objects ─────────────────────────────────────────────────────────────
use Modules\UoM\Domain\ValueObjects\UomType;

// ── Domain Entities ───────────────────────────────────────────────────────────
use Modules\UoM\Domain\Entities\UomCategory;
use Modules\UoM\Domain\Entities\UnitOfMeasure;
use Modules\UoM\Domain\Entities\UomConversion;
use Modules\UoM\Domain\Entities\ProductUomSetting;

// ── Domain Events ─────────────────────────────────────────────────────────────
use Modules\UoM\Domain\Events\UomCategoryCreated;
use Modules\UoM\Domain\Events\UomCategoryUpdated;
use Modules\UoM\Domain\Events\UomCategoryDeleted;
use Modules\UoM\Domain\Events\UnitOfMeasureCreated;
use Modules\UoM\Domain\Events\UnitOfMeasureUpdated;
use Modules\UoM\Domain\Events\UnitOfMeasureDeleted;
use Modules\UoM\Domain\Events\UomConversionCreated;
use Modules\UoM\Domain\Events\UomConversionUpdated;
use Modules\UoM\Domain\Events\UomConversionDeleted;
use Modules\UoM\Domain\Events\ProductUomSettingCreated;
use Modules\UoM\Domain\Events\ProductUomSettingUpdated;
use Modules\UoM\Domain\Events\ProductUomSettingDeleted;

// ── Domain Exceptions ─────────────────────────────────────────────────────────
use Modules\UoM\Domain\Exceptions\UomCategoryNotFoundException;
use Modules\UoM\Domain\Exceptions\UnitOfMeasureNotFoundException;
use Modules\UoM\Domain\Exceptions\UomConversionNotFoundException;
use Modules\UoM\Domain\Exceptions\ProductUomSettingNotFoundException;

// ── Repository Interfaces ─────────────────────────────────────────────────────
use Modules\UoM\Domain\RepositoryInterfaces\UomCategoryRepositoryInterface;
use Modules\UoM\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;
use Modules\UoM\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;
use Modules\UoM\Domain\RepositoryInterfaces\ProductUomSettingRepositoryInterface;

// ── Application DTOs ─────────────────────────────────────────────────────────
use Modules\UoM\Application\DTOs\UomCategoryData;
use Modules\UoM\Application\DTOs\UpdateUomCategoryData;
use Modules\UoM\Application\DTOs\UnitOfMeasureData;
use Modules\UoM\Application\DTOs\UpdateUnitOfMeasureData;
use Modules\UoM\Application\DTOs\UomConversionData;
use Modules\UoM\Application\DTOs\UpdateUomConversionData;
use Modules\UoM\Application\DTOs\ProductUomSettingData;
use Modules\UoM\Application\DTOs\UpdateProductUomSettingData;

// ── Application Service Contracts ─────────────────────────────────────────────
use Modules\UoM\Application\Contracts\CreateUomCategoryServiceInterface;
use Modules\UoM\Application\Contracts\FindUomCategoryServiceInterface;
use Modules\UoM\Application\Contracts\UpdateUomCategoryServiceInterface;
use Modules\UoM\Application\Contracts\DeleteUomCategoryServiceInterface;
use Modules\UoM\Application\Contracts\CreateUnitOfMeasureServiceInterface;
use Modules\UoM\Application\Contracts\FindUnitOfMeasureServiceInterface;
use Modules\UoM\Application\Contracts\UpdateUnitOfMeasureServiceInterface;
use Modules\UoM\Application\Contracts\DeleteUnitOfMeasureServiceInterface;
use Modules\UoM\Application\Contracts\CreateUomConversionServiceInterface;
use Modules\UoM\Application\Contracts\FindUomConversionServiceInterface;
use Modules\UoM\Application\Contracts\UpdateUomConversionServiceInterface;
use Modules\UoM\Application\Contracts\DeleteUomConversionServiceInterface;
use Modules\UoM\Application\Contracts\CreateProductUomSettingServiceInterface;
use Modules\UoM\Application\Contracts\FindProductUomSettingServiceInterface;
use Modules\UoM\Application\Contracts\UpdateProductUomSettingServiceInterface;
use Modules\UoM\Application\Contracts\DeleteProductUomSettingServiceInterface;

// ── Application Services (Concrete) ──────────────────────────────────────────
use Modules\UoM\Application\Services\CreateUomCategoryService;
use Modules\UoM\Application\Services\FindUomCategoryService;
use Modules\UoM\Application\Services\UpdateUomCategoryService;
use Modules\UoM\Application\Services\DeleteUomCategoryService;
use Modules\UoM\Application\Services\CreateUnitOfMeasureService;
use Modules\UoM\Application\Services\FindUnitOfMeasureService;
use Modules\UoM\Application\Services\UpdateUnitOfMeasureService;
use Modules\UoM\Application\Services\DeleteUnitOfMeasureService;
use Modules\UoM\Application\Services\CreateUomConversionService;
use Modules\UoM\Application\Services\FindUomConversionService;
use Modules\UoM\Application\Services\UpdateUomConversionService;
use Modules\UoM\Application\Services\DeleteUomConversionService;
use Modules\UoM\Application\Services\CreateProductUomSettingService;
use Modules\UoM\Application\Services\FindProductUomSettingService;
use Modules\UoM\Application\Services\UpdateProductUomSettingService;
use Modules\UoM\Application\Services\DeleteProductUomSettingService;

// ── Core base classes ─────────────────────────────────────────────────────────
use Modules\Core\Application\Contracts\ReadServiceInterface;
use Modules\Core\Application\Contracts\WriteServiceInterface;
use Modules\Core\Application\DTOs\BaseDto;
use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Events\BaseEvent;

// ── Infrastructure Models ─────────────────────────────────────────────────────
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\UomCategoryModel;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\UomConversionModel;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\ProductUomSettingModel;

// ── Infrastructure Repositories ───────────────────────────────────────────────
use Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories\EloquentUomCategoryRepository;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories\EloquentUnitOfMeasureRepository;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories\EloquentUomConversionRepository;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductUomSettingRepository;

// ── Infrastructure Controllers ────────────────────────────────────────────────
use Modules\UoM\Infrastructure\Http\Controllers\UomCategoryController;
use Modules\UoM\Infrastructure\Http\Controllers\UnitOfMeasureController;
use Modules\UoM\Infrastructure\Http\Controllers\UomConversionController;
use Modules\UoM\Infrastructure\Http\Controllers\ProductUomSettingController;

// ── Infrastructure Requests ───────────────────────────────────────────────────
use Modules\UoM\Infrastructure\Http\Requests\StoreUomCategoryRequest;
use Modules\UoM\Infrastructure\Http\Requests\UpdateUomCategoryRequest;
use Modules\UoM\Infrastructure\Http\Requests\StoreUnitOfMeasureRequest;
use Modules\UoM\Infrastructure\Http\Requests\UpdateUnitOfMeasureRequest;
use Modules\UoM\Infrastructure\Http\Requests\StoreUomConversionRequest;
use Modules\UoM\Infrastructure\Http\Requests\UpdateUomConversionRequest;
use Modules\UoM\Infrastructure\Http\Requests\StoreProductUomSettingRequest;
use Modules\UoM\Infrastructure\Http\Requests\UpdateProductUomSettingRequest;

// ── Infrastructure Resources ──────────────────────────────────────────────────
use Modules\UoM\Infrastructure\Http\Resources\UomCategoryResource;
use Modules\UoM\Infrastructure\Http\Resources\UomCategoryCollection;
use Modules\UoM\Infrastructure\Http\Resources\UnitOfMeasureResource;
use Modules\UoM\Infrastructure\Http\Resources\UnitOfMeasureCollection;
use Modules\UoM\Infrastructure\Http\Resources\UomConversionResource;
use Modules\UoM\Infrastructure\Http\Resources\UomConversionCollection;
use Modules\UoM\Infrastructure\Http\Resources\ProductUomSettingResource;
use Modules\UoM\Infrastructure\Http\Resources\ProductUomSettingCollection;

// ── Service Provider ──────────────────────────────────────────────────────────
use Modules\UoM\Infrastructure\Providers\UomServiceProvider;

/**
 * UomModuleTest
 *
 * Validates the optional multi-unit-of-measure (UoM) module which provides
 * base, purchase, sales, and inventory unit support with flexible conversion
 * factors — a core requirement of the enterprise WIMS platform.
 */
class UomModuleTest extends TestCase
{
    // ========================================================================
    // UOM TYPE — VALUE OBJECT
    // ========================================================================

    public function test_uom_type_constants(): void
    {
        $this->assertSame('base',      UomType::BASE);
        $this->assertSame('purchase',  UomType::PURCHASE);
        $this->assertSame('sales',     UomType::SALES);
        $this->assertSame('inventory', UomType::INVENTORY);
    }

    public function test_uom_type_valid_types_list(): void
    {
        $this->assertContains(UomType::BASE,      UomType::VALID_TYPES);
        $this->assertContains(UomType::PURCHASE,  UomType::VALID_TYPES);
        $this->assertContains(UomType::SALES,     UomType::VALID_TYPES);
        $this->assertContains(UomType::INVENTORY, UomType::VALID_TYPES);
        $this->assertCount(4, UomType::VALID_TYPES);
    }

    public function test_uom_type_constructor_valid(): void
    {
        $type = new UomType(UomType::BASE);
        $this->assertSame('base', $type->value());
    }

    public function test_uom_type_constructor_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new UomType('invalid_type');
    }

    public function test_uom_type_is_base(): void
    {
        $type = new UomType(UomType::BASE);
        $this->assertTrue($type->isBase());
        $this->assertFalse($type->isPurchase());
        $this->assertFalse($type->isSales());
        $this->assertFalse($type->isInventory());
    }

    public function test_uom_type_is_purchase(): void
    {
        $type = new UomType(UomType::PURCHASE);
        $this->assertFalse($type->isBase());
        $this->assertTrue($type->isPurchase());
        $this->assertFalse($type->isSales());
        $this->assertFalse($type->isInventory());
    }

    public function test_uom_type_is_sales(): void
    {
        $type = new UomType(UomType::SALES);
        $this->assertFalse($type->isBase());
        $this->assertFalse($type->isPurchase());
        $this->assertTrue($type->isSales());
        $this->assertFalse($type->isInventory());
    }

    public function test_uom_type_is_inventory(): void
    {
        $type = new UomType(UomType::INVENTORY);
        $this->assertFalse($type->isBase());
        $this->assertFalse($type->isPurchase());
        $this->assertFalse($type->isSales());
        $this->assertTrue($type->isInventory());
    }

    public function test_uom_type_equals(): void
    {
        $a = new UomType(UomType::BASE);
        $b = new UomType(UomType::BASE);
        $c = new UomType(UomType::SALES);
        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_uom_type_to_string(): void
    {
        $type = new UomType(UomType::PURCHASE);
        $this->assertSame('purchase', (string) $type);
    }

    // ========================================================================
    // UOM CATEGORY — DOMAIN ENTITY
    // ========================================================================

    public function test_uom_category_entity_construction(): void
    {
        $cat = $this->makeCategory();
        $this->assertSame(1, $cat->getTenantId());
        $this->assertSame('Weight', $cat->getName());
        $this->assertSame('WEIGHT', $cat->getCode());
        $this->assertSame('Weight measurements', $cat->getDescription());
        $this->assertTrue($cat->isActive());
        $this->assertNull($cat->getId());
    }

    public function test_uom_category_update_details(): void
    {
        $cat = $this->makeCategory();
        $cat->updateDetails('Volume', 'VOLUME', 'Volume measurements', false);
        $this->assertSame('Volume', $cat->getName());
        $this->assertSame('VOLUME', $cat->getCode());
        $this->assertSame('Volume measurements', $cat->getDescription());
        $this->assertFalse($cat->isActive());
    }

    public function test_uom_category_activate_deactivate(): void
    {
        $cat = $this->makeCategory();
        $cat->deactivate();
        $this->assertFalse($cat->isActive());
        $cat->activate();
        $this->assertTrue($cat->isActive());
    }

    public function test_uom_category_nullable_description(): void
    {
        $cat = new UomCategory(1, 'Length', 'LENGTH');
        $this->assertNull($cat->getDescription());
    }

    public function test_uom_category_timestamps(): void
    {
        $cat = $this->makeCategory();
        $this->assertInstanceOf(\DateTimeInterface::class, $cat->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $cat->getUpdatedAt());
    }

    public function test_uom_category_with_id(): void
    {
        $cat = new UomCategory(1, 'Weight', 'WEIGHT', null, true, 99);
        $this->assertSame(99, $cat->getId());
    }

    // ========================================================================
    // UNIT OF MEASURE — DOMAIN ENTITY
    // ========================================================================

    public function test_unit_of_measure_entity_construction(): void
    {
        $uom = $this->makeUnit();
        $this->assertSame(1, $uom->getTenantId());
        $this->assertSame(5, $uom->getUomCategoryId());
        $this->assertSame('Kilogram', $uom->getName());
        $this->assertSame('KG', $uom->getCode());
        $this->assertSame('kg', $uom->getSymbol());
        $this->assertTrue($uom->isBaseUnit());
        $this->assertSame(1.0, $uom->getFactor());
        $this->assertTrue($uom->isActive());
    }

    public function test_unit_of_measure_update_details(): void
    {
        $uom = $this->makeUnit();
        $uom->updateDetails(5, 'Gram', 'G', 'g', false, 0.001, 'Gram unit', true);
        $this->assertSame('Gram', $uom->getName());
        $this->assertSame('G', $uom->getCode());
        $this->assertSame('g', $uom->getSymbol());
        $this->assertFalse($uom->isBaseUnit());
        $this->assertSame(0.001, $uom->getFactor());
        $this->assertSame('Gram unit', $uom->getDescription());
    }

    public function test_unit_of_measure_activate_deactivate(): void
    {
        $uom = $this->makeUnit();
        $uom->deactivate();
        $this->assertFalse($uom->isActive());
        $uom->activate();
        $this->assertTrue($uom->isActive());
    }

    public function test_unit_of_measure_nullable_description(): void
    {
        $uom = new UnitOfMeasure(1, 5, 'Liter', 'L', 'l');
        $this->assertNull($uom->getDescription());
    }

    public function test_unit_of_measure_non_base_unit_with_factor(): void
    {
        $uom = new UnitOfMeasure(1, 5, 'Milligram', 'MG', 'mg', false, 0.000001);
        $this->assertFalse($uom->isBaseUnit());
        $this->assertSame(0.000001, $uom->getFactor());
    }

    public function test_unit_of_measure_timestamps(): void
    {
        $uom = $this->makeUnit();
        $this->assertInstanceOf(\DateTimeInterface::class, $uom->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $uom->getUpdatedAt());
    }

    // ========================================================================
    // UOM CONVERSION — DOMAIN ENTITY
    // ========================================================================

    public function test_uom_conversion_entity_construction(): void
    {
        $conv = $this->makeConversion();
        $this->assertSame(1, $conv->getTenantId());
        $this->assertSame(10, $conv->getFromUomId());
        $this->assertSame(11, $conv->getToUomId());
        $this->assertSame(1000.0, $conv->getFactor());
        $this->assertTrue($conv->isActive());
    }

    public function test_uom_conversion_convert(): void
    {
        $conv = $this->makeConversion();
        $result = $conv->convert(2.5);
        $this->assertSame(2500.0, $result);
    }

    public function test_uom_conversion_update_factor(): void
    {
        $conv = $this->makeConversion();
        $conv->updateFactor(500.0);
        $this->assertSame(500.0, $conv->getFactor());
    }

    public function test_uom_conversion_activate_deactivate(): void
    {
        $conv = $this->makeConversion();
        $conv->deactivate();
        $this->assertFalse($conv->isActive());
        $conv->activate();
        $this->assertTrue($conv->isActive());
    }

    public function test_uom_conversion_convert_zero(): void
    {
        $conv = $this->makeConversion();
        $this->assertSame(0.0, $conv->convert(0.0));
    }

    public function test_uom_conversion_timestamps(): void
    {
        $conv = $this->makeConversion();
        $this->assertInstanceOf(\DateTimeInterface::class, $conv->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $conv->getUpdatedAt());
    }

    // ========================================================================
    // PRODUCT UOM SETTING — DOMAIN ENTITY
    // ========================================================================

    public function test_product_uom_setting_entity_construction(): void
    {
        $setting = $this->makeSetting();
        $this->assertSame(1, $setting->getTenantId());
        $this->assertSame(42, $setting->getProductId());
        $this->assertSame(10, $setting->getBaseUomId());
        $this->assertSame(11, $setting->getPurchaseUomId());
        $this->assertSame(12, $setting->getSalesUomId());
        $this->assertSame(10, $setting->getInventoryUomId());
        $this->assertSame(12.0, $setting->getPurchaseFactor());
        $this->assertSame(1.0, $setting->getSalesFactor());
        $this->assertSame(1.0, $setting->getInventoryFactor());
        $this->assertTrue($setting->isActive());
    }

    public function test_product_uom_setting_nullable_uom_ids(): void
    {
        $setting = new ProductUomSetting(1, 99);
        $this->assertNull($setting->getBaseUomId());
        $this->assertNull($setting->getPurchaseUomId());
        $this->assertNull($setting->getSalesUomId());
        $this->assertNull($setting->getInventoryUomId());
    }

    public function test_product_uom_setting_default_factors(): void
    {
        $setting = new ProductUomSetting(1, 99);
        $this->assertSame(1.0, $setting->getPurchaseFactor());
        $this->assertSame(1.0, $setting->getSalesFactor());
        $this->assertSame(1.0, $setting->getInventoryFactor());
    }

    public function test_product_uom_setting_update_details(): void
    {
        $setting = $this->makeSetting();
        $setting->updateDetails(20, 21, 22, 20, 24.0, 2.0, 1.0, false);
        $this->assertSame(20, $setting->getBaseUomId());
        $this->assertSame(21, $setting->getPurchaseUomId());
        $this->assertSame(22, $setting->getSalesUomId());
        $this->assertSame(20, $setting->getInventoryUomId());
        $this->assertSame(24.0, $setting->getPurchaseFactor());
        $this->assertSame(2.0, $setting->getSalesFactor());
        $this->assertSame(1.0, $setting->getInventoryFactor());
        $this->assertFalse($setting->isActive());
    }

    public function test_product_uom_setting_activate_deactivate(): void
    {
        $setting = $this->makeSetting();
        $setting->deactivate();
        $this->assertFalse($setting->isActive());
        $setting->activate();
        $this->assertTrue($setting->isActive());
    }

    public function test_product_uom_setting_timestamps(): void
    {
        $setting = $this->makeSetting();
        $this->assertInstanceOf(\DateTimeInterface::class, $setting->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $setting->getUpdatedAt());
    }

    // ========================================================================
    // DOMAIN EVENTS — UOM CATEGORY
    // ========================================================================

    public function test_uom_category_created_event_class_exists(): void
    {
        $this->assertTrue(class_exists(UomCategoryCreated::class));
    }

    public function test_uom_category_created_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(UomCategoryCreated::class, BaseEvent::class));
    }

    public function test_uom_category_created_holds_category(): void
    {
        $cat   = $this->makeCategoryWithId(1);
        $event = new UomCategoryCreated($cat);
        $this->assertSame($cat, $event->category);
    }

    public function test_uom_category_created_broadcasts_on_tenant_channel(): void
    {
        $cat      = $this->makeCategoryWithId(1);
        $event    = new UomCategoryCreated($cat);
        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertStringContainsString('tenant.1', $channels[0]->name);
    }

    public function test_uom_category_created_broadcast_with_contains_expected_keys(): void
    {
        $cat   = $this->makeCategoryWithId(1);
        $event = new UomCategoryCreated($cat);
        $data  = $event->broadcastWith();
        $this->assertArrayHasKey('id',   $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('code', $data);
    }

    public function test_uom_category_updated_event_class_exists(): void
    {
        $this->assertTrue(class_exists(UomCategoryUpdated::class));
    }

    public function test_uom_category_updated_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(UomCategoryUpdated::class, BaseEvent::class));
    }

    public function test_uom_category_updated_broadcasts_on_tenant_channel(): void
    {
        $cat      = $this->makeCategoryWithId(1);
        $event    = new UomCategoryUpdated($cat);
        $channels = $event->broadcastOn();
        $this->assertStringContainsString('tenant.1', $channels[0]->name);
    }

    public function test_uom_category_deleted_event_class_exists(): void
    {
        $this->assertTrue(class_exists(UomCategoryDeleted::class));
    }

    public function test_uom_category_deleted_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(UomCategoryDeleted::class, BaseEvent::class));
    }

    public function test_uom_category_deleted_broadcasts_on_tenant_channel(): void
    {
        $event    = new UomCategoryDeleted(5, 1);
        $channels = $event->broadcastOn();
        $this->assertStringContainsString('tenant.1', $channels[0]->name);
    }

    public function test_uom_category_deleted_broadcast_with_contains_id(): void
    {
        $event = new UomCategoryDeleted(5, 1);
        $data  = $event->broadcastWith();
        $this->assertArrayHasKey('id', $data);
        $this->assertSame(5, $data['id']);
    }

    public function test_uom_category_events_do_not_broadcast_on_org_channel(): void
    {
        $cat      = $this->makeCategoryWithId(1);
        $created  = new UomCategoryCreated($cat);
        $updated  = new UomCategoryUpdated($cat);
        $deleted  = new UomCategoryDeleted(5, 1);

        $this->assertCount(1, $created->broadcastOn());
        $this->assertCount(1, $updated->broadcastOn());
        $this->assertCount(1, $deleted->broadcastOn());
        $this->assertNull($created->orgUnitId);
        $this->assertNull($updated->orgUnitId);
        $this->assertNull($deleted->orgUnitId);
    }

    // ========================================================================
    // DOMAIN EVENTS — UNIT OF MEASURE
    // ========================================================================

    public function test_unit_of_measure_created_event_class_exists(): void
    {
        $this->assertTrue(class_exists(UnitOfMeasureCreated::class));
    }

    public function test_unit_of_measure_created_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(UnitOfMeasureCreated::class, BaseEvent::class));
    }

    public function test_unit_of_measure_created_holds_unit(): void
    {
        $unit  = $this->makeUnitWithId(1);
        $event = new UnitOfMeasureCreated($unit);
        $this->assertSame($unit, $event->unit);
    }

    public function test_unit_of_measure_created_broadcasts_on_tenant_channel(): void
    {
        $unit     = $this->makeUnitWithId(1);
        $event    = new UnitOfMeasureCreated($unit);
        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertStringContainsString('tenant.1', $channels[0]->name);
    }

    public function test_unit_of_measure_created_broadcast_with_contains_expected_keys(): void
    {
        $unit  = $this->makeUnitWithId(1);
        $event = new UnitOfMeasureCreated($unit);
        $data  = $event->broadcastWith();
        $this->assertArrayHasKey('id',     $data);
        $this->assertArrayHasKey('name',   $data);
        $this->assertArrayHasKey('code',   $data);
        $this->assertArrayHasKey('symbol', $data);
    }

    public function test_unit_of_measure_updated_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(UnitOfMeasureUpdated::class, BaseEvent::class));
    }

    public function test_unit_of_measure_updated_broadcasts_on_tenant_channel(): void
    {
        $unit     = $this->makeUnitWithId(1);
        $event    = new UnitOfMeasureUpdated($unit);
        $channels = $event->broadcastOn();
        $this->assertStringContainsString('tenant.1', $channels[0]->name);
    }

    public function test_unit_of_measure_deleted_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(UnitOfMeasureDeleted::class, BaseEvent::class));
    }

    public function test_unit_of_measure_deleted_broadcasts_on_tenant_channel(): void
    {
        $event    = new UnitOfMeasureDeleted(7, 1);
        $channels = $event->broadcastOn();
        $this->assertStringContainsString('tenant.1', $channels[0]->name);
    }

    public function test_unit_of_measure_deleted_broadcast_with_contains_id(): void
    {
        $event = new UnitOfMeasureDeleted(7, 1);
        $data  = $event->broadcastWith();
        $this->assertArrayHasKey('id', $data);
        $this->assertSame(7, $data['id']);
    }

    public function test_unit_of_measure_events_do_not_broadcast_on_org_channel(): void
    {
        $unit    = $this->makeUnitWithId(1);
        $created = new UnitOfMeasureCreated($unit);
        $updated = new UnitOfMeasureUpdated($unit);
        $deleted = new UnitOfMeasureDeleted(7, 1);

        $this->assertNull($created->orgUnitId);
        $this->assertNull($updated->orgUnitId);
        $this->assertNull($deleted->orgUnitId);
    }

    // ========================================================================
    // DOMAIN EVENTS — UOM CONVERSION
    // ========================================================================

    public function test_uom_conversion_created_event_class_exists(): void
    {
        $this->assertTrue(class_exists(UomConversionCreated::class));
    }

    public function test_uom_conversion_created_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(UomConversionCreated::class, BaseEvent::class));
    }

    public function test_uom_conversion_created_broadcasts_on_tenant_channel(): void
    {
        $conv     = $this->makeConversionWithId(1);
        $event    = new UomConversionCreated($conv);
        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertStringContainsString('tenant.1', $channels[0]->name);
    }

    public function test_uom_conversion_created_broadcast_with_contains_expected_keys(): void
    {
        $conv  = $this->makeConversionWithId(1);
        $event = new UomConversionCreated($conv);
        $data  = $event->broadcastWith();
        $this->assertArrayHasKey('id',          $data);
        $this->assertArrayHasKey('from_uom_id', $data);
        $this->assertArrayHasKey('to_uom_id',   $data);
        $this->assertArrayHasKey('factor',      $data);
    }

    public function test_uom_conversion_updated_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(UomConversionUpdated::class, BaseEvent::class));
    }

    public function test_uom_conversion_deleted_broadcasts_on_tenant_channel(): void
    {
        $event    = new UomConversionDeleted(3, 1);
        $channels = $event->broadcastOn();
        $this->assertStringContainsString('tenant.1', $channels[0]->name);
    }

    public function test_uom_conversion_events_do_not_broadcast_on_org_channel(): void
    {
        $conv    = $this->makeConversionWithId(1);
        $created = new UomConversionCreated($conv);
        $updated = new UomConversionUpdated($conv);
        $deleted = new UomConversionDeleted(3, 1);

        $this->assertNull($created->orgUnitId);
        $this->assertNull($updated->orgUnitId);
        $this->assertNull($deleted->orgUnitId);
    }

    // ========================================================================
    // DOMAIN EVENTS — PRODUCT UOM SETTING
    // ========================================================================

    public function test_product_uom_setting_created_event_class_exists(): void
    {
        $this->assertTrue(class_exists(ProductUomSettingCreated::class));
    }

    public function test_product_uom_setting_created_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(ProductUomSettingCreated::class, BaseEvent::class));
    }

    public function test_product_uom_setting_created_broadcasts_on_tenant_channel(): void
    {
        $setting  = $this->makeSettingWithId(1);
        $event    = new ProductUomSettingCreated($setting);
        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertStringContainsString('tenant.1', $channels[0]->name);
    }

    public function test_product_uom_setting_created_broadcast_with_contains_expected_keys(): void
    {
        $setting = $this->makeSettingWithId(1);
        $event   = new ProductUomSettingCreated($setting);
        $data    = $event->broadcastWith();
        $this->assertArrayHasKey('id',         $data);
        $this->assertArrayHasKey('product_id', $data);
    }

    public function test_product_uom_setting_updated_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(ProductUomSettingUpdated::class, BaseEvent::class));
    }

    public function test_product_uom_setting_deleted_broadcasts_on_tenant_channel(): void
    {
        $event    = new ProductUomSettingDeleted(8, 1);
        $channels = $event->broadcastOn();
        $this->assertStringContainsString('tenant.1', $channels[0]->name);
    }

    public function test_product_uom_setting_events_do_not_broadcast_on_org_channel(): void
    {
        $setting = $this->makeSettingWithId(1);
        $created = new ProductUomSettingCreated($setting);
        $updated = new ProductUomSettingUpdated($setting);
        $deleted = new ProductUomSettingDeleted(8, 1);

        $this->assertNull($created->orgUnitId);
        $this->assertNull($updated->orgUnitId);
        $this->assertNull($deleted->orgUnitId);
    }

    // ========================================================================
    // DOMAIN EXCEPTIONS
    // ========================================================================

    public function test_uom_category_not_found_exception_class_exists(): void
    {
        $this->assertTrue(class_exists(UomCategoryNotFoundException::class));
    }

    public function test_uom_category_not_found_exception_is_throwable(): void
    {
        $this->expectException(UomCategoryNotFoundException::class);
        throw new UomCategoryNotFoundException(5);
    }

    public function test_unit_of_measure_not_found_exception_class_exists(): void
    {
        $this->assertTrue(class_exists(UnitOfMeasureNotFoundException::class));
    }

    public function test_unit_of_measure_not_found_exception_is_throwable(): void
    {
        $this->expectException(UnitOfMeasureNotFoundException::class);
        throw new UnitOfMeasureNotFoundException(10);
    }

    public function test_uom_conversion_not_found_exception_class_exists(): void
    {
        $this->assertTrue(class_exists(UomConversionNotFoundException::class));
    }

    public function test_uom_conversion_not_found_exception_is_throwable(): void
    {
        $this->expectException(UomConversionNotFoundException::class);
        throw new UomConversionNotFoundException(3);
    }

    public function test_product_uom_setting_not_found_exception_class_exists(): void
    {
        $this->assertTrue(class_exists(ProductUomSettingNotFoundException::class));
    }

    public function test_product_uom_setting_not_found_exception_is_throwable(): void
    {
        $this->expectException(ProductUomSettingNotFoundException::class);
        throw new ProductUomSettingNotFoundException(42);
    }

    // ========================================================================
    // REPOSITORY INTERFACES
    // ========================================================================

    public function test_uom_category_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(UomCategoryRepositoryInterface::class));
    }

    public function test_uom_category_repository_has_save_method(): void
    {
        $this->assertTrue(method_exists(UomCategoryRepositoryInterface::class, 'save'));
    }

    public function test_unit_of_measure_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(UnitOfMeasureRepositoryInterface::class));
    }

    public function test_unit_of_measure_repository_has_save_method(): void
    {
        $this->assertTrue(method_exists(UnitOfMeasureRepositoryInterface::class, 'save'));
    }

    public function test_uom_conversion_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(UomConversionRepositoryInterface::class));
    }

    public function test_uom_conversion_repository_has_save_method(): void
    {
        $this->assertTrue(method_exists(UomConversionRepositoryInterface::class, 'save'));
    }

    public function test_product_uom_setting_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(ProductUomSettingRepositoryInterface::class));
    }

    public function test_product_uom_setting_repository_has_save_method(): void
    {
        $this->assertTrue(method_exists(ProductUomSettingRepositoryInterface::class, 'save'));
    }

    // ========================================================================
    // APPLICATION DTOs
    // ========================================================================

    public function test_uom_category_data_extends_base_dto(): void
    {
        $this->assertTrue(is_subclass_of(UomCategoryData::class, BaseDto::class));
    }

    public function test_uom_category_data_has_required_rules(): void
    {
        $dto = new UomCategoryData();
        $this->assertArrayHasKey('tenantId', $dto->rules());
        $this->assertArrayHasKey('name',     $dto->rules());
        $this->assertArrayHasKey('code',     $dto->rules());
    }

    public function test_update_uom_category_data_extends_base_dto(): void
    {
        $this->assertTrue(is_subclass_of(UpdateUomCategoryData::class, BaseDto::class));
    }

    public function test_update_uom_category_data_is_provided(): void
    {
        $dto = UpdateUomCategoryData::fromArray(['id' => 1, 'name' => 'New Name']);
        $this->assertTrue($dto->isProvided('name'));
        $this->assertFalse($dto->isProvided('code'));
    }

    public function test_unit_of_measure_data_extends_base_dto(): void
    {
        $this->assertTrue(is_subclass_of(UnitOfMeasureData::class, BaseDto::class));
    }

    public function test_unit_of_measure_data_has_required_rules(): void
    {
        $dto = new UnitOfMeasureData();
        $this->assertArrayHasKey('tenantId',      $dto->rules());
        $this->assertArrayHasKey('uomCategoryId', $dto->rules());
        $this->assertArrayHasKey('name',          $dto->rules());
        $this->assertArrayHasKey('code',          $dto->rules());
        $this->assertArrayHasKey('symbol',        $dto->rules());
    }

    public function test_update_unit_of_measure_data_is_provided(): void
    {
        $dto = UpdateUnitOfMeasureData::fromArray(['id' => 1, 'symbol' => 'kg']);
        $this->assertTrue($dto->isProvided('symbol'));
        $this->assertFalse($dto->isProvided('name'));
    }

    public function test_uom_conversion_data_extends_base_dto(): void
    {
        $this->assertTrue(is_subclass_of(UomConversionData::class, BaseDto::class));
    }

    public function test_uom_conversion_data_has_required_rules(): void
    {
        $dto = new UomConversionData();
        $this->assertArrayHasKey('tenantId',  $dto->rules());
        $this->assertArrayHasKey('fromUomId', $dto->rules());
        $this->assertArrayHasKey('toUomId',   $dto->rules());
        $this->assertArrayHasKey('factor',    $dto->rules());
    }

    public function test_product_uom_setting_data_extends_base_dto(): void
    {
        $this->assertTrue(is_subclass_of(ProductUomSettingData::class, BaseDto::class));
    }

    public function test_product_uom_setting_data_has_required_rules(): void
    {
        $dto = new ProductUomSettingData();
        $this->assertArrayHasKey('tenantId',  $dto->rules());
        $this->assertArrayHasKey('productId', $dto->rules());
    }

    public function test_product_uom_setting_data_has_factor_rules(): void
    {
        $dto = new ProductUomSettingData();
        $this->assertArrayHasKey('purchaseFactor',  $dto->rules());
        $this->assertArrayHasKey('salesFactor',     $dto->rules());
        $this->assertArrayHasKey('inventoryFactor', $dto->rules());
    }

    // ========================================================================
    // APPLICATION SERVICE CONTRACTS
    // ========================================================================

    public function test_create_uom_category_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(CreateUomCategoryServiceInterface::class));
    }

    public function test_find_uom_category_service_interface_extends_read_service(): void
    {
        $this->assertTrue(is_subclass_of(FindUomCategoryServiceInterface::class, ReadServiceInterface::class));
    }

    public function test_update_uom_category_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(UpdateUomCategoryServiceInterface::class));
    }

    public function test_delete_uom_category_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(DeleteUomCategoryServiceInterface::class));
    }

    public function test_create_unit_of_measure_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(CreateUnitOfMeasureServiceInterface::class));
    }

    public function test_find_unit_of_measure_service_interface_extends_read_service(): void
    {
        $this->assertTrue(is_subclass_of(FindUnitOfMeasureServiceInterface::class, ReadServiceInterface::class));
    }

    public function test_update_unit_of_measure_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(UpdateUnitOfMeasureServiceInterface::class));
    }

    public function test_delete_unit_of_measure_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(DeleteUnitOfMeasureServiceInterface::class));
    }

    public function test_create_uom_conversion_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(CreateUomConversionServiceInterface::class));
    }

    public function test_find_uom_conversion_service_interface_extends_read_service(): void
    {
        $this->assertTrue(is_subclass_of(FindUomConversionServiceInterface::class, ReadServiceInterface::class));
    }

    public function test_update_uom_conversion_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(UpdateUomConversionServiceInterface::class));
    }

    public function test_delete_uom_conversion_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(DeleteUomConversionServiceInterface::class));
    }

    public function test_create_product_uom_setting_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(CreateProductUomSettingServiceInterface::class));
    }

    public function test_find_product_uom_setting_service_interface_extends_read_service(): void
    {
        $this->assertTrue(is_subclass_of(FindProductUomSettingServiceInterface::class, ReadServiceInterface::class));
    }

    public function test_update_product_uom_setting_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(UpdateProductUomSettingServiceInterface::class));
    }

    public function test_delete_product_uom_setting_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(DeleteProductUomSettingServiceInterface::class));
    }

    // ========================================================================
    // APPLICATION SERVICES (CONCRETE)
    // ========================================================================

    public function test_create_uom_category_service_extends_base_service(): void
    {
        $this->assertTrue(is_subclass_of(CreateUomCategoryService::class, BaseService::class));
    }

    public function test_create_uom_category_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(CreateUomCategoryService::class, CreateUomCategoryServiceInterface::class));
    }

    public function test_find_uom_category_service_extends_base_service(): void
    {
        $this->assertTrue(is_subclass_of(FindUomCategoryService::class, BaseService::class));
    }

    public function test_find_uom_category_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(FindUomCategoryService::class, FindUomCategoryServiceInterface::class));
    }

    public function test_update_uom_category_service_extends_base_service(): void
    {
        $this->assertTrue(is_subclass_of(UpdateUomCategoryService::class, BaseService::class));
    }

    public function test_update_uom_category_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(UpdateUomCategoryService::class, UpdateUomCategoryServiceInterface::class));
    }

    public function test_delete_uom_category_service_extends_base_service(): void
    {
        $this->assertTrue(is_subclass_of(DeleteUomCategoryService::class, BaseService::class));
    }

    public function test_delete_uom_category_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(DeleteUomCategoryService::class, DeleteUomCategoryServiceInterface::class));
    }

    public function test_create_unit_of_measure_service_extends_base_service(): void
    {
        $this->assertTrue(is_subclass_of(CreateUnitOfMeasureService::class, BaseService::class));
    }

    public function test_create_unit_of_measure_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(CreateUnitOfMeasureService::class, CreateUnitOfMeasureServiceInterface::class));
    }

    public function test_find_unit_of_measure_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(FindUnitOfMeasureService::class, FindUnitOfMeasureServiceInterface::class));
    }

    public function test_update_unit_of_measure_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(UpdateUnitOfMeasureService::class, UpdateUnitOfMeasureServiceInterface::class));
    }

    public function test_delete_unit_of_measure_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(DeleteUnitOfMeasureService::class, DeleteUnitOfMeasureServiceInterface::class));
    }

    public function test_create_uom_conversion_service_extends_base_service(): void
    {
        $this->assertTrue(is_subclass_of(CreateUomConversionService::class, BaseService::class));
    }

    public function test_create_uom_conversion_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(CreateUomConversionService::class, CreateUomConversionServiceInterface::class));
    }

    public function test_find_uom_conversion_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(FindUomConversionService::class, FindUomConversionServiceInterface::class));
    }

    public function test_update_uom_conversion_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(UpdateUomConversionService::class, UpdateUomConversionServiceInterface::class));
    }

    public function test_delete_uom_conversion_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(DeleteUomConversionService::class, DeleteUomConversionServiceInterface::class));
    }

    public function test_create_product_uom_setting_service_extends_base_service(): void
    {
        $this->assertTrue(is_subclass_of(CreateProductUomSettingService::class, BaseService::class));
    }

    public function test_create_product_uom_setting_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(CreateProductUomSettingService::class, CreateProductUomSettingServiceInterface::class));
    }

    public function test_find_product_uom_setting_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(FindProductUomSettingService::class, FindProductUomSettingServiceInterface::class));
    }

    public function test_update_product_uom_setting_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(UpdateProductUomSettingService::class, UpdateProductUomSettingServiceInterface::class));
    }

    public function test_delete_product_uom_setting_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(DeleteProductUomSettingService::class, DeleteProductUomSettingServiceInterface::class));
    }

    // ========================================================================
    // INFRASTRUCTURE MODELS
    // ========================================================================

    public function test_uom_category_model_class_exists(): void
    {
        $this->assertTrue(class_exists(UomCategoryModel::class));
    }

    public function test_uom_category_model_table_name(): void
    {
        $model = new UomCategoryModel();
        $this->assertSame('uom_categories', $model->getTable());
    }

    public function test_unit_of_measure_model_class_exists(): void
    {
        $this->assertTrue(class_exists(UnitOfMeasureModel::class));
    }

    public function test_unit_of_measure_model_table_name(): void
    {
        $model = new UnitOfMeasureModel();
        $this->assertSame('units_of_measure', $model->getTable());
    }

    public function test_uom_conversion_model_class_exists(): void
    {
        $this->assertTrue(class_exists(UomConversionModel::class));
    }

    public function test_uom_conversion_model_table_name(): void
    {
        $model = new UomConversionModel();
        $this->assertSame('uom_conversions', $model->getTable());
    }

    public function test_product_uom_setting_model_class_exists(): void
    {
        $this->assertTrue(class_exists(ProductUomSettingModel::class));
    }

    public function test_product_uom_setting_model_table_name(): void
    {
        $model = new ProductUomSettingModel();
        $this->assertSame('product_uom_settings', $model->getTable());
    }

    // ========================================================================
    // INFRASTRUCTURE REPOSITORIES
    // ========================================================================

    public function test_eloquent_uom_category_repository_class_exists(): void
    {
        $this->assertTrue(class_exists(EloquentUomCategoryRepository::class));
    }

    public function test_eloquent_uom_category_repository_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(EloquentUomCategoryRepository::class, UomCategoryRepositoryInterface::class));
    }

    public function test_eloquent_unit_of_measure_repository_class_exists(): void
    {
        $this->assertTrue(class_exists(EloquentUnitOfMeasureRepository::class));
    }

    public function test_eloquent_unit_of_measure_repository_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(EloquentUnitOfMeasureRepository::class, UnitOfMeasureRepositoryInterface::class));
    }

    public function test_eloquent_uom_conversion_repository_class_exists(): void
    {
        $this->assertTrue(class_exists(EloquentUomConversionRepository::class));
    }

    public function test_eloquent_uom_conversion_repository_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(EloquentUomConversionRepository::class, UomConversionRepositoryInterface::class));
    }

    public function test_eloquent_product_uom_setting_repository_class_exists(): void
    {
        $this->assertTrue(class_exists(EloquentProductUomSettingRepository::class));
    }

    public function test_eloquent_product_uom_setting_repository_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(EloquentProductUomSettingRepository::class, ProductUomSettingRepositoryInterface::class));
    }

    // ========================================================================
    // INFRASTRUCTURE CONTROLLERS
    // ========================================================================

    public function test_uom_category_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(UomCategoryController::class));
    }

    public function test_uom_category_controller_injects_four_services(): void
    {
        $r      = new \ReflectionClass(UomCategoryController::class);
        $params = array_map(fn ($p) => (string) $p->getType(), $r->getConstructor()->getParameters());
        $this->assertContains(FindUomCategoryServiceInterface::class,   $params);
        $this->assertContains(CreateUomCategoryServiceInterface::class, $params);
        $this->assertContains(UpdateUomCategoryServiceInterface::class, $params);
        $this->assertContains(DeleteUomCategoryServiceInterface::class, $params);
    }

    public function test_unit_of_measure_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(UnitOfMeasureController::class));
    }

    public function test_unit_of_measure_controller_injects_four_services(): void
    {
        $r      = new \ReflectionClass(UnitOfMeasureController::class);
        $params = array_map(fn ($p) => (string) $p->getType(), $r->getConstructor()->getParameters());
        $this->assertContains(FindUnitOfMeasureServiceInterface::class,   $params);
        $this->assertContains(CreateUnitOfMeasureServiceInterface::class, $params);
        $this->assertContains(UpdateUnitOfMeasureServiceInterface::class, $params);
        $this->assertContains(DeleteUnitOfMeasureServiceInterface::class, $params);
    }

    public function test_uom_conversion_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(UomConversionController::class));
    }

    public function test_uom_conversion_controller_injects_four_services(): void
    {
        $r      = new \ReflectionClass(UomConversionController::class);
        $params = array_map(fn ($p) => (string) $p->getType(), $r->getConstructor()->getParameters());
        $this->assertContains(FindUomConversionServiceInterface::class,   $params);
        $this->assertContains(CreateUomConversionServiceInterface::class, $params);
        $this->assertContains(UpdateUomConversionServiceInterface::class, $params);
        $this->assertContains(DeleteUomConversionServiceInterface::class, $params);
    }

    public function test_product_uom_setting_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(ProductUomSettingController::class));
    }

    public function test_product_uom_setting_controller_injects_four_services(): void
    {
        $r      = new \ReflectionClass(ProductUomSettingController::class);
        $params = array_map(fn ($p) => (string) $p->getType(), $r->getConstructor()->getParameters());
        $this->assertContains(FindProductUomSettingServiceInterface::class,   $params);
        $this->assertContains(CreateProductUomSettingServiceInterface::class, $params);
        $this->assertContains(UpdateProductUomSettingServiceInterface::class, $params);
        $this->assertContains(DeleteProductUomSettingServiceInterface::class, $params);
    }

    // ========================================================================
    // INFRASTRUCTURE FORM REQUESTS
    // ========================================================================

    public function test_store_uom_category_request_rules(): void
    {
        $r = new StoreUomCategoryRequest();
        $this->assertArrayHasKey('tenant_id', $r->rules());
        $this->assertArrayHasKey('name',      $r->rules());
        $this->assertArrayHasKey('code',      $r->rules());
    }

    public function test_update_uom_category_request_uses_sometimes(): void
    {
        $r = new UpdateUomCategoryRequest();
        $this->assertStringContainsString('sometimes', $r->rules()['name']);
        $this->assertStringContainsString('sometimes', $r->rules()['code']);
    }

    public function test_store_unit_of_measure_request_rules(): void
    {
        $r = new StoreUnitOfMeasureRequest();
        $this->assertArrayHasKey('tenant_id',       $r->rules());
        $this->assertArrayHasKey('uom_category_id', $r->rules());
        $this->assertArrayHasKey('name',            $r->rules());
        $this->assertArrayHasKey('code',            $r->rules());
        $this->assertArrayHasKey('symbol',          $r->rules());
    }

    public function test_update_unit_of_measure_request_uses_sometimes(): void
    {
        $r = new UpdateUnitOfMeasureRequest();
        $this->assertStringContainsString('sometimes', $r->rules()['name']);
    }

    public function test_store_uom_conversion_request_rules(): void
    {
        $r = new StoreUomConversionRequest();
        $this->assertArrayHasKey('tenant_id',   $r->rules());
        $this->assertArrayHasKey('from_uom_id', $r->rules());
        $this->assertArrayHasKey('to_uom_id',   $r->rules());
        $this->assertArrayHasKey('factor',      $r->rules());
    }

    public function test_store_product_uom_setting_request_rules(): void
    {
        $r = new StoreProductUomSettingRequest();
        $this->assertArrayHasKey('tenant_id',       $r->rules());
        $this->assertArrayHasKey('product_id',      $r->rules());
        $this->assertArrayHasKey('purchase_factor', $r->rules());
        $this->assertArrayHasKey('sales_factor',    $r->rules());
    }

    public function test_update_product_uom_setting_request_has_factor_rules(): void
    {
        $r = new UpdateProductUomSettingRequest();
        $this->assertArrayHasKey('purchase_factor',  $r->rules());
        $this->assertArrayHasKey('sales_factor',     $r->rules());
        $this->assertArrayHasKey('inventory_factor', $r->rules());
    }

    // ========================================================================
    // INFRASTRUCTURE RESOURCES
    // ========================================================================

    public function test_uom_category_resource_returns_expected_keys(): void
    {
        $cat  = $this->makeCategoryWithId(1);
        $res  = new UomCategoryResource($cat);
        $data = $res->toArray(new \Illuminate\Http\Request());
        $this->assertArrayHasKey('id',          $data);
        $this->assertArrayHasKey('tenant_id',   $data);
        $this->assertArrayHasKey('name',        $data);
        $this->assertArrayHasKey('code',        $data);
        $this->assertArrayHasKey('is_active',   $data);
        $this->assertArrayHasKey('created_at',  $data);
        $this->assertArrayHasKey('updated_at',  $data);
    }

    public function test_uom_category_collection_collects_correct_resource(): void
    {
        $collection = new UomCategoryCollection([]);
        $this->assertSame(UomCategoryResource::class, $collection->collects);
    }

    public function test_unit_of_measure_resource_returns_expected_keys(): void
    {
        $unit = $this->makeUnitWithId(1);
        $res  = new UnitOfMeasureResource($unit);
        $data = $res->toArray(new \Illuminate\Http\Request());
        $this->assertArrayHasKey('id',              $data);
        $this->assertArrayHasKey('uom_category_id', $data);
        $this->assertArrayHasKey('name',            $data);
        $this->assertArrayHasKey('code',            $data);
        $this->assertArrayHasKey('symbol',          $data);
        $this->assertArrayHasKey('is_base_unit',    $data);
        $this->assertArrayHasKey('factor',          $data);
        $this->assertArrayHasKey('is_active',       $data);
    }

    public function test_unit_of_measure_collection_collects_correct_resource(): void
    {
        $collection = new UnitOfMeasureCollection([]);
        $this->assertSame(UnitOfMeasureResource::class, $collection->collects);
    }

    public function test_uom_conversion_resource_returns_expected_keys(): void
    {
        $conv = $this->makeConversionWithId(1);
        $res  = new UomConversionResource($conv);
        $data = $res->toArray(new \Illuminate\Http\Request());
        $this->assertArrayHasKey('id',          $data);
        $this->assertArrayHasKey('from_uom_id', $data);
        $this->assertArrayHasKey('to_uom_id',   $data);
        $this->assertArrayHasKey('factor',      $data);
        $this->assertArrayHasKey('is_active',   $data);
    }

    public function test_uom_conversion_collection_collects_correct_resource(): void
    {
        $collection = new UomConversionCollection([]);
        $this->assertSame(UomConversionResource::class, $collection->collects);
    }

    public function test_product_uom_setting_resource_returns_expected_keys(): void
    {
        $setting = $this->makeSettingWithId(1);
        $res     = new ProductUomSettingResource($setting);
        $data    = $res->toArray(new \Illuminate\Http\Request());
        $this->assertArrayHasKey('id',               $data);
        $this->assertArrayHasKey('product_id',       $data);
        $this->assertArrayHasKey('base_uom_id',      $data);
        $this->assertArrayHasKey('purchase_uom_id',  $data);
        $this->assertArrayHasKey('sales_uom_id',     $data);
        $this->assertArrayHasKey('inventory_uom_id', $data);
        $this->assertArrayHasKey('purchase_factor',  $data);
        $this->assertArrayHasKey('sales_factor',     $data);
        $this->assertArrayHasKey('inventory_factor', $data);
        $this->assertArrayHasKey('is_active',        $data);
    }

    public function test_product_uom_setting_collection_collects_correct_resource(): void
    {
        $collection = new ProductUomSettingCollection([]);
        $this->assertSame(ProductUomSettingResource::class, $collection->collects);
    }

    // ========================================================================
    // SERVICE PROVIDER
    // ========================================================================

    public function test_uom_service_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(UomServiceProvider::class));
    }

    public function test_uom_service_provider_has_register_method(): void
    {
        $this->assertTrue(method_exists(UomServiceProvider::class, 'register'));
    }

    public function test_uom_service_provider_has_boot_method(): void
    {
        $this->assertTrue(method_exists(UomServiceProvider::class, 'boot'));
    }

    // ========================================================================
    // HELPERS
    // ========================================================================

    private function makeCategory(): UomCategory
    {
        return new UomCategory(
            tenantId:    1,
            name:        'Weight',
            code:        'WEIGHT',
            description: 'Weight measurements',
            isActive:    true,
        );
    }

    private function makeCategoryWithId(int $tenantId): UomCategory
    {
        return new UomCategory(
            tenantId:    $tenantId,
            name:        'Weight',
            code:        'WEIGHT',
            description: 'Weight measurements',
            isActive:    true,
            id:          1,
        );
    }

    private function makeUnit(): UnitOfMeasure
    {
        return new UnitOfMeasure(
            tenantId:      1,
            uomCategoryId: 5,
            name:          'Kilogram',
            code:          'KG',
            symbol:        'kg',
            isBaseUnit:    true,
            factor:        1.0,
        );
    }

    private function makeUnitWithId(int $tenantId): UnitOfMeasure
    {
        return new UnitOfMeasure(
            tenantId:      $tenantId,
            uomCategoryId: 5,
            name:          'Kilogram',
            code:          'KG',
            symbol:        'kg',
            isBaseUnit:    true,
            factor:        1.0,
            description:   null,
            isActive:      true,
            id:            1,
        );
    }

    private function makeConversion(): UomConversion
    {
        return new UomConversion(
            tenantId:  1,
            fromUomId: 10,
            toUomId:   11,
            factor:    1000.0,
        );
    }

    private function makeConversionWithId(int $tenantId): UomConversion
    {
        return new UomConversion(
            tenantId:  $tenantId,
            fromUomId: 10,
            toUomId:   11,
            factor:    1000.0,
            isActive:  true,
            id:        1,
        );
    }

    private function makeSetting(): ProductUomSetting
    {
        return new ProductUomSetting(
            tenantId:        1,
            productId:       42,
            baseUomId:       10,
            purchaseUomId:   11,
            salesUomId:      12,
            inventoryUomId:  10,
            purchaseFactor:  12.0,
            salesFactor:     1.0,
            inventoryFactor: 1.0,
            isActive:        true,
        );
    }

    private function makeSettingWithId(int $tenantId): ProductUomSetting
    {
        return new ProductUomSetting(
            tenantId:        $tenantId,
            productId:       42,
            baseUomId:       10,
            purchaseUomId:   11,
            salesUomId:      12,
            inventoryUomId:  10,
            purchaseFactor:  12.0,
            salesFactor:     1.0,
            inventoryFactor: 1.0,
            isActive:        true,
            id:              1,
        );
    }
}
