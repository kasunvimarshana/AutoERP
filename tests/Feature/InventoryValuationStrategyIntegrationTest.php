<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Application\Contracts\ManageValuationConfigServiceInterface;
use Modules\Inventory\Application\Contracts\ValuationEngineServiceInterface;
use Modules\Inventory\Application\DTOs\CostLayerInboundData;
use Modules\Inventory\Domain\Exceptions\InsufficientAvailableStockException;
use Tests\TestCase;

class InventoryValuationStrategyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 90;

    private int $productId = 1001;

    private int $locationId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant($this->tenantId);
        $this->seedReferenceData($this->tenantId);
        $this->locationId = $this->seedWarehouseAndLocation($this->tenantId);
    }

    // =========================================================================
    // FIFO Tests
    // =========================================================================

    public function test_fifo_inbound_creates_separate_cost_layers(): void
    {
        /** @var ValuationEngineServiceInterface $engine */
        $engine = app(ValuationEngineServiceInterface::class);

        $engine->processInbound($this->makeInboundDTO('fifo', '10.000000', '5.000000', '2024-01-01'));
        $engine->processInbound($this->makeInboundDTO('fifo', '20.000000', '8.000000', '2024-02-01'));

        $this->assertDatabaseCount('inventory_cost_layers', 2);
        $this->assertDatabaseHas('inventory_cost_layers', ['unit_cost' => '5.000000', 'quantity_in' => '10.000000']);
        $this->assertDatabaseHas('inventory_cost_layers', ['unit_cost' => '8.000000', 'quantity_in' => '20.000000']);
    }

    public function test_fifo_outbound_consumes_oldest_layer_first(): void
    {
        /** @var ValuationEngineServiceInterface $engine */
        $engine = app(ValuationEngineServiceInterface::class);

        // Layer 1: qty=10, cost=5 (oldest)
        $engine->processInbound($this->makeInboundDTO('fifo', '10.000000', '5.000000', '2024-01-01'));
        // Layer 2: qty=20, cost=8 (newest)
        $engine->processInbound($this->makeInboundDTO('fifo', '20.000000', '8.000000', '2024-02-01'));

        $touched = $engine->processOutbound(
            tenantId: $this->tenantId,
            productId: $this->productId,
            variantId: null,
            locationId: $this->locationId,
            quantity: '8.000000',
            valuationMethod: 'fifo',
        );

        // Only layer1 consumed (has 10 units, we need 8)
        $this->assertCount(1, $touched);
        $this->assertSame('5.000000', $touched[0]->getUnitCost());
        $this->assertSame('2.000000', $touched[0]->getQuantityRemaining());
        $this->assertFalse($touched[0]->isClosed());
    }

    public function test_fifo_outbound_spans_multiple_layers(): void
    {
        /** @var ValuationEngineServiceInterface $engine */
        $engine = app(ValuationEngineServiceInterface::class);

        // Layer 1: qty=3 (oldest), Layer 2: qty=20 (newest)
        $engine->processInbound($this->makeInboundDTO('fifo', '3.000000', '5.000000', '2024-01-01'));
        $engine->processInbound($this->makeInboundDTO('fifo', '20.000000', '8.000000', '2024-02-01'));

        $touched = $engine->processOutbound(
            tenantId: $this->tenantId,
            productId: $this->productId,
            variantId: null,
            locationId: $this->locationId,
            quantity: '15.000000',
            valuationMethod: 'fifo',
        );

        $this->assertCount(2, $touched);
        $this->assertSame('0.000000', $touched[0]->getQuantityRemaining());
        $this->assertTrue($touched[0]->isClosed());
        $this->assertSame('8.000000', $touched[1]->getQuantityRemaining());
    }

    // =========================================================================
    // LIFO Tests
    // =========================================================================

    public function test_lifo_outbound_consumes_newest_layer_first(): void
    {
        /** @var ValuationEngineServiceInterface $engine */
        $engine = app(ValuationEngineServiceInterface::class);

        // Layer 1: qty=10, cost=5 (oldest); Layer 2: qty=20, cost=8 (newest)
        $engine->processInbound($this->makeInboundDTO('lifo', '10.000000', '5.000000', '2024-01-01'));
        $engine->processInbound($this->makeInboundDTO('lifo', '20.000000', '8.000000', '2024-02-01'));

        $touched = $engine->processOutbound(
            tenantId: $this->tenantId,
            productId: $this->productId,
            variantId: null,
            locationId: $this->locationId,
            quantity: '5.000000',
            valuationMethod: 'lifo',
        );

        // LIFO takes from newest (cost=8, qty=20), consumes 5
        $this->assertCount(1, $touched);
        $this->assertSame('8.000000', $touched[0]->getUnitCost());
        $this->assertSame('15.000000', $touched[0]->getQuantityRemaining());
    }

    // =========================================================================
    // Weighted Average Tests
    // =========================================================================

    public function test_weighted_average_merges_running_layer_on_receipt(): void
    {
        /** @var ValuationEngineServiceInterface $engine */
        $engine = app(ValuationEngineServiceInterface::class);

        // Receipt 1: qty=10, cost=100 per unit
        $layer1 = $engine->processInbound($this->makeInboundDTO('weighted_average', '10.000000', '100.000000', '2024-01-01'));
        // Receipt 2: qty=10, cost=200 per unit
        $layer2 = $engine->processInbound($this->makeInboundDTO('weighted_average', '10.000000', '200.000000', '2024-02-01'));

        // Same layer updated (weighted average merges into single running layer)
        $this->assertSame($layer1->getId(), $layer2->getId());
        $this->assertSame('20.000000', $layer2->getQuantityRemaining());

        // avg = (10*100 + 10*200) / 20 = 150
        $expectedAvg = bcdiv(
            bcadd(bcmul('100.000000', '10.000000', 6), bcmul('200.000000', '10.000000', 6), 6),
            '20.000000',
            6,
        );
        $this->assertSame($expectedAvg, $layer2->getUnitCost());
    }

    public function test_weighted_average_outbound_uses_running_cost(): void
    {
        /** @var ValuationEngineServiceInterface $engine */
        $engine = app(ValuationEngineServiceInterface::class);

        $engine->processInbound($this->makeInboundDTO('weighted_average', '10.000000', '100.000000', '2024-01-01'));
        $engine->processInbound($this->makeInboundDTO('weighted_average', '10.000000', '200.000000', '2024-02-01'));

        $touched = $engine->processOutbound(
            tenantId: $this->tenantId,
            productId: $this->productId,
            variantId: null,
            locationId: $this->locationId,
            quantity: '5.000000',
            valuationMethod: 'weighted_average',
        );

        $this->assertCount(1, $touched);
        $this->assertSame('15.000000', $touched[0]->getQuantityRemaining());
    }

    // =========================================================================
    // Standard Cost Tests
    // =========================================================================

    public function test_standard_cost_inbound_merges_at_standard_cost(): void
    {
        /** @var ValuationEngineServiceInterface $engine */
        $engine = app(ValuationEngineServiceInterface::class);

        $dto1 = new CostLayerInboundData(
            tenantId: $this->tenantId,
            productId: $this->productId,
            variantId: null,
            batchId: null,
            locationId: $this->locationId,
            valuationMethod: 'standard',
            layerDate: '2024-01-01',
            quantity: '10.000000',
            unitCost: '99.000000',
            referenceType: null,
            referenceId: null,
        );
        $layer1 = $engine->processInbound($dto1);

        $dto2 = new CostLayerInboundData(
            tenantId: $this->tenantId,
            productId: $this->productId,
            variantId: null,
            batchId: null,
            locationId: $this->locationId,
            valuationMethod: 'standard',
            layerDate: '2024-02-01',
            quantity: '5.000000',
            unitCost: '99.000000',
            referenceType: null,
            referenceId: null,
        );
        $layer2 = $engine->processInbound($dto2);

        $this->assertSame($layer1->getId(), $layer2->getId());
        $this->assertSame('15.000000', $layer2->getQuantityRemaining());
    }

    // =========================================================================
    // Insufficient stock
    // =========================================================================

    public function test_outbound_throws_when_insufficient_stock(): void
    {
        /** @var ValuationEngineServiceInterface $engine */
        $engine = app(ValuationEngineServiceInterface::class);

        $engine->processInbound($this->makeInboundDTO('fifo', '5.000000', '5.000000', '2024-01-01'));

        $this->expectException(InsufficientAvailableStockException::class);

        $engine->processOutbound(
            tenantId: $this->tenantId,
            productId: $this->productId,
            variantId: null,
            locationId: $this->locationId,
            quantity: '10.000000',
            valuationMethod: 'fifo',
        );
    }

    // =========================================================================
    // Valuation Config resolution
    // =========================================================================

    public function test_resolve_valuation_method_falls_back_to_fifo_when_no_config(): void
    {
        /** @var ValuationEngineServiceInterface $engine */
        $engine = app(ValuationEngineServiceInterface::class);

        $method = $engine->resolveValuationMethod(
            tenantId: $this->tenantId,
            productId: $this->productId,
        );

        $this->assertSame('fifo', $method);
    }

    public function test_product_scope_config_overrides_tenant_default(): void
    {
        /** @var ManageValuationConfigServiceInterface $mgr */
        $mgr = app(ManageValuationConfigServiceInterface::class);

        // Tenant-level default: LIFO
        $mgr->create([
            'tenant_id' => $this->tenantId,
            'valuation_method' => 'lifo',
            'allocation_strategy' => 'lifo',
            'is_active' => true,
        ]);

        // Product-level override: WA
        $mgr->create([
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'valuation_method' => 'weighted_average',
            'allocation_strategy' => 'fefo',
            'is_active' => true,
        ]);

        /** @var ValuationEngineServiceInterface $engine */
        $engine = app(ValuationEngineServiceInterface::class);

        $method = $engine->resolveValuationMethod(
            tenantId: $this->tenantId,
            productId: $this->productId,
        );

        $this->assertSame('weighted_average', $method);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * @param  string  $quantity  quantity of the layer
     * @param  string  $unitCost  cost per unit
     */
    private function makeInboundDTO(
        string $method,
        string $quantity,
        string $unitCost,
        string $layerDate,
    ): CostLayerInboundData {
        return new CostLayerInboundData(
            tenantId: $this->tenantId,
            productId: $this->productId,
            variantId: null,
            batchId: null,
            locationId: $this->locationId,
            valuationMethod: $method,
            layerDate: $layerDate,
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
            'name' => 'Tenant '.$tenantId,
            'slug' => 'tenant-'.$tenantId,
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

    private function seedReferenceData(int $tenantId): void
    {
        DB::table('units_of_measure')->insert([
            'id' => 5001,
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
            'id' => $this->productId,
            'tenant_id' => $tenantId,
            'category_id' => null,
            'brand_id' => null,
            'org_unit_id' => null,
            'type' => 'physical',
            'name' => 'Test Product',
            'slug' => 'test-product-val',
            'sku' => 'SKU-VAL-1001',
            'description' => null,
            'base_uom_id' => 5001,
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
            'name' => 'Valuation WH',
            'code' => 'VAL-WH',
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
            'name' => 'Main Rack',
            'code' => 'VAL-MAIN',
            'type' => 'rack',
            'path' => 'VAL-MAIN',
            'depth' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
