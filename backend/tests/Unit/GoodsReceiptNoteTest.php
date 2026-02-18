<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Modules\Inventory\Models\Product;
use App\Modules\Procurement\Models\GoodsReceiptNote;
use App\Modules\Procurement\Models\GoodsReceiptNoteItem;
use App\Modules\Procurement\Models\PurchaseOrder;
use App\Modules\Procurement\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Goods Receipt Note Model Tests
 *
 * Tests the GRN model functionality including
 * discrepancy detection and quantity calculations
 */
class GoodsReceiptNoteTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Supplier $supplier;

    protected PurchaseOrder $purchaseOrder;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->purchaseOrder = PurchaseOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'supplier_id' => $this->supplier->id,
        ]);
    }

    public function test_can_create_goods_receipt_note(): void
    {
        $grn = GoodsReceiptNote::create([
            'tenant_id' => $this->tenant->id,
            'grn_number' => 'GRN-001',
            'purchase_order_id' => $this->purchaseOrder->id,
            'supplier_id' => $this->supplier->id,
            'received_date' => now(),
            'received_by' => 1,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('goods_receipt_notes', [
            'id' => $grn->id,
            'grn_number' => 'GRN-001',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_add_items_to_grn(): void
    {
        $grn = GoodsReceiptNote::create([
            'tenant_id' => $this->tenant->id,
            'grn_number' => 'GRN-001',
            'purchase_order_id' => $this->purchaseOrder->id,
            'supplier_id' => $this->supplier->id,
            'received_date' => now(),
            'received_by' => 1,
        ]);

        $item = GoodsReceiptNoteItem::create([
            'goods_receipt_note_id' => $grn->id,
            'product_id' => $this->product->id,
            'quantity_ordered' => 100,
            'quantity_received' => 95,
            'quantity_rejected' => 5,
            'unit_price' => 10.00,
        ]);

        $this->assertDatabaseHas('goods_receipt_note_items', [
            'id' => $item->id,
            'goods_receipt_note_id' => $grn->id,
            'product_id' => $this->product->id,
        ]);

        $this->assertCount(1, $grn->items);
    }

    public function test_detects_discrepancies(): void
    {
        $grn = GoodsReceiptNote::create([
            'tenant_id' => $this->tenant->id,
            'grn_number' => 'GRN-001',
            'purchase_order_id' => $this->purchaseOrder->id,
            'supplier_id' => $this->supplier->id,
            'received_date' => now(),
            'received_by' => 1,
        ]);

        // Item with discrepancy
        GoodsReceiptNoteItem::create([
            'goods_receipt_note_id' => $grn->id,
            'product_id' => $this->product->id,
            'quantity_ordered' => 100,
            'quantity_received' => 95,
            'quantity_rejected' => 5,
            'unit_price' => 10.00,
        ]);

        $this->assertTrue($grn->hasDiscrepancies());
    }

    public function test_item_discrepancy_calculation(): void
    {
        $grn = GoodsReceiptNote::create([
            'tenant_id' => $this->tenant->id,
            'grn_number' => 'GRN-001',
            'purchase_order_id' => $this->purchaseOrder->id,
            'supplier_id' => $this->supplier->id,
            'received_date' => now(),
            'received_by' => 1,
        ]);

        $item = GoodsReceiptNoteItem::create([
            'goods_receipt_note_id' => $grn->id,
            'product_id' => $this->product->id,
            'quantity_ordered' => 100,
            'quantity_received' => 90,
            'quantity_rejected' => 0,
            'unit_price' => 10.00,
        ]);

        $this->assertTrue($item->hasDiscrepancy());
        $this->assertEquals(10, $item->discrepancyQuantity());
    }

    public function test_calculates_total_quantity(): void
    {
        $grn = GoodsReceiptNote::create([
            'tenant_id' => $this->tenant->id,
            'grn_number' => 'GRN-001',
            'purchase_order_id' => $this->purchaseOrder->id,
            'supplier_id' => $this->supplier->id,
            'received_date' => now(),
            'received_by' => 1,
        ]);

        GoodsReceiptNoteItem::create([
            'goods_receipt_note_id' => $grn->id,
            'product_id' => $this->product->id,
            'quantity_ordered' => 100,
            'quantity_received' => 95,
            'unit_price' => 10.00,
        ]);

        GoodsReceiptNoteItem::create([
            'goods_receipt_note_id' => $grn->id,
            'product_id' => Product::factory()->create(['tenant_id' => $this->tenant->id])->id,
            'quantity_ordered' => 50,
            'quantity_received' => 50,
            'unit_price' => 15.00,
        ]);

        $totalQuantity = $grn->calculateTotalQuantity();
        $this->assertEquals(145, $totalQuantity);
    }

    public function test_tracks_batch_and_serial_numbers(): void
    {
        $grn = GoodsReceiptNote::create([
            'tenant_id' => $this->tenant->id,
            'grn_number' => 'GRN-001',
            'purchase_order_id' => $this->purchaseOrder->id,
            'supplier_id' => $this->supplier->id,
            'received_date' => now(),
            'received_by' => 1,
        ]);

        $item = GoodsReceiptNoteItem::create([
            'goods_receipt_note_id' => $grn->id,
            'product_id' => $this->product->id,
            'quantity_ordered' => 100,
            'quantity_received' => 100,
            'unit_price' => 10.00,
            'batch_number' => 'BATCH-2026-001',
            'serial_number' => 'SN-123456',
            'expiry_date' => now()->addYear(),
        ]);

        $this->assertEquals('BATCH-2026-001', $item->batch_number);
        $this->assertEquals('SN-123456', $item->serial_number);
        $this->assertNotNull($item->expiry_date);
    }

    public function test_no_discrepancy_when_quantities_match(): void
    {
        $grn = GoodsReceiptNote::create([
            'tenant_id' => $this->tenant->id,
            'grn_number' => 'GRN-001',
            'purchase_order_id' => $this->purchaseOrder->id,
            'supplier_id' => $this->supplier->id,
            'received_date' => now(),
            'received_by' => 1,
        ]);

        GoodsReceiptNoteItem::create([
            'goods_receipt_note_id' => $grn->id,
            'product_id' => $this->product->id,
            'quantity_ordered' => 100,
            'quantity_received' => 100,
            'quantity_rejected' => 0,
            'unit_price' => 10.00,
        ]);

        $this->assertFalse($grn->hasDiscrepancies());
    }
}
