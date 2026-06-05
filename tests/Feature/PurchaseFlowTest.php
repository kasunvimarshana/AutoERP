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
                    'tax_amount' => 50,
                ]],
            ])
            ->assertCreated()
            ->assertJsonPath('data.grand_total', '550.0000');

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
            ->assertJsonPath('data.status', 'issued');

        $invoiceId = (int) $invoice->json('data.id');
        $this->assertDatabaseHas('journal_entries', ['tenant_id' => 1, 'source_module' => 'invoice', 'reference_id' => $invoiceId]);
        $this->assertDatabaseHas('ap_transactions', ['tenant_id' => 1, 'source_id' => $invoiceId, 'outstanding_amount' => 550]);

        $payment = $this->withHeaders($this->headers)
            ->postJson('/api/payment/payments', [
                'party_type' => 'supplier',
                'party_id' => $supplierId,
                'payment_date' => '2026-06-09',
                'amount' => 550,
                'direction' => 'outbound',
                'payment_method_id' => $paymentMethodId,
                'allocations' => [[
                    'invoice_id' => $invoiceId,
                    'allocated_amount' => 550,
                ]],
            ])
            ->assertCreated()
            ->assertJsonPath('data.allocated_amount', '550.0000');

        $this->assertDatabaseHas('payment_allocations', [
            'tenant_id' => 1,
            'invoice_id' => $invoiceId,
            'payment_id' => (int) $payment->json('data.id'),
            'allocated_amount' => 550,
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
