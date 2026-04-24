<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Application\Contracts\RecordStockMovementServiceInterface;
use Modules\Pricing\Application\Contracts\CreatePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\DeletePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\DeletePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UpdatePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\UpdatePriceListServiceInterface;
use Modules\Product\Application\Contracts\CreateProductServiceInterface;
use Modules\Product\Application\Contracts\RebuildProductSearchProjectionServiceInterface;
use Modules\Product\Application\Contracts\SearchProductsServiceInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductSearchProjectionModel;
use Modules\Warehouse\Application\Contracts\CreateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseServiceInterface;
use Tests\TestCase;

class ProductSearchProjectionUnifiedQueryModelIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 77;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('tenants')->insert([
            'id' => $this->tenantId,
            'name' => 'Unified Search Tenant',
            'slug' => 'unified-search-tenant-'.time(),
            'plan' => 'free',
            'tenant_plan_id' => null,
            'status' => 'active',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('units_of_measure')->insert([
            [
                'id' => 1,
                'tenant_id' => $this->tenantId,
                'name' => 'Piece',
                'symbol' => 'pcs',
                'type' => 'unit',
                'is_base' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'tenant_id' => $this->tenantId,
                'name' => 'Box',
                'symbol' => 'box',
                'type' => 'unit',
                'is_base' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('currencies')->insert([
            'id' => 1,
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_projection_contains_uom_and_default_pricing_and_supports_context_filters(): void
    {
        $createProductService = app(CreateProductServiceInterface::class);
        $rebuildProjectionService = app(RebuildProductSearchProjectionServiceInterface::class);
        $searchService = app(SearchProductsServiceInterface::class);

        $product = $createProductService->execute([
            'tenant_id' => $this->tenantId,
            'name' => 'Unified Search Product',
            'sku' => 'UNIFIED-001',
            'category_id' => null,
            'brand_id' => null,
            'base_uom_id' => 1,
            'sales_uom_id' => 2,
            'purchase_uom_id' => 1,
            'description' => 'Projection model test product',
            'is_active' => true,
            'metadata' => [],
        ]);

        DB::table('price_lists')->insert([
            'id' => 100,
            'tenant_id' => $this->tenantId,
            'name' => 'Default Sales',
            'type' => 'sales',
            'currency_id' => 1,
            'is_default' => true,
            'valid_from' => now()->toDateString(),
            'valid_to' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('price_list_items')->insert([
            'tenant_id' => $this->tenantId,
            'price_list_id' => 100,
            'product_id' => $product->getId(),
            'variant_id' => null,
            'uom_id' => 2,
            'min_quantity' => '1.000000',
            'price' => '12.000000',
            'discount_pct' => '10.000000',
            'valid_from' => now()->toDateString(),
            'valid_to' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rebuildProjectionService->execute($this->tenantId);

        $projection = ProductSearchProjectionModel::query()
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->whereNull('variant_id')
            ->firstOrFail();

        $this->assertEquals('Piece', $projection->base_uom_name);
        $this->assertEquals('pcs', $projection->base_uom_symbol);
        $this->assertEquals('Box', $projection->sales_uom_name);
        $this->assertEquals('box', $projection->sales_uom_symbol);
        $this->assertEquals('10.800000', (string) $projection->default_sales_unit_price);
        $this->assertEquals(1, $projection->default_sales_currency_id);
        $this->assertEquals(2, $projection->default_sales_price_uom_id);

        $results = $searchService->execute([
            'tenant_id' => $this->tenantId,
            'q' => 'Unified Search',
            'uom_id' => 2,
            'price_context' => 'sales',
            'currency_id' => 1,
            'min_price' => '10.000000',
            'max_price' => '11.000000',
        ]);

        $this->assertEquals(1, $results->total());
        $row = $results->items()[0];
        $this->assertEquals('UNIFIED-001', $row->product_sku);
        $this->assertEquals('10.800000', (string) $row->default_sales_unit_price);
    }

    public function test_price_list_item_mutations_automatically_refresh_projection_snapshot(): void
    {
        $createProductService = app(CreateProductServiceInterface::class);
        $createPriceListItemService = app(CreatePriceListItemServiceInterface::class);
        $updatePriceListItemService = app(UpdatePriceListItemServiceInterface::class);
        $deletePriceListItemService = app(DeletePriceListItemServiceInterface::class);

        $product = $createProductService->execute([
            'tenant_id' => $this->tenantId,
            'name' => 'Dynamic Price Product',
            'sku' => 'DYN-001',
            'category_id' => null,
            'brand_id' => null,
            'base_uom_id' => 1,
            'sales_uom_id' => 2,
            'purchase_uom_id' => 1,
            'description' => 'Pricing mutation test product',
            'is_active' => true,
            'metadata' => [],
        ]);

        DB::table('price_lists')->insert([
            'id' => 200,
            'tenant_id' => $this->tenantId,
            'name' => 'Auto Refresh Sales',
            'type' => 'sales',
            'currency_id' => 1,
            'is_default' => true,
            'valid_from' => now()->toDateString(),
            'valid_to' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $projectionBefore = ProductSearchProjectionModel::query()
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->whereNull('variant_id')
            ->firstOrFail();
        $this->assertNull($projectionBefore->default_sales_unit_price);

        $createdItem = $createPriceListItemService->execute([
            'price_list_id' => 200,
            'product_id' => $product->getId(),
            'variant_id' => null,
            'uom_id' => 2,
            'min_quantity' => '1.000000',
            'price' => '15.000000',
            'discount_pct' => '10.000000',
            'valid_from' => now()->toDateString(),
            'valid_to' => null,
        ]);

        $projectionAfterCreate = ProductSearchProjectionModel::query()
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->whereNull('variant_id')
            ->firstOrFail();
        $this->assertEquals('13.500000', (string) $projectionAfterCreate->default_sales_unit_price);

        $updatePriceListItemService->execute([
            'id' => $createdItem->getId(),
            'price_list_id' => 200,
            'product_id' => $product->getId(),
            'variant_id' => null,
            'uom_id' => 2,
            'min_quantity' => '1.000000',
            'price' => '20.000000',
            'discount_pct' => '5.000000',
            'valid_from' => now()->toDateString(),
            'valid_to' => null,
        ]);

        $projectionAfterUpdate = ProductSearchProjectionModel::query()
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->whereNull('variant_id')
            ->firstOrFail();
        $this->assertEquals('19.000000', (string) $projectionAfterUpdate->default_sales_unit_price);

        $deletePriceListItemService->execute([
            'id' => $createdItem->getId(),
        ]);

        $projectionAfterDelete = ProductSearchProjectionModel::query()
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->whereNull('variant_id')
            ->firstOrFail();
        $this->assertNull($projectionAfterDelete->default_sales_unit_price);
    }

    public function test_price_list_state_mutations_automatically_refresh_projection_snapshot(): void
    {
        $createProductService = app(CreateProductServiceInterface::class);
        $rebuildProjectionService = app(RebuildProductSearchProjectionServiceInterface::class);
        $updatePriceListService = app(UpdatePriceListServiceInterface::class);
        $deletePriceListService = app(DeletePriceListServiceInterface::class);

        $product = $createProductService->execute([
            'tenant_id' => $this->tenantId,
            'name' => 'Price List State Product',
            'sku' => 'STATE-001',
            'category_id' => null,
            'brand_id' => null,
            'base_uom_id' => 1,
            'sales_uom_id' => 2,
            'purchase_uom_id' => 1,
            'description' => 'Price list state test product',
            'is_active' => true,
            'metadata' => [],
        ]);

        DB::table('price_lists')->insert([
            'id' => 300,
            'tenant_id' => $this->tenantId,
            'name' => 'Stateful Sales',
            'type' => 'sales',
            'currency_id' => 1,
            'is_default' => true,
            'valid_from' => now()->toDateString(),
            'valid_to' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('price_list_items')->insert([
            'id' => 301,
            'tenant_id' => $this->tenantId,
            'price_list_id' => 300,
            'product_id' => $product->getId(),
            'variant_id' => null,
            'uom_id' => 2,
            'min_quantity' => '1.000000',
            'price' => '30.000000',
            'discount_pct' => '0.000000',
            'valid_from' => now()->toDateString(),
            'valid_to' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rebuildProjectionService->execute($this->tenantId);

        $projectionBefore = ProductSearchProjectionModel::query()
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->whereNull('variant_id')
            ->firstOrFail();
        $this->assertEquals('30.000000', (string) $projectionBefore->default_sales_unit_price);

        $updatePriceListService->execute([
            'id' => 300,
            'tenant_id' => $this->tenantId,
            'name' => 'Stateful Sales',
            'type' => 'sales',
            'currency_id' => 1,
            'is_default' => true,
            'valid_from' => now()->toDateString(),
            'valid_to' => null,
            'is_active' => false,
        ]);

        $projectionAfterDeactivate = ProductSearchProjectionModel::query()
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->whereNull('variant_id')
            ->firstOrFail();
        $this->assertNull($projectionAfterDeactivate->default_sales_unit_price);

        DB::table('price_lists')->where('id', 300)->update([
            'is_active' => true,
            'updated_at' => now(),
        ]);

        $rebuildProjectionService->execute($this->tenantId);

        $projectionAfterRebuild = ProductSearchProjectionModel::query()
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->whereNull('variant_id')
            ->firstOrFail();
        $this->assertEquals('30.000000', (string) $projectionAfterRebuild->default_sales_unit_price);

        $deletePriceListService->execute([
            'id' => 300,
        ]);

        $projectionAfterDelete = ProductSearchProjectionModel::query()
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->whereNull('variant_id')
            ->firstOrFail();
        $this->assertNull($projectionAfterDelete->default_sales_unit_price);
    }

    public function test_stock_movement_refresh_updates_batch_lot_and_warehouse_projection_data(): void
    {
        $createProductService = app(CreateProductServiceInterface::class);
        $searchService = app(SearchProductsServiceInterface::class);
        $createWarehouseService = app(CreateWarehouseServiceInterface::class);
        $createWarehouseLocationService = app(CreateWarehouseLocationServiceInterface::class);
        $recordStockMovementService = app(RecordStockMovementServiceInterface::class);

        $product = $createProductService->execute([
            'tenant_id' => $this->tenantId,
            'name' => 'Warehouse Batch Product',
            'sku' => 'WH-BATCH-001',
            'category_id' => null,
            'brand_id' => null,
            'base_uom_id' => 1,
            'sales_uom_id' => 1,
            'purchase_uom_id' => 1,
            'description' => 'Warehouse and batch search test product',
            'is_batch_tracked' => true,
            'is_active' => true,
            'metadata' => [],
        ]);

        $warehouse = $createWarehouseService->execute([
            'tenant_id' => $this->tenantId,
            'name' => 'Projection Warehouse',
            'code' => 'PRJ-WH',
            'is_default' => true,
        ]);

        $location = $createWarehouseLocationService->execute([
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $warehouse->getId(),
            'name' => 'Bin A1',
            'code' => 'BIN-A1',
            'type' => 'bin',
        ]);

        DB::table('batches')->insert([
            'id' => 501,
            'tenant_id' => $this->tenantId,
            'product_id' => $product->getId(),
            'variant_id' => null,
            'batch_number' => 'BATCH-RED-001',
            'lot_number' => 'LOT-RED-001',
            'manufacture_date' => null,
            'expiry_date' => null,
            'received_date' => now()->toDateString(),
            'supplier_id' => null,
            'status' => 'active',
            'notes' => null,
            'metadata' => null,
            'sales_price' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $recordStockMovementService->execute([
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $warehouse->getId(),
            'product_id' => $product->getId(),
            'batch_id' => 501,
            'to_location_id' => $location->getId(),
            'movement_type' => 'receipt',
            'uom_id' => 1,
            'quantity' => '7.000000',
            'unit_cost' => '4.500000',
        ]);

        $projection = ProductSearchProjectionModel::query()
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $product->getId())
            ->whereNull('variant_id')
            ->firstOrFail();

        $this->assertStringContainsString('BATCH-RED-001', (string) $projection->batch_lot_text);
        $this->assertStringContainsString('LOT-RED-001', (string) $projection->batch_lot_text);
        $this->assertEquals('7.000000', (string) $projection->stock_available);
        $this->assertIsArray($projection->stock_by_warehouse_json);
        $this->assertCount(1, $projection->stock_by_warehouse_json);
        $this->assertEquals($warehouse->getId(), $projection->stock_by_warehouse_json[0]['warehouse_id']);
        $this->assertEquals('7.000000', $projection->stock_by_warehouse_json[0]['available']);

        $warehouseScopedResults = $searchService->execute([
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $warehouse->getId(),
            'warehouse_in_stock' => true,
            'batch' => 'BATCH-RED-001',
        ]);

        $this->assertEquals(1, $warehouseScopedResults->total());
        $row = $warehouseScopedResults->items()[0];
        $this->assertEquals('WH-BATCH-001', $row->product_sku);
    }
}
