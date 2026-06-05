<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Application\Services\StockAdjustmentService;
use Modules\Inventory\Application\Services\StockAvailabilityService;
use Modules\Inventory\Application\Services\StockIssuingService;
use Modules\Inventory\Application\Services\StockReceivingService;
use Modules\Inventory\Application\Services\StockReservationService;
use Modules\Inventory\Application\Services\StockTransferService;
use Tests\TestCase;

final class InventoryServicesTest extends TestCase
{
    use RefreshDatabase;

    private int $itemId;

    private int $mainWarehouseId;

    private int $spareWarehouseId;

    private int $uomId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->uomId = (int) DB::table('unit_of_measures')->where('tenant_id', 1)->where('uom_code', 'PCS')->value('id');
        $this->mainWarehouseId = (int) DB::table('warehouses')->where('tenant_id', 1)->where('code', 'MAIN')->value('id');
        $this->spareWarehouseId = (int) DB::table('warehouses')->insertGetId([
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'name' => 'Spare Warehouse',
            'code' => 'SPARE',
            'type' => 'standard',
            'is_active' => true,
            'is_default' => false,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->itemId = (int) DB::table('items')->insertGetId([
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'item_code' => 'INV-ITEM-100',
            'name' => 'Inventory Test Item',
            'base_uom_id' => $this->uomId,
            'track_inventory' => true,
            'is_stock_item' => true,
            'is_service_item' => false,
            'cost_price' => 100,
            'sales_price' => 150,
            'reorder_level' => 0,
            'reorder_quantity' => 0,
            'status' => 'active',
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_receive_reserve_issue_transfer_and_adjust_stock(): void
    {
        app(StockReceivingService::class)->receive([
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'source_type' => 'purchase_grn',
            'source_id' => 10,
            'warehouse_id' => $this->mainWarehouseId,
            'lines' => [[
                'source_line_id' => 1,
                'item_id' => $this->itemId,
                'uom_id' => $this->uomId,
                'quantity' => 10,
                'unit_cost' => 100,
            ]],
        ]);

        $this->assertAvailability(10, 0, 10, $this->mainWarehouseId);
        $this->assertDatabaseHas('inventory_cost_layers', [
            'tenant_id' => 1,
            'item_id' => $this->itemId,
            'quantity_remaining' => 10,
        ]);

        $reservation = app(StockReservationService::class)->reserve([
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'source_type' => 'sales_order',
            'source_id' => 20,
            'warehouse_id' => $this->mainWarehouseId,
            'lines' => [[
                'item_id' => $this->itemId,
                'uom_id' => $this->uomId,
                'quantity' => 3,
            ]],
        ]);
        $reservationId = $reservation['reservations'][0]['reservation_id'];

        $this->assertAvailability(10, 3, 7, $this->mainWarehouseId);

        app(StockReservationService::class)->release(1, $reservationId, 1);
        $this->assertAvailability(10, 2, 8, $this->mainWarehouseId);

        app(StockReservationService::class)->consume(1, $reservationId, 2);
        $this->assertAvailability(10, 0, 10, $this->mainWarehouseId);

        app(StockIssuingService::class)->issue([
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'source_type' => 'sales_delivery',
            'source_id' => 30,
            'warehouse_id' => $this->mainWarehouseId,
            'lines' => [[
                'source_line_id' => 1,
                'item_id' => $this->itemId,
                'uom_id' => $this->uomId,
                'quantity' => 2,
            ]],
        ]);
        $this->assertAvailability(8, 0, 8, $this->mainWarehouseId);

        app(StockTransferService::class)->transfer([
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'requested_by' => 1,
            'from_warehouse_id' => $this->mainWarehouseId,
            'to_warehouse_id' => $this->spareWarehouseId,
            'lines' => [[
                'item_id' => $this->itemId,
                'uom_id' => $this->uomId,
                'quantity' => 4,
            ]],
        ]);
        $this->assertAvailability(4, 0, 4, $this->mainWarehouseId);
        $this->assertAvailability(4, 0, 4, $this->spareWarehouseId);
        $this->assertDatabaseCount('stock_transfer_lines', 1);

        app(StockAdjustmentService::class)->adjust([
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'type' => 'adjustment_out',
            'warehouse_id' => $this->spareWarehouseId,
            'reason' => 'Count correction',
            'lines' => [[
                'item_id' => $this->itemId,
                'uom_id' => $this->uomId,
                'quantity' => 1,
            ]],
        ]);
        $this->assertAvailability(3, 0, 3, $this->spareWarehouseId);
        $this->assertDatabaseCount('stock_adjustment_lines', 1);
        $this->assertDatabaseCount('stock_movements', 8);
    }

    private function assertAvailability(float $onHand, float $reserved, float $available, int $warehouseId): void
    {
        $result = app(StockAvailabilityService::class)->check([
            'tenant_id' => 1,
            'warehouse_id' => $warehouseId,
            'item_id' => $this->itemId,
        ]);

        $this->assertSame($onHand, $result['on_hand_quantity']);
        $this->assertSame($reserved, $result['reserved_quantity']);
        $this->assertSame($available, $result['available_quantity']);
    }
}
