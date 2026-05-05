<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Infrastructure\Listeners\HandleGoodsReceiptPosted;
use Modules\Inventory\Infrastructure\Listeners\HandlePurchaseReturnPosted as InventoryHandlePurchaseReturnPosted;
use Modules\Inventory\Infrastructure\Listeners\HandleSalesOrderConfirmed;
use Modules\Inventory\Infrastructure\Listeners\HandleSalesReturnReceived as InventoryHandleSalesReturnReceived;
use Modules\Inventory\Infrastructure\Listeners\HandleShipmentProcessed;
use Modules\Purchase\Domain\Events\GoodsReceiptPosted;
use Modules\Purchase\Domain\Events\PurchaseReturnPosted;
use Modules\Sales\Domain\Events\SalesOrderConfirmed;
use Modules\Sales\Domain\Events\SalesReturnReceived;
use Modules\Sales\Domain\Events\ShipmentProcessed;
use Tests\TestCase;

class InventoryListenerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId  = 301;
    private int $productId = 4001;
    private int $uomId     = 5001;
    private int $warehouseId;
    private int $locationId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant($this->tenantId);
        $this->seedReferenceData();

        $this->warehouseId = (int) DB::table('warehouses')->insertGetId([
            'tenant_id'   => $this->tenantId,
            'name'        => 'Test Warehouse',
            'code'        => 'TW-301',
            'type'        => 'standard',
            'is_active'   => true,
            'is_default'  => true,
            'row_version' => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->locationId = (int) DB::table('warehouse_locations')->insertGetId([
            'tenant_id'    => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'name'         => 'Rack A',
            'code'         => 'RACK-A',
            'type'         => 'rack',
            'is_active'    => true,
            'row_version'  => 1,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        DB::table('stock_levels')->insert([
            'tenant_id'         => $this->tenantId,
            'product_id'        => $this->productId,
            'variant_id'        => null,
            'location_id'       => $this->locationId,
            'batch_id'          => null,
            'serial_id'         => null,
            'uom_id'            => $this->uomId,
            'quantity_on_hand'  => '50.000000',
            'quantity_reserved' => '0.000000',
            'unit_cost'         => '15.000000',
            'last_movement_at'  => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }

    // ────────────────────────────────────────────────────────
    // HandleSalesOrderConfirmed
    // ────────────────────────────────────────────────────────

    public function test_handle_sales_order_confirmed_creates_stock_reservation(): void
    {
        $listener = app(HandleSalesOrderConfirmed::class);

        $event = new SalesOrderConfirmed(
            tenantId: $this->tenantId,
            salesOrderId: 800,
            customerId: 1,
            warehouseId: $this->warehouseId,
            lines: [
                [
                    'product_id' => $this->productId,
                    'variant_id' => null,
                    'quantity'   => '10.000000',
                    'uom_id'     => $this->uomId,
                ],
            ],
        );

        $listener->handle($event);

        // A reservation row must have been created.
        $reservation = DB::table('stock_reservations')
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $this->productId)
            ->where('reserved_for_type', 'sales_orders')
            ->where('reserved_for_id', 800)
            ->first();

        $this->assertNotNull($reservation, 'Expected a stock reservation for the sales order');
        $this->assertSame(0, bccomp((string) $reservation->quantity, '10.000000', 6));
        $this->assertSame($this->locationId, (int) $reservation->location_id);

        // quantity_reserved on the stock_levels row must reflect the reservation.
        $reservedQty = DB::table('stock_levels')
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationId)
            ->value('quantity_reserved');

        $this->assertSame(0, bccomp((string) $reservedQty, '10.000000', 6));
    }

    public function test_handle_sales_order_confirmed_skips_zero_quantity_lines(): void
    {
        $listener = app(HandleSalesOrderConfirmed::class);

        $event = new SalesOrderConfirmed(
            tenantId: $this->tenantId,
            salesOrderId: 801,
            customerId: 1,
            warehouseId: $this->warehouseId,
            lines: [
                [
                    'product_id' => $this->productId,
                    'variant_id' => null,
                    'quantity'   => '0.000000',
                    'uom_id'     => $this->uomId,
                ],
            ],
        );

        $listener->handle($event);

        $this->assertSame(0, DB::table('stock_reservations')->count());
    }

    public function test_handle_sales_order_confirmed_skips_empty_lines(): void
    {
        $listener = app(HandleSalesOrderConfirmed::class);

        $event = new SalesOrderConfirmed(
            tenantId: $this->tenantId,
            salesOrderId: 802,
            customerId: 1,
            warehouseId: $this->warehouseId,
            lines: [],
        );

        $listener->handle($event);

        $this->assertSame(0, DB::table('stock_reservations')->count());
    }

    public function test_handle_sales_order_confirmed_partial_fill_when_insufficient_stock(): void
    {
        $listener = app(HandleSalesOrderConfirmed::class);

        // Request more than available on hand (50).
        $event = new SalesOrderConfirmed(
            tenantId: $this->tenantId,
            salesOrderId: 803,
            customerId: 1,
            warehouseId: $this->warehouseId,
            lines: [
                [
                    'product_id' => $this->productId,
                    'variant_id' => null,
                    'quantity'   => '100.000000',
                    'uom_id'     => $this->uomId,
                ],
            ],
        );

        $listener->handle($event);

        // Should create a partial reservation equal to available stock.
        $totalReserved = (string) DB::table('stock_reservations')
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $this->productId)
            ->where('reserved_for_id', 803)
            ->sum('quantity');

        $this->assertSame(0, bccomp($totalReserved, '50.000000', 6),
            'Partial reservation should equal available stock quantity');
    }

    // ────────────────────────────────────────────────────────
    // HandleShipmentProcessed
    // ────────────────────────────────────────────────────────

    public function test_handle_shipment_processed_deducts_stock_and_creates_movement(): void
    {
        // Pre-reserve 10 units so we can verify they are released on shipment.
        DB::table('stock_reservations')->insert([
            'tenant_id'          => $this->tenantId,
            'product_id'         => $this->productId,
            'variant_id'         => null,
            'batch_id'           => null,
            'serial_id'          => null,
            'location_id'        => $this->locationId,
            'quantity'           => '10.000000',
            'reserved_for_type'  => 'sales_orders',
            'reserved_for_id'    => 900,
            'expires_at'         => null,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        DB::table('stock_levels')
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationId)
            ->update(['quantity_reserved' => '10.000000']);

        $listener = app(HandleShipmentProcessed::class);

        $event = new ShipmentProcessed(
            tenantId: $this->tenantId,
            shipmentId: 700,
            customerId: 1,
            warehouseId: $this->warehouseId,
            salesOrderId: 900,
            lines: [
                [
                    'id'               => null,
                    'product_id'       => $this->productId,
                    'from_location_id' => $this->locationId,
                    'uom_id'           => $this->uomId,
                    'shipped_qty'      => '10.000000',
                    'unit_cost'        => '15.000000',
                    'variant_id'       => null,
                    'batch_id'         => null,
                    'serial_id'        => null,
                ],
            ],
        );

        $listener->handle($event);

        // Stock on hand must be reduced.
        $qtyOnHand = DB::table('stock_levels')
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationId)
            ->value('quantity_on_hand');

        $this->assertSame(0, bccomp((string) $qtyOnHand, '40.000000', 6),
            'Stock on hand should decrease by shipped quantity');

        // A stock movement row must exist.
        $this->assertDatabaseHas('stock_movements', [
            'tenant_id'        => $this->tenantId,
            'product_id'       => $this->productId,
            'from_location_id' => $this->locationId,
            'movement_type'    => 'shipment',
            'reference_type'   => 'shipment',
            'reference_id'     => 700,
            'quantity'         => '10.000000',
        ]);

        // Reservations for this sales order must have been released.
        $remainingReservations = DB::table('stock_reservations')
            ->where('tenant_id', $this->tenantId)
            ->where('reserved_for_type', 'sales_orders')
            ->where('reserved_for_id', 900)
            ->count();

        $this->assertSame(0, $remainingReservations,
            'Reservations for the shipped sales order should be released');
    }

    public function test_handle_shipment_processed_skips_lines_without_from_location(): void
    {
        $listener = app(HandleShipmentProcessed::class);

        $event = new ShipmentProcessed(
            tenantId: $this->tenantId,
            shipmentId: 701,
            customerId: 1,
            warehouseId: $this->warehouseId,
            salesOrderId: null,
            lines: [
                [
                    'id'               => null,
                    'product_id'       => $this->productId,
                    'from_location_id' => null,
                    'uom_id'           => $this->uomId,
                    'shipped_qty'      => '5.000000',
                    'unit_cost'        => '15.000000',
                    'variant_id'       => null,
                    'batch_id'         => null,
                    'serial_id'        => null,
                ],
            ],
        );

        $listener->handle($event);

        // No movement should be recorded.
        $this->assertSame(0, DB::table('stock_movements')->count());

        // Stock on hand must be unchanged.
        $qtyOnHand = DB::table('stock_levels')
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationId)
            ->value('quantity_on_hand');

        $this->assertSame(0, bccomp((string) $qtyOnHand, '50.000000', 6));
    }

    public function test_handle_shipment_processed_skips_empty_lines(): void
    {
        $listener = app(HandleShipmentProcessed::class);

        $event = new ShipmentProcessed(
            tenantId: $this->tenantId,
            shipmentId: 702,
            customerId: 1,
            warehouseId: $this->warehouseId,
            salesOrderId: null,
            lines: [],
        );

        $listener->handle($event);

        $this->assertSame(0, DB::table('stock_movements')->count());
    }

    // ────────────────────────────────────────────────────────
    // HandleGoodsReceiptPosted
    // ────────────────────────────────────────────────────────

    public function test_handle_goods_receipt_posted_creates_movement_and_increases_stock(): void
    {
        $listener = app(HandleGoodsReceiptPosted::class);

        $event = new GoodsReceiptPosted(
            tenantId: $this->tenantId,
            grnHeaderId: 1001,
            supplierId: 1,
            warehouseId: $this->warehouseId,
            lines: [
                [
                    'product_id'   => $this->productId,
                    'variant_id'   => null,
                    'batch_id'     => null,
                    'serial_id'    => null,
                    'location_id'  => $this->locationId,
                    'uom_id'       => $this->uomId,
                    'received_qty' => '20.000000',
                    'unit_cost'    => '12.000000',
                ],
            ],
        );

        $listener->handle($event);

        // A receipt movement row must exist.
        $movement = DB::table('stock_movements')
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $this->productId)
            ->where('movement_type', 'receipt')
            ->where('reference_type', 'grn_header')
            ->where('reference_id', 1001)
            ->first();

        $this->assertNotNull($movement, 'Expected a stock movement for GRN');
        $this->assertSame(0, bccomp((string) $movement->quantity, '20.000000', 6));
        $this->assertSame($this->locationId, (int) $movement->to_location_id);
        $this->assertNull($movement->from_location_id);

        // on_hand must have increased by 20 (50 → 70).
        $onHand = DB::table('stock_levels')
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationId)
            ->value('quantity_on_hand');

        $this->assertSame(0, bccomp((string) $onHand, '70.000000', 6));
    }

    public function test_handle_goods_receipt_posted_skips_lines_without_location(): void
    {
        $listener = app(HandleGoodsReceiptPosted::class);

        $event = new GoodsReceiptPosted(
            tenantId: $this->tenantId,
            grnHeaderId: 1002,
            supplierId: 1,
            warehouseId: $this->warehouseId,
            lines: [
                [
                    'product_id'   => $this->productId,
                    'uom_id'       => $this->uomId,
                    'received_qty' => '10.000000',
                    // location_id intentionally missing
                ],
            ],
        );

        $listener->handle($event);

        $this->assertSame(0, DB::table('stock_movements')->count());
    }

    public function test_handle_goods_receipt_posted_skips_empty_lines(): void
    {
        $listener = app(HandleGoodsReceiptPosted::class);

        $event = new GoodsReceiptPosted(
            tenantId: $this->tenantId,
            grnHeaderId: 1003,
            supplierId: 1,
            warehouseId: $this->warehouseId,
            lines: [],
        );

        $listener->handle($event);

        $this->assertSame(0, DB::table('stock_movements')->count());
    }

    // ────────────────────────────────────────────────────────
    // HandlePurchaseReturnPosted (Inventory)
    // ────────────────────────────────────────────────────────

    public function test_handle_purchase_return_posted_creates_return_out_movement_and_reduces_stock(): void
    {
        $listener = app(InventoryHandlePurchaseReturnPosted::class);

        $event = new PurchaseReturnPosted(
            tenantId: $this->tenantId,
            purchaseReturnId: 2001,
            supplierId: 1,
            lines: [
                [
                    'product_id'       => $this->productId,
                    'variant_id'       => null,
                    'batch_id'         => null,
                    'serial_id'        => null,
                    'from_location_id' => $this->locationId,
                    'uom_id'           => $this->uomId,
                    'return_qty'       => '8.000000',
                    'unit_cost'        => '15.000000',
                ],
            ],
        );

        $listener->handle($event);

        // A return_out movement row must exist.
        $movement = DB::table('stock_movements')
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $this->productId)
            ->where('movement_type', 'return_out')
            ->where('reference_type', 'purchase_return')
            ->where('reference_id', 2001)
            ->first();

        $this->assertNotNull($movement, 'Expected a return_out stock movement for purchase return');
        $this->assertSame(0, bccomp((string) $movement->quantity, '8.000000', 6));
        $this->assertSame($this->locationId, (int) $movement->from_location_id);
        $this->assertNull($movement->to_location_id);

        // on_hand must have decreased by 8 (50 → 42).
        $onHand = DB::table('stock_levels')
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationId)
            ->value('quantity_on_hand');

        $this->assertSame(0, bccomp((string) $onHand, '42.000000', 6));
    }

    public function test_handle_purchase_return_posted_skips_lines_without_from_location(): void
    {
        $listener = app(InventoryHandlePurchaseReturnPosted::class);

        $event = new PurchaseReturnPosted(
            tenantId: $this->tenantId,
            purchaseReturnId: 2002,
            supplierId: 1,
            lines: [
                [
                    'product_id' => $this->productId,
                    'uom_id'     => $this->uomId,
                    'return_qty' => '5.000000',
                    // from_location_id intentionally missing
                ],
            ],
        );

        $listener->handle($event);

        $this->assertSame(0, DB::table('stock_movements')->count());
    }

    // ────────────────────────────────────────────────────────
    // HandleSalesReturnReceived (Inventory)
    // ────────────────────────────────────────────────────────

    public function test_handle_sales_return_received_creates_return_in_movement_and_increases_stock(): void
    {
        $listener = app(InventoryHandleSalesReturnReceived::class);

        $event = new SalesReturnReceived(
            tenantId: $this->tenantId,
            salesReturnId: 3001,
            customerId: 1,
            lines: [
                [
                    'product_id'     => $this->productId,
                    'variant_id'     => null,
                    'batch_id'       => null,
                    'serial_id'      => null,
                    'to_location_id' => $this->locationId,
                    'uom_id'         => $this->uomId,
                    'return_qty'     => '5.000000',
                ],
            ],
        );

        $listener->handle($event);

        // A return_in movement row must exist.
        $movement = DB::table('stock_movements')
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $this->productId)
            ->where('movement_type', 'return_in')
            ->where('reference_type', 'sales_return')
            ->where('reference_id', 3001)
            ->first();

        $this->assertNotNull($movement, 'Expected a return_in stock movement for sales return');
        $this->assertSame(0, bccomp((string) $movement->quantity, '5.000000', 6));
        $this->assertSame($this->locationId, (int) $movement->to_location_id);
        $this->assertNull($movement->from_location_id);

        // on_hand must have increased by 5 (50 → 55).
        $onHand = DB::table('stock_levels')
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationId)
            ->value('quantity_on_hand');

        $this->assertSame(0, bccomp((string) $onHand, '55.000000', 6));
    }

    public function test_handle_sales_return_received_skips_lines_without_to_location(): void
    {
        $listener = app(InventoryHandleSalesReturnReceived::class);

        $event = new SalesReturnReceived(
            tenantId: $this->tenantId,
            salesReturnId: 3002,
            customerId: 1,
            lines: [
                [
                    'product_id' => $this->productId,
                    'uom_id'     => $this->uomId,
                    'return_qty' => '5.000000',
                    // to_location_id intentionally missing
                ],
            ],
        );

        $listener->handle($event);

        $this->assertSame(0, DB::table('stock_movements')->count());
    }

    public function test_handle_sales_return_received_skips_empty_lines(): void
    {
        $listener = app(InventoryHandleSalesReturnReceived::class);

        $event = new SalesReturnReceived(
            tenantId: $this->tenantId,
            salesReturnId: 3003,
            customerId: 1,
            lines: [],
        );

        $listener->handle($event);

        $this->assertSame(0, DB::table('stock_movements')->count());
    }

    // ────────────────────────────────────────────────────────
    // Seeders
    // ────────────────────────────────────────────────────────

    private function seedTenant(int $tenantId): void
    {
        DB::table('tenants')->insert([
            'id'                   => $tenantId,
            'name'                 => 'Listener Test Tenant',
            'slug'                 => 'listener-tenant-'.$tenantId,
            'domain'               => null,
            'logo_path'            => null,
            'database_config'      => null,
            'mail_config'          => null,
            'cache_config'         => null,
            'queue_config'         => null,
            'feature_flags'        => null,
            'api_keys'             => null,
            'settings'             => null,
            'plan'                 => 'free',
            'tenant_plan_id'       => null,
            'status'               => 'active',
            'active'               => true,
            'trial_ends_at'        => null,
            'subscription_ends_at' => null,
            'created_at'           => now(),
            'updated_at'           => now(),
            'deleted_at'           => null,
        ]);
    }

    private function seedReferenceData(): void
    {
        DB::table('units_of_measure')->insert([
            'id'         => $this->uomId,
            'tenant_id'  => $this->tenantId,
            'name'       => 'Each',
            'symbol'     => 'ea',
            'type'       => 'unit',
            'is_base'    => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('products')->insert([
            'id'                       => $this->productId,
            'tenant_id'                => $this->tenantId,
            'category_id'              => null,
            'brand_id'                 => null,
            'org_unit_id'              => null,
            'type'                     => 'physical',
            'name'                     => 'Listener Test Product',
            'slug'                     => 'listener-test-product',
            'sku'                      => 'LTP-4001',
            'description'              => null,
            'base_uom_id'              => $this->uomId,
            'purchase_uom_id'          => null,
            'sales_uom_id'             => null,
            'tax_group_id'             => null,
            'uom_conversion_factor'    => '1.0000000000',
            'is_batch_tracked'         => false,
            'is_lot_tracked'           => false,
            'is_serial_tracked'        => false,
            'valuation_method'         => 'fifo',
            'standard_cost'            => '15.000000',
            'income_account_id'        => null,
            'cogs_account_id'          => null,
            'inventory_account_id'     => null,
            'expense_account_id'       => null,
            'is_active'                => true,
            'image_path'               => null,
            'metadata'                 => null,
            'created_at'               => now(),
            'updated_at'               => now(),
            'deleted_at'               => null,
        ]);
    }
}
