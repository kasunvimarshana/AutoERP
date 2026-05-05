<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Application\Contracts\AllocationEngineServiceInterface;
use Modules\Inventory\Application\Contracts\ValuationEngineServiceInterface;
use Modules\Inventory\Application\DTOs\AllocationRequestData;
use Modules\Inventory\Application\DTOs\CostLayerInboundData;
use Modules\Inventory\Domain\Exceptions\InsufficientAvailableStockException;
use Tests\TestCase;

class InventoryAllocationStrategyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 91;

    private int $productId = 2001;

    private int $locationId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant($this->tenantId);
        $this->seedProduct($this->tenantId, $this->productId);
        $this->locationId = $this->seedWarehouseAndLocation($this->tenantId);
    }

    public function test_fifo_allocation_selects_oldest_layer(): void
    {
        /** @var ValuationEngineServiceInterface $valuation */
        $valuation = app(ValuationEngineServiceInterface::class);

        // Layer 1: qty=10, cost=5 (oldest); Layer 2: qty=20, cost=8 (newest)
        $valuation->processInbound($this->makeDTO('fifo', '10.000000', '5.000000', '2024-01-01'));
        $valuation->processInbound($this->makeDTO('fifo', '20.000000', '8.000000', '2024-02-01'));

        /** @var AllocationEngineServiceInterface $alloc */
        $alloc = app(AllocationEngineServiceInterface::class);

        // Request 6 units — layer1 has 10, so only 1 line is needed
        $result = $alloc->allocate(new AllocationRequestData(
            tenantId: $this->tenantId,
            productId: $this->productId,
            variantId: null,
            locationId: $this->locationId,
            requiredQuantity: '6.000000',
            allocationStrategy: 'fifo',
        ));

        $this->assertTrue($result->isFullyAllocated());
        $this->assertSame('6.000000', $result->getTotalAllocated());

        $lines = $result->getLines();
        $this->assertCount(1, $lines);
        $this->assertSame('5.000000', $lines[0]->getUnitCost());
        $this->assertSame('6.000000', $lines[0]->getAllocatedQuantity());
    }

    public function test_lifo_allocation_selects_newest_layer(): void
    {
        /** @var ValuationEngineServiceInterface $valuation */
        $valuation = app(ValuationEngineServiceInterface::class);

        // Layer 1: qty=10, cost=5 (oldest); Layer 2: qty=20, cost=8 (newest)
        $valuation->processInbound($this->makeDTO('lifo', '10.000000', '5.000000', '2024-01-01'));
        $valuation->processInbound($this->makeDTO('lifo', '20.000000', '8.000000', '2024-02-01'));

        /** @var AllocationEngineServiceInterface $alloc */
        $alloc = app(AllocationEngineServiceInterface::class);

        $result = $alloc->allocate(new AllocationRequestData(
            tenantId: $this->tenantId,
            productId: $this->productId,
            variantId: null,
            locationId: $this->locationId,
            requiredQuantity: '5.000000',
            allocationStrategy: 'lifo',
        ));

        $this->assertTrue($result->isFullyAllocated());
        $lines = $result->getLines();
        $this->assertCount(1, $lines);
        $this->assertSame('8.000000', $lines[0]->getUnitCost());
        $this->assertSame('5.000000', $lines[0]->getAllocatedQuantity());
    }

    public function test_nearest_bin_prefers_preferred_location(): void
    {
        /** @var ValuationEngineServiceInterface $valuation */
        $valuation = app(ValuationEngineServiceInterface::class);

        $altLocationId = $this->seedAltLocation($this->tenantId);

        // Main location: qty=10, cost=5
        $valuation->processInbound($this->makeDTO('fifo', '10.000000', '5.000000', '2024-01-01'));

        // Alt location: qty=10, cost=3
        $valuation->processInbound(new CostLayerInboundData(
            tenantId: $this->tenantId,
            productId: $this->productId,
            variantId: null,
            batchId: null,
            locationId: $altLocationId,
            valuationMethod: 'fifo',
            layerDate: '2024-01-02',
            quantity: '10.000000',
            unitCost: '3.000000',
            referenceType: null,
            referenceId: null,
        ));

        /** @var AllocationEngineServiceInterface $alloc */
        $alloc = app(AllocationEngineServiceInterface::class);

        $result = $alloc->allocate(new AllocationRequestData(
            tenantId: $this->tenantId,
            productId: $this->productId,
            variantId: null,
            locationId: $this->locationId,
            requiredQuantity: '5.000000',
            allocationStrategy: 'nearest_bin',
            context: ['preferred_location_id' => $this->locationId],
        ));

        $this->assertTrue($result->isFullyAllocated());
        $lines = $result->getLines();
        $this->assertCount(1, $lines);
        $this->assertSame('5.000000', $lines[0]->getUnitCost());
        $this->assertSame($this->locationId, $lines[0]->getLocationId());
    }

    public function test_manual_allocation_uses_specified_layer_ids(): void
    {
        /** @var ValuationEngineServiceInterface $valuation */
        $valuation = app(ValuationEngineServiceInterface::class);

        // Layer 1: qty=10, cost=5; Layer 2: qty=20, cost=8
        $valuation->processInbound($this->makeDTO('fifo', '10.000000', '5.000000', '2024-01-01'));
        $layer2 = $valuation->processInbound($this->makeDTO('fifo', '20.000000', '8.000000', '2024-02-01'));

        /** @var AllocationEngineServiceInterface $alloc */
        $alloc = app(AllocationEngineServiceInterface::class);

        // Manually pick layer2 (newer, more expensive) instead of FIFO order
        $result = $alloc->allocate(new AllocationRequestData(
            tenantId: $this->tenantId,
            productId: $this->productId,
            variantId: null,
            locationId: $this->locationId,
            requiredQuantity: '5.000000',
            allocationStrategy: 'manual',
            context: ['manual_layer_ids' => [$layer2->getId()]],
        ));

        $this->assertTrue($result->isFullyAllocated());
        $lines = $result->getLines();
        $this->assertCount(1, $lines);
        $this->assertSame('8.000000', $lines[0]->getUnitCost());
    }

    public function test_allocation_throws_when_insufficient(): void
    {
        /** @var ValuationEngineServiceInterface $valuation */
        $valuation = app(ValuationEngineServiceInterface::class);

        // Only 3 units available
        $valuation->processInbound($this->makeDTO('fifo', '3.000000', '5.000000', '2024-01-01'));

        /** @var AllocationEngineServiceInterface $alloc */
        $alloc = app(AllocationEngineServiceInterface::class);

        $this->expectException(InsufficientAvailableStockException::class);

        $alloc->allocate(new AllocationRequestData(
            tenantId: $this->tenantId,
            productId: $this->productId,
            variantId: null,
            locationId: $this->locationId,
            requiredQuantity: '10.000000',
            allocationStrategy: 'fifo',
        ));
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeDTO(string $method, string $quantity, string $unitCost, string $date): CostLayerInboundData
    {
        return new CostLayerInboundData(
            tenantId: $this->tenantId,
            productId: $this->productId,
            variantId: null,
            batchId: null,
            locationId: $this->locationId,
            valuationMethod: $method,
            layerDate: $date,
            quantity: $quantity,
            unitCost: $unitCost,
            referenceType: null,
            referenceId: null,
        );
    }

    private function seedTenant(int $tenantId): void
    {
        DB::table('tenants')->insert([
            'id' => $tenantId,
            'name' => 'Alloc Tenant',
            'slug' => 'alloc-tenant-'.$tenantId,
            'domain' => null,
            'logo_path' => null,
            'database_config' => null,
            'mail_config' => null,
            'cache_config' => null,
            'queue_config' => null,
            'feature_flags' => null,
            'api_keys' => null,
            'settings' => null,
            'plan' => 'free',
            'tenant_plan_id' => null,
            'status' => 'active',
            'active' => true,
            'trial_ends_at' => null,
            'subscription_ends_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function seedProduct(int $tenantId, int $productId): void
    {
        DB::table('units_of_measure')->insert([
            'id' => 5002,
            'tenant_id' => $tenantId,
            'name' => 'Each',
            'symbol' => 'ea',
            'type' => 'unit',
            'is_base' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('products')->insert([
            'id' => $productId,
            'tenant_id' => $tenantId,
            'category_id' => null,
            'brand_id' => null,
            'org_unit_id' => null,
            'type' => 'physical',
            'name' => 'Alloc Product',
            'slug' => 'alloc-product-'.$productId,
            'sku' => 'SKU-ALLOC-'.$productId,
            'description' => null,
            'base_uom_id' => 5002,
            'purchase_uom_id' => null,
            'sales_uom_id' => null,
            'tax_group_id' => null,
            'uom_conversion_factor' => '1.0000000000',
            'is_batch_tracked' => false,
            'is_lot_tracked' => false,
            'is_serial_tracked' => false,
            'valuation_method' => 'fifo',
            'standard_cost' => '10.000000',
            'income_account_id' => null,
            'cogs_account_id' => null,
            'inventory_account_id' => null,
            'expense_account_id' => null,
            'is_active' => true,
            'image_path' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function seedWarehouseAndLocation(int $tenantId): int
    {
        $warehouseId = DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Alloc WH',
            'code' => 'ALLOC-WH',
            'type' => 'standard',
            'is_default' => true,
            'is_active' => true,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('warehouse_locations')->insertGetId([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouseId,
            'parent_id' => null,
            'name' => 'Main Bin',
            'code' => 'ALLOC-MAIN',
            'type' => 'bin',
            'path' => 'ALLOC-MAIN',
            'depth' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedAltLocation(int $tenantId): int
    {
        $warehouseId = DB::table('warehouses')
            ->where('tenant_id', $tenantId)
            ->value('id');

        return DB::table('warehouse_locations')->insertGetId([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouseId,
            'parent_id' => null,
            'name' => 'Alt Bin',
            'code' => 'ALLOC-ALT',
            'type' => 'bin',
            'path' => 'ALLOC-ALT',
            'depth' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
