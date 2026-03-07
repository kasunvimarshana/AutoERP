<?php

namespace Tests\Unit;

use App\Models\InventoryItem;
use App\Models\InventoryReservation;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;
    private Warehouse        $warehouse;
    private string           $tenantId = 'tenant-test';

    protected function setUp(): void
    {
        parent::setUp();
        $this->service   = new InventoryService();
        $this->warehouse = Warehouse::create([
            'tenant_id' => $this->tenantId,
            'name'      => 'Test Warehouse',
            'code'      => 'WH-TEST',
            'is_active' => true,
        ]);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function createProduct(string $sku, int $availableQty): array
    {
        $product = Product::create([
            'tenant_id'  => $this->tenantId,
            'sku'        => $sku,
            'name'       => "Product {$sku}",
            'category'   => 'Test',
            'unit_price' => 9.99,
            'currency'   => 'USD',
            'is_active'  => true,
        ]);

        $item = InventoryItem::create([
            'product_id'         => $product->id,
            'tenant_id'          => $this->tenantId,
            'warehouse_id'       => $this->warehouse->id,
            'quantity_available' => $availableQty,
            'quantity_reserved'  => 0,
            'quantity_sold'      => 0,
            'reorder_level'      => 5,
            'max_stock_level'    => 1000,
            'unit_of_measure'    => 'unit',
        ]);

        return ['product' => $product, 'item' => $item];
    }

    // =========================================================================
    // 1. testReserveStockSuccess
    // =========================================================================

    /** @test */
    public function testReserveStockSuccess(): void
    {
        $this->createProduct('SKU-A', 100);
        $this->createProduct('SKU-B', 50);

        $result = $this->service->reserveStock(
            sagaId:   'saga-001',
            orderId:  'order-001',
            items: [
                ['sku' => 'SKU-A', 'quantity' => 10, 'warehouse_id' => $this->warehouse->id],
                ['sku' => 'SKU-B', 'quantity' => 5,  'warehouse_id' => $this->warehouse->id],
            ],
            tenantId: $this->tenantId,
        );

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['reservations']);
        $this->assertEmpty($result['errors']);

        // Verify quantities were updated.
        $itemA = InventoryItem::where('tenant_id', $this->tenantId)
            ->whereHas('product', fn ($q) => $q->where('sku', 'SKU-A'))
            ->first();
        $this->assertEquals(90, $itemA->quantity_available);
        $this->assertEquals(10, $itemA->quantity_reserved);

        $itemB = InventoryItem::where('tenant_id', $this->tenantId)
            ->whereHas('product', fn ($q) => $q->where('sku', 'SKU-B'))
            ->first();
        $this->assertEquals(45, $itemB->quantity_available);
        $this->assertEquals(5, $itemB->quantity_reserved);

        // Verify reservations were created in DB.
        $this->assertDatabaseHas('inventory_reservations', [
            'saga_id'  => 'saga-001',
            'order_id' => 'order-001',
            'quantity' => 10,
            'status'   => 'pending',
        ]);
    }

    // =========================================================================
    // 2. testReserveStockFailsWhenInsufficientStock
    // =========================================================================

    /** @test */
    public function testReserveStockFailsWhenInsufficientStock(): void
    {
        $this->createProduct('SKU-C', 5);

        $result = $this->service->reserveStock(
            sagaId:   'saga-002',
            orderId:  'order-002',
            items: [
                ['sku' => 'SKU-C', 'quantity' => 10, 'warehouse_id' => $this->warehouse->id],
            ],
            tenantId: $this->tenantId,
        );

        $this->assertFalse($result['success']);
        $this->assertEmpty($result['reservations']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Insufficient stock', $result['errors'][0]);

        // No reservation should have been persisted.
        $this->assertDatabaseMissing('inventory_reservations', [
            'saga_id' => 'saga-002',
        ]);

        // Quantity should be unchanged.
        $item = InventoryItem::where('tenant_id', $this->tenantId)
            ->whereHas('product', fn ($q) => $q->where('sku', 'SKU-C'))
            ->first();
        $this->assertEquals(5, $item->quantity_available);
        $this->assertEquals(0, $item->quantity_reserved);
    }

    // =========================================================================
    // 3. testAtomicReservationRollback
    // =========================================================================

    /** @test */
    public function testAtomicReservationRollback(): void
    {
        $this->createProduct('SKU-D', 100);
        $this->createProduct('SKU-E', 3); // Not enough for quantity=10.

        $result = $this->service->reserveStock(
            sagaId:   'saga-003',
            orderId:  'order-003',
            items: [
                ['sku' => 'SKU-D', 'quantity' => 10, 'warehouse_id' => $this->warehouse->id],
                ['sku' => 'SKU-E', 'quantity' => 10, 'warehouse_id' => $this->warehouse->id],
            ],
            tenantId: $this->tenantId,
        );

        $this->assertFalse($result['success']);
        $this->assertEmpty($result['reservations']);

        // CRITICAL: SKU-D must NOT have been reserved since SKU-E failed validation.
        $itemD = InventoryItem::where('tenant_id', $this->tenantId)
            ->whereHas('product', fn ($q) => $q->where('sku', 'SKU-D'))
            ->first();
        $this->assertEquals(100, $itemD->quantity_available, 'SKU-D should be fully rolled back.');
        $this->assertEquals(0, $itemD->quantity_reserved, 'SKU-D reserved qty should remain 0.');

        // No reservations in DB for this saga.
        $this->assertDatabaseMissing('inventory_reservations', [
            'saga_id' => 'saga-003',
        ]);
    }

    // =========================================================================
    // 4. testReleaseStockRestoresQuantity
    // =========================================================================

    /** @test */
    public function testReleaseStockRestoresQuantity(): void
    {
        $this->createProduct('SKU-F', 50);

        // First reserve.
        $reserveResult = $this->service->reserveStock(
            sagaId:   'saga-004',
            orderId:  'order-004',
            items: [
                ['sku' => 'SKU-F', 'quantity' => 20, 'warehouse_id' => $this->warehouse->id],
            ],
            tenantId: $this->tenantId,
        );
        $this->assertTrue($reserveResult['success']);

        $itemF = InventoryItem::where('tenant_id', $this->tenantId)
            ->whereHas('product', fn ($q) => $q->where('sku', 'SKU-F'))
            ->first();
        $this->assertEquals(30, $itemF->quantity_available);
        $this->assertEquals(20, $itemF->quantity_reserved);

        // Now release.
        $this->service->releaseStock('saga-004', 'order-004');

        $itemF->refresh();
        $this->assertEquals(50, $itemF->quantity_available, 'Available should be fully restored.');
        $this->assertEquals(0, $itemF->quantity_reserved, 'Reserved should be back to 0.');

        // Reservation status should be 'released'.
        $this->assertDatabaseHas('inventory_reservations', [
            'saga_id'  => 'saga-004',
            'order_id' => 'order-004',
            'status'   => 'released',
        ]);
    }

    // =========================================================================
    // 5. testCheckAvailability
    // =========================================================================

    /** @test */
    public function testCheckAvailability(): void
    {
        $this->createProduct('SKU-G', 100);
        $this->createProduct('SKU-H', 2);

        $result = $this->service->checkAvailability(
            items: [
                ['sku' => 'SKU-G', 'quantity' => 10],
                ['sku' => 'SKU-H', 'quantity' => 5],     // Insufficient.
                ['sku' => 'SKU-Z', 'quantity' => 1],     // Non-existent.
            ],
            tenantId: $this->tenantId,
        );

        $this->assertFalse($result['available'], 'Overall availability should be false.');
        $this->assertCount(3, $result['items']);

        $skuG = collect($result['items'])->firstWhere('sku', 'SKU-G');
        $this->assertTrue($skuG['available']);

        $skuH = collect($result['items'])->firstWhere('sku', 'SKU-H');
        $this->assertFalse($skuH['available']);

        $skuZ = collect($result['items'])->firstWhere('sku', 'SKU-Z');
        $this->assertFalse($skuZ['available']);

        // Check must NOT affect stock levels.
        $itemG = InventoryItem::where('tenant_id', $this->tenantId)
            ->whereHas('product', fn ($q) => $q->where('sku', 'SKU-G'))
            ->first();
        $this->assertEquals(100, $itemG->quantity_available, 'checkAvailability must not reserve stock.');
    }
}
