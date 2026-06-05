<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string, string>
     */
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $login = $this->postJson('/api/auth/login', [
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'provider_key' => 'internal',
            'login_identifier' => 'admin@example.com',
            'password' => 'password',
            'device_name' => 'Purchase flow test',
        ])->assertOk();

        $this->headers = [
            'Authorization' => 'Bearer '.$login->json('data.tokens.access_token'),
            'X-Tenant-ID' => '1',
            'X-Organization-Unit-ID' => '1',
        ];
    }

    public function test_purchase_order_grn_invoice_payment_and_return_flow(): void
    {
        $supplierId = $this->supplierId();
        $uomId = (int) DB::table('unit_of_measures')->where('tenant_id', 1)->where('uom_code', 'PCS')->value('id');
        $warehouseId = (int) DB::table('warehouses')->where('tenant_id', 1)->where('code', 'MAIN')->value('id');
        $paymentMethodId = (int) DB::table('payment_methods')->where('tenant_id', 1)->where('code', 'CASH')->value('id');
        $itemId = $this->itemId($uomId);

        $po = $this->withHeaders($this->headers)
            ->postJson('/api/purchase/purchase-orders', [
                'po_number' => 'PO-FLOW-100',
                'supplier_id' => $supplierId,
                'warehouse_id' => $warehouseId,
                'order_date' => '2026-06-06',
                'expected_date' => '2026-06-08',
                'lines' => [[
                    'item_id' => $itemId,
                    'uom_id' => $uomId,
                    'ordered_qty' => 5,
                    'unit_price' => 100,
                    'discount_amount' => 10,
                    'tax_amount' => 50,
                ]],
                'header_discount_amount' => 20,
                'header_tax_amount' => 5,
                'header_charge_total' => 15,
                'credit_note_total' => 3,
            ])
            ->assertCreated()
            ->assertJsonPath('data.subtotal', '500.0000')
            ->assertJsonPath('data.line_discount_total', '10.0000')
            ->assertJsonPath('data.header_discount_amount', '20.0000')
            ->assertJsonPath('data.line_tax_total', '50.0000')
            ->assertJsonPath('data.header_tax_amount', '5.0000')
            ->assertJsonPath('data.charge_total', '15.0000')
            ->assertJsonPath('data.credit_note_total', '3.0000')
            ->assertJsonPath('data.grand_total', '537.0000');

        $poId = (int) $po->json('data.id');
        $poLineId = (int) $po->json('data.lines.0.id');

        $this->withHeaders($this->headers)
            ->postJson("/api/purchase/purchase-orders/$poId/confirm")
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $grn = $this->withHeaders($this->headers)
            ->postJson('/api/purchase/grns', [
                'grn_number' => 'GRN-FLOW-100',
                'purchase_order_id' => $poId,
                'received_date' => '2026-06-08',
                'lines' => [[
                    'purchase_order_line_id' => $poLineId,
                    'item_id' => $itemId,
                    'uom_id' => $uomId,
                    'warehouse_id' => $warehouseId,
                    'received_qty' => 5,
                    'unit_price' => 100,
                    'tax_amount' => 50,
                ]],
            ])
            ->assertCreated();

        $grnId = (int) $grn->json('data.id');
        $grnLineId = (int) $grn->json('data.lines.0.id');

        $this->withHeaders($this->headers)
            ->postJson("/api/purchase/grns/$grnId/post")
            ->assertOk()
            ->assertJsonPath('data.status', 'posted');

        $this->assertDatabaseHas('stock_levels', [
            'tenant_id' => 1,
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'quantity_on_hand' => 5,
        ]);

        $invoice = $this->withHeaders($this->headers)
            ->postJson("/api/purchase/grns/$grnId/invoice")
            ->assertOk()
            ->assertJsonPath('data.ledger_direction', 'payable')
            ->assertJsonPath('data.line_discount_total', '10.0000')
            ->assertJsonPath('data.header_discount_total', '20.0000')
            ->assertJsonPath('data.tax_total', '55.0000')
            ->assertJsonPath('data.charge_total', '15.0000')
            ->assertJsonPath('data.credit_adjustment_total', '3.0000')
            ->assertJsonPath('data.grand_total', '537.0000')
            ->assertJsonPath('data.status', 'issued');

        $invoiceId = (int) $invoice->json('data.id');
        $this->assertDatabaseHas('journal_entries', ['tenant_id' => 1, 'source_module' => 'invoice', 'reference_id' => $invoiceId]);
        $this->assertDatabaseHas('ap_transactions', ['tenant_id' => 1, 'source_id' => $invoiceId, 'outstanding_amount' => 537]);

        $payment = $this->withHeaders($this->headers)
            ->postJson('/api/payment/payments', [
                'party_type' => 'supplier',
                'party_id' => $supplierId,
                'payment_date' => '2026-06-09',
                'amount' => 537,
                'direction' => 'outbound',
                'payment_method_id' => $paymentMethodId,
                'allocations' => [[
                    'invoice_id' => $invoiceId,
                    'allocated_amount' => 537,
                ]],
            ])
            ->assertCreated()
            ->assertJsonPath('data.allocated_amount', '537.0000');

        $this->assertDatabaseHas('payment_allocations', [
            'tenant_id' => 1,
            'invoice_id' => $invoiceId,
            'payment_id' => (int) $payment->json('data.id'),
            'allocated_amount' => 537,
        ]);

        $return = $this->withHeaders($this->headers)
            ->postJson('/api/purchase/purchase-returns', [
                'return_number' => 'PR-FLOW-100',
                'original_grn_id' => $grnId,
                'original_invoice_id' => $invoiceId,
                'return_date' => '2026-06-10',
                'lines' => [[
                    'original_grn_line_id' => $grnLineId,
                    'item_id' => $itemId,
                    'uom_id' => $uomId,
                    'warehouse_id' => $warehouseId,
                    'return_qty' => 1,
                    'unit_price' => 100,
                ]],
            ])
            ->assertCreated();

        $returnId = (int) $return->json('data.id');
        $this->withHeaders($this->headers)
            ->postJson("/api/purchase/purchase-returns/$returnId/post")
            ->assertOk()
            ->assertJsonPath('data.status', 'refunded');

        $this->assertDatabaseHas('stock_levels', [
            'tenant_id' => 1,
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'quantity_on_hand' => 4,
        ]);
        $this->assertDatabaseHas('purchase_invoice_links', [
            'tenant_id' => 1,
            'source_type' => 'purchase_return',
            'source_id' => $returnId,
        ]);
    }

    public function test_purchase_order_supports_partial_multiple_invoices_with_header_allocation(): void
    {
        $supplierId = $this->supplierId();
        $uomId = (int) DB::table('unit_of_measures')->where('tenant_id', 1)->where('uom_code', 'PCS')->value('id');
        $warehouseId = (int) DB::table('warehouses')->where('tenant_id', 1)->where('code', 'MAIN')->value('id');
        $itemId = $this->itemId($uomId);

        $po = $this->withHeaders($this->headers)
            ->postJson('/api/purchase/purchase-orders', [
                'po_number' => 'PO-PARTIAL-100',
                'supplier_id' => $supplierId,
                'warehouse_id' => $warehouseId,
                'order_date' => '2026-06-06',
                'lines' => [[
                    'item_id' => $itemId,
                    'uom_id' => $uomId,
                    'ordered_qty' => 10,
                    'unit_price' => 100,
                    'discount_amount' => 10,
                    'tax_amount' => 50,
                ]],
                'header_discount_amount' => 20,
                'header_tax_amount' => 5,
                'header_charge_total' => 15,
                'credit_note_total' => 3,
            ])
            ->assertCreated()
            ->assertJsonPath('data.grand_total', '1037.0000');

        $poId = (int) $po->json('data.id');
        $poLineId = (int) $po->json('data.lines.0.id');

        $this->withHeaders($this->headers)
            ->postJson("/api/purchase/purchase-orders/$poId/confirm")
            ->assertOk();

        $firstInvoice = $this->withHeaders($this->headers)
            ->postJson("/api/purchase/purchase-orders/$poId/invoice", [
                'lines' => [[
                    'source_line_id' => $poLineId,
                    'quantity' => 4,
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('data.document_type', 'purchase_invoice')
            ->assertJsonPath('data.gross_total', '400.0000')
            ->assertJsonPath('data.line_discount_total', '4.0000')
            ->assertJsonPath('data.header_discount_total', '8.0000')
            ->assertJsonPath('data.tax_total', '22.0000')
            ->assertJsonPath('data.charge_total', '6.0000')
            ->assertJsonPath('data.credit_adjustment_total', '1.2000')
            ->assertJsonPath('data.grand_total', '414.8000');

        $this->assertDatabaseHas('purchase_invoice_links', [
            'tenant_id' => 1,
            'source_type' => 'purchase_order',
            'source_id' => $poId,
            'source_line_id' => $poLineId,
            'invoice_id' => (int) $firstInvoice->json('data.id'),
            'linked_quantity' => 4,
            'allocated_header_discount_amount' => 8,
            'allocated_line_tax_amount' => 20,
            'allocated_header_tax_amount' => 2,
            'allocated_charge_amount' => 6,
            'allocated_credit_adjustment_amount' => 1.2,
        ]);

        $this->withHeaders($this->headers)
            ->getJson("/api/purchase/purchase-orders/$poId")
            ->assertOk()
            ->assertJsonPath('data.invoice_status', 'partially_invoiced')
            ->assertJsonPath('data.lines.0.invoiced_qty', 4);

        $secondInvoice = $this->withHeaders($this->headers)
            ->postJson("/api/purchase/purchase-orders/$poId/invoice", [
                'lines' => [[
                    'source_line_id' => $poLineId,
                    'quantity' => 6,
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('data.gross_total', '600.0000')
            ->assertJsonPath('data.line_discount_total', '6.0000')
            ->assertJsonPath('data.header_discount_total', '12.0000')
            ->assertJsonPath('data.tax_total', '33.0000')
            ->assertJsonPath('data.charge_total', '9.0000')
            ->assertJsonPath('data.credit_adjustment_total', '1.8000')
            ->assertJsonPath('data.grand_total', '622.2000');

        $this->assertSame(1037.0, round((float) $firstInvoice->json('data.grand_total') + (float) $secondInvoice->json('data.grand_total'), 4));
        $this->assertSame(20.0, round((float) DB::table('purchase_invoice_links')->where('tenant_id', 1)->where('source_type', 'purchase_order')->where('source_id', $poId)->sum('allocated_header_discount_amount'), 4));
        $this->assertSame(5.0, round((float) DB::table('purchase_invoice_links')->where('tenant_id', 1)->where('source_type', 'purchase_order')->where('source_id', $poId)->sum('allocated_header_tax_amount'), 4));
        $this->assertSame(15.0, round((float) DB::table('purchase_invoice_links')->where('tenant_id', 1)->where('source_type', 'purchase_order')->where('source_id', $poId)->sum('allocated_charge_amount'), 4));
        $this->assertSame(3.0, round((float) DB::table('purchase_invoice_links')->where('tenant_id', 1)->where('source_type', 'purchase_order')->where('source_id', $poId)->sum('allocated_credit_adjustment_amount'), 4));

        $this->withHeaders($this->headers)
            ->getJson("/api/purchase/purchase-orders/$poId")
            ->assertOk()
            ->assertJsonPath('data.invoice_status', 'fully_invoiced')
            ->assertJsonPath('data.lines.0.invoiced_qty', 10);
    }

    public function test_supplier_advance_payment_can_be_allocated_to_purchase_invoice_later(): void
    {
        $supplierId = $this->supplierId();
        $uomId = (int) DB::table('unit_of_measures')->where('tenant_id', 1)->where('uom_code', 'PCS')->value('id');
        $warehouseId = (int) DB::table('warehouses')->where('tenant_id', 1)->where('code', 'MAIN')->value('id');
        $paymentMethodId = (int) DB::table('payment_methods')->where('tenant_id', 1)->where('code', 'CASH')->value('id');
        $itemId = $this->itemId($uomId);

        $po = $this->withHeaders($this->headers)
            ->postJson('/api/purchase/purchase-orders', [
                'po_number' => 'PO-ADV-100',
                'supplier_id' => $supplierId,
                'warehouse_id' => $warehouseId,
                'order_date' => '2026-06-06',
                'lines' => [[
                    'item_id' => $itemId,
                    'uom_id' => $uomId,
                    'ordered_qty' => 1,
                    'unit_price' => 100,
                ]],
            ])
            ->assertCreated();

        $poId = (int) $po->json('data.id');
        $this->withHeaders($this->headers)->postJson("/api/purchase/purchase-orders/$poId/confirm")->assertOk();
        $invoice = $this->withHeaders($this->headers)->postJson("/api/purchase/purchase-orders/$poId/invoice")->assertOk();
        $invoiceId = (int) $invoice->json('data.id');

        $this->withHeaders($this->headers)
            ->postJson('/api/payment/payments', [
                'party_type' => 'supplier',
                'party_id' => $supplierId,
                'payment_date' => '2026-06-07',
                'amount' => 40,
                'direction' => 'outbound',
                'payment_method_id' => $paymentMethodId,
            ])
            ->assertCreated()
            ->assertJsonPath('data.allocated_amount', '0.0000');

        $advanceId = (int) DB::table('advance_payments')->where('tenant_id', 1)->where('party_type', 'supplier')->where('party_id', $supplierId)->value('id');

        $this->withHeaders($this->headers)
            ->postJson("/api/payment/advances/$advanceId/allocations", [
                'allocations' => [[
                    'invoice_id' => $invoiceId,
                    'allocated_amount' => 40,
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('data.remaining_amount', 0)
            ->assertJsonPath('data.status', 'fully_applied');

        $this->assertDatabaseHas('advance_payment_allocations', [
            'tenant_id' => 1,
            'advance_payment_id' => $advanceId,
            'invoice_id' => $invoiceId,
            'allocated_amount' => 40,
        ]);

        $this->withHeaders($this->headers)
            ->getJson("/api/invoice/invoices/$invoiceId")
            ->assertOk()
            ->assertJsonPath('data.paid_total', '40.0000')
            ->assertJsonPath('data.balance_due', '60.0000')
            ->assertJsonPath('data.status', 'partially_paid');
    }

    private function supplierId(): int
    {
        return (int) DB::table('suppliers')->insertGetId([
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'supplier_code' => 'SUP-PUR-100',
            'supplier_name' => 'Purchase Flow Supplier',
            'supplier_type' => 'business',
            'credit_limit' => 10000,
            'payment_terms_days' => 7,
            'status' => 'active',
            'is_active' => true,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function itemId(int $uomId): int
    {
        return (int) DB::table('items')->insertGetId([
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'item_code' => 'PUR-ITEM-100',
            'name' => 'Purchase Flow Item',
            'base_uom_id' => $uomId,
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
}
