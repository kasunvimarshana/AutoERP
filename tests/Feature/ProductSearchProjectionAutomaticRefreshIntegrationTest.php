<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Application\Contracts\CreateBatchServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteBatchServiceInterface;
use Modules\Inventory\Application\Contracts\RecordStockMovementServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateBatchServiceInterface;
use Modules\Product\Application\Contracts\CreateProductServiceInterface;
use Modules\Product\Application\Contracts\CreateProductVariantServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductServiceInterface;
use Modules\Product\Application\Contracts\CreateProductIdentifierServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductVariantServiceInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductSearchProjectionModel;
use Modules\Warehouse\Application\Contracts\CreateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseServiceInterface;
use Tests\TestCase;

class ProductSearchProjectionAutomaticRefreshIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;
    private CreateProductServiceInterface $createProductService;
    private UpdateProductServiceInterface $updateProductService;
    private DeleteProductServiceInterface $deleteProductService;
    private CreateProductVariantServiceInterface $createVariantService;
    private DeleteProductVariantServiceInterface $deleteVariantService;
    private CreateProductIdentifierServiceInterface $createIdentifierService;
    private CreateBatchServiceInterface $createBatchService;
    private UpdateBatchServiceInterface $updateBatchService;
    private DeleteBatchServiceInterface $deleteBatchService;
    private RecordStockMovementServiceInterface $recordStockMovementService;
    private CreateWarehouseServiceInterface $createWarehouseService;
    private CreateWarehouseLocationServiceInterface $createWarehouseLocationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantId = 60;

        // Create minimal tenant record
        DB::table('tenants')->insert([
            'id' => $this->tenantId,
            'name' => 'Test Tenant',
            'slug' => 'test-tenant-'.time(),
            'plan' => 'free',
            'tenant_plan_id' => null,
            'status' => 'active',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create reference data required by Product service
        DB::table('units_of_measure')->insert([
            'id' => 1,
            'tenant_id' => $this->tenantId,
            'name' => 'Piece',
            'symbol' => 'pcs',
            'type' => 'unit',
            'is_base' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('units_of_measure')->insert([
            'id' => 2,
            'tenant_id' => $this->tenantId,
            'name' => 'Box',
            'symbol' => 'box',
            'type' => 'unit',
            'is_base' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createProductService = app(CreateProductServiceInterface::class);
        $this->updateProductService = app(UpdateProductServiceInterface::class);
        $this->deleteProductService = app(DeleteProductServiceInterface::class);
        $this->createVariantService = app(CreateProductVariantServiceInterface::class);
        $this->deleteVariantService = app(DeleteProductVariantServiceInterface::class);
        $this->createIdentifierService = app(CreateProductIdentifierServiceInterface::class);
        $this->createBatchService = app(CreateBatchServiceInterface::class);
        $this->updateBatchService = app(UpdateBatchServiceInterface::class);
        $this->deleteBatchService = app(DeleteBatchServiceInterface::class);
        $this->recordStockMovementService = app(RecordStockMovementServiceInterface::class);
        $this->createWarehouseService = app(CreateWarehouseServiceInterface::class);
        $this->createWarehouseLocationService = app(CreateWarehouseLocationServiceInterface::class);
    }

    public function test_creating_product_automatically_populates_projection(): void
    {
        // Verify no projection exists initially
        $before = ProductSearchProjectionModel::where('tenant_id', $this->tenantId)
            ->where('product_name', 'Like Search Test')
            ->count();
        $this->assertEquals(0, $before);

        // Create product
        $product = $this->createProductService->execute([
            'tenant_id' => $this->tenantId,
            'name' => 'Like Search Test',
            'sku' => 'LIKE-TEST-001',
            'category_id' => null,
            'brand_id' => null,
            'base_uom_id' => 1,
            'description' => 'Test product for like search',
            'is_active' => true,
            'metadata' => [],
        ]);

        // Verify projection was created automatically
        $projection = ProductSearchProjectionModel::where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->first();

        $this->assertNotNull($projection);
        $this->assertEquals('Like Search Test', $projection->product_name);
        $this->assertEquals('LIKE-TEST-001', $projection->product_sku);
        $this->assertTrue($projection->is_active_product);
    }

    public function test_updating_product_automatically_refreshes_projection(): void
    {
        // Create product
        $product = $this->createProductService->execute([
            'tenant_id' => $this->tenantId,
            'name' => 'Original Name',
            'sku' => 'ORIGINAL-SKU',
            'category_id' => null,
            'brand_id' => null,
            'base_uom_id' => 1,
            'description' => 'Original description',
            'is_active' => true,
            'metadata' => [],
        ]);

        // Verify initial projection
        $projectionBefore = ProductSearchProjectionModel::where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->firstOrFail();
        $this->assertEquals('Original Name', $projectionBefore->product_name);
        $this->assertEquals('ORIGINAL-SKU', $projectionBefore->product_sku);
        $updatedAtBefore = $projectionBefore->updated_at;

        // Wait a moment to ensure timestamp difference
        sleep(1);

        // Update product
        $this->updateProductService->execute([
            'id' => $product->getId(),
            'tenant_id' => $this->tenantId,
            'name' => 'Updated Name',
            'sku' => 'UPDATED-SKU',
            'category_id' => null,
            'brand_id' => null,
            'base_uom_id' => 1,
            'description' => 'Updated description',
            'is_active' => false,
            'metadata' => [],
        ]);

        // Verify projection was updated automatically
        $projectionAfter = ProductSearchProjectionModel::where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->firstOrFail();

        $this->assertEquals('Updated Name', $projectionAfter->product_name);
        $this->assertEquals('UPDATED-SKU', $projectionAfter->product_sku);
        $this->assertFalse($projectionAfter->is_active_product);
        $this->assertGreaterThan($updatedAtBefore, $projectionAfter->updated_at);
    }

    public function test_deleting_product_automatically_removes_projection(): void
    {
        // Create product
        $product = $this->createProductService->execute([
            'tenant_id' => $this->tenantId,
            'name' => 'To Delete',
            'sku' => 'DELETE-ME',
            'category_id' => null,
            'brand_id' => null,
            'base_uom_id' => 1,
            'description' => 'Will be deleted',
            'is_active' => true,
            'metadata' => [],
        ]);

        // Verify projection exists
        $projectionBefore = ProductSearchProjectionModel::where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->count();
        $this->assertEquals(1, $projectionBefore);

        // Delete product (soft delete)
        $this->deleteProductService->execute([
            'id' => $product->getId(),
            'tenant_id' => $this->tenantId,
        ]);

        // Verify projection was hard-deleted (because refresh uses forceDelete)
        $projectionAfter = ProductSearchProjectionModel::withoutTrashed()
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->count();
        $this->assertEquals(0, $projectionAfter);

        $projectionAllIncludingTrashed = ProductSearchProjectionModel::onlyTrashed()
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->count();
        $this->assertEquals(0, $projectionAllIncludingTrashed);
    }

    public function test_creating_variant_automatically_adds_projection_row(): void
    {
        // Create product
        $product = $this->createProductService->execute([
            'tenant_id' => $this->tenantId,
            'name' => 'Base Product',
            'sku' => 'BASE-PROD',
            'category_id' => null,
            'brand_id' => null,
            'base_uom_id' => 1,
            'description' => 'Base product',
            'is_active' => true,
            'metadata' => [],
        ]);

        // Verify only base projection exists
        $projectionCountBefore = ProductSearchProjectionModel::where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->count();
        $this->assertEquals(1, $projectionCountBefore);

        // Create variant
        $variant = $this->createVariantService->execute([
            'tenant_id' => $this->tenantId,
            'product_id' => $product->getId(),
            'name' => 'Variant Red M',
            'sku' => 'BASE-PROD-RED-M',
            'is_active' => true,
            'metadata' => [],
        ]);

        // Verify variant projection was created automatically
        $variantProjection = ProductSearchProjectionModel::where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->where('variant_id', $variant->getId())
            ->first();

        $this->assertNotNull($variantProjection);
        $this->assertEquals('Variant Red M', $variantProjection->variant_name);
        $this->assertEquals('BASE-PROD-RED-M', $variantProjection->variant_sku);

        // Verify total projection count is now 2 (base + variant)
        $projectionCountAfter = ProductSearchProjectionModel::where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->count();
        $this->assertEquals(2, $projectionCountAfter);
    }

    public function test_deleting_variant_automatically_removes_its_projection_row(): void
    {
        // Create product
        $product = $this->createProductService->execute([
            'tenant_id' => $this->tenantId,
            'name' => 'Multi-Variant Product',
            'sku' => 'MULTI-VAR-PROD',
            'category_id' => null,
            'brand_id' => null,
            'base_uom_id' => 1,
            'description' => 'Product with variants',
            'is_active' => true,
            'metadata' => [],
        ]);

        // Create variant
        $variant = $this->createVariantService->execute([
            'tenant_id' => $this->tenantId,
            'product_id' => $product->getId(),
            'name' => 'Variant Blue L',
            'sku' => 'MULTI-VAR-PROD-BLUE-L',
            'is_active' => true,
            'metadata' => [],
        ]);

        // Verify variant projection exists
        $variantProjectionBefore = ProductSearchProjectionModel::where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->where('variant_id', $variant->getId())
            ->count();
        $this->assertEquals(1, $variantProjectionBefore);

        // Delete variant
        $this->deleteVariantService->execute([
            'id' => $variant->getId(),
            'tenant_id' => $this->tenantId,
        ]);

        // Verify variant projection was removed but base product projection remains
        $variantProjectionAfter = ProductSearchProjectionModel::withoutTrashed()
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->where('variant_id', $variant->getId())
            ->count();
        $this->assertEquals(0, $variantProjectionAfter);

        $baseProjectionCount = ProductSearchProjectionModel::withoutTrashed()
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->whereNull('variant_id')
            ->count();
        $this->assertEquals(1, $baseProjectionCount);
    }

    public function test_creating_identifier_automatically_updates_projection(): void
    {
        // Create product
        $product = $this->createProductService->execute([
            'tenant_id' => $this->tenantId,
            'name' => 'Identifiable Product',
            'sku' => 'ID-PROD-001',
            'category_id' => null,
            'brand_id' => null,
            'base_uom_id' => 1,
            'description' => 'Product with identifiers',
            'is_active' => true,
            'metadata' => [],
        ]);

        // Get initial projection
        $projectionBefore = ProductSearchProjectionModel::where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->firstOrFail();
        // Initially, identifiers_json should be an empty array (no identifiers yet)
        $this->assertIsArray($projectionBefore->identifiers_json);
        $this->assertEmpty($projectionBefore->identifiers_json);

        // Create identifier
        $identifier = $this->createIdentifierService->execute([
            'tenant_id' => $this->tenantId,
            'product_id' => $product->getId(),
            'variant_id' => null,
            'technology' => 'barcode_1d',
            'format' => 'ean13',
            'value' => '5901234123457',
            'is_active' => true,
            'metadata' => [],
        ]);

        // Verify projection identifiers were updated automatically
        $projectionAfter = ProductSearchProjectionModel::where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->firstOrFail();

        $this->assertNotNull($projectionAfter->identifiers_json);
        $identifierData = $projectionAfter->identifiers_json;
        $this->assertIsArray($identifierData);
        $this->assertNotEmpty($identifierData);
        $this->assertGreaterThan(0, count($identifierData));
        $this->assertStringContainsString('5901234123457', implode(' ', $identifierData));
    }

    public function test_batch_mutations_automatically_refresh_projection_batch_lot_text(): void
    {
        $product = $this->createProductService->execute([
            'tenant_id' => $this->tenantId,
            'name' => 'Batch Tracked Product',
            'sku' => 'BATCH-TRACK-001',
            'category_id' => null,
            'brand_id' => null,
            'base_uom_id' => 1,
            'description' => 'Product whose batch projection text should refresh',
            'is_batch_tracked' => true,
            'is_lot_tracked' => true,
            'is_active' => true,
            'metadata' => [],
        ]);

        $projectionBefore = ProductSearchProjectionModel::where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->whereNull('variant_id')
            ->firstOrFail();

        $this->assertTrue($projectionBefore->batch_lot_text === null || $projectionBefore->batch_lot_text === '');

        $batch = $this->createBatchService->execute([
            'tenant_id' => $this->tenantId,
            'product_id' => $product->getId(),
            'variant_id' => null,
            'batch_number' => 'BATCH-RED-001',
            'lot_number' => 'LOT-RED-001',
            'status' => 'active',
        ]);

        $projectionAfterCreate = ProductSearchProjectionModel::where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->whereNull('variant_id')
            ->firstOrFail();

        $this->assertStringContainsString('BATCH-RED-001', (string) $projectionAfterCreate->batch_lot_text);
        $this->assertStringContainsString('LOT-RED-001', (string) $projectionAfterCreate->batch_lot_text);

        $this->updateBatchService->execute([
            'id' => $batch->getId(),
            'tenant_id' => $this->tenantId,
            'batch_number' => 'BATCH-BLUE-001',
            'lot_number' => 'LOT-BLUE-001',
            'status' => 'quarantine',
        ]);

        $projectionAfterUpdate = ProductSearchProjectionModel::where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->whereNull('variant_id')
            ->firstOrFail();

        $this->assertStringContainsString('BATCH-BLUE-001', (string) $projectionAfterUpdate->batch_lot_text);
        $this->assertStringContainsString('LOT-BLUE-001', (string) $projectionAfterUpdate->batch_lot_text);
        $this->assertStringNotContainsString('BATCH-RED-001', (string) $projectionAfterUpdate->batch_lot_text);

        $this->deleteBatchService->execute([
            'id' => $batch->getId(),
        ]);

        $projectionAfterDelete = ProductSearchProjectionModel::where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->whereNull('variant_id')
            ->firstOrFail();

        $this->assertTrue($projectionAfterDelete->batch_lot_text === null || $projectionAfterDelete->batch_lot_text === '');
    }

    public function test_stock_movement_automatically_refreshes_projection_stock_fields(): void
    {
        $product = $this->createProductService->execute([
            'tenant_id' => $this->tenantId,
            'name' => 'Stock Refresh Product',
            'sku' => 'STOCK-REFRESH-001',
            'category_id' => null,
            'brand_id' => null,
            'base_uom_id' => 1,
            'description' => 'Product whose projection stock should refresh after movement',
            'is_active' => true,
            'metadata' => [],
        ]);

        $warehouse = $this->createWarehouseService->execute([
            'tenant_id' => $this->tenantId,
            'name' => 'Projection Warehouse',
            'code' => 'PRJ-WH-'.$this->tenantId,
            'is_default' => true,
        ]);

        $location = $this->createWarehouseLocationService->execute([
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $warehouse->getId(),
            'name' => 'Projection Bin',
            'code' => 'PRJ-BIN-'.$this->tenantId,
            'type' => 'bin',
        ]);

        $projectionBefore = ProductSearchProjectionModel::where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->whereNull('variant_id')
            ->firstOrFail();

        $this->assertSame('0.000000', (string) $projectionBefore->stock_available);

        $this->recordStockMovementService->execute([
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $warehouse->getId(),
            'product_id' => $product->getId(),
            'to_location_id' => $location->getId(),
            'movement_type' => 'receipt',
            'uom_id' => 1,
            'quantity' => '7.000000',
            'unit_cost' => '4.500000',
        ]);

        $projectionAfter = ProductSearchProjectionModel::where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->whereNull('variant_id')
            ->firstOrFail();

        $this->assertSame('7.000000', (string) $projectionAfter->stock_available);
        $this->assertIsArray($projectionAfter->stock_by_warehouse_json);
        $this->assertCount(1, $projectionAfter->stock_by_warehouse_json);
        $this->assertSame($warehouse->getId(), $projectionAfter->stock_by_warehouse_json[0]['warehouse_id']);
        $this->assertSame('7.000000', $projectionAfter->stock_by_warehouse_json[0]['available']);
    }
}
