<?php

declare(strict_types=1);

namespace Modules\Purchase\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Services\PurchaseOrderService;
use Tests\TestCase;

final class PurchaseOrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_create_purchase_order_with_lines_returns_readable_resource(): void
    {
        $context = $this->context();

        $response = $this->postJson('/api/v1/purchase/orders', $this->payload($context));

        $response->assertCreated()
            ->assertJsonPath('data.supplier.name', 'Supplier '.$context['supplier_code'])
            ->assertJsonPath('data.warehouse.code', $context['warehouse_code'])
            ->assertJsonPath('data.lines.0.item.code', $context['item_code'])
            ->assertJsonPath('data.lines.0.uom.code', $context['uom_code'])
            ->assertJsonPath('data.lines.0.line_total', '101.100000')
            ->assertJsonPath('data.grand_total', '101.100000');
    }

    public function test_create_purchase_order_with_header_adjustments_is_decimal_safe(): void
    {
        $context = $this->context();
        $payload = $this->payload($context, [
            'lines' => [
                [
                    'item_id' => $context['item_id'],
                    'uom_id' => $context['uom_id'],
                    'ordered_quantity' => '3.333333',
                    'unit_price' => '2.100000',
                    'discount_amount' => '0.000001',
                    'tax_amount' => '0.000002',
                    'charge_amount' => '0.000003',
                ],
            ],
            'adjustments' => [
                [
                    'name' => 'Freight',
                    'adjustment_type' => 'freight',
                    'effect' => 'increase',
                    'amount' => '1.000001',
                ],
                [
                    'name' => 'Discount',
                    'adjustment_type' => 'discount',
                    'effect' => 'decrease',
                    'amount' => '0.500001',
                ],
            ],
        ]);

        $response = $this->postJson('/api/v1/purchase/orders', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.lines.0.line_total', '7.000003')
            ->assertJsonPath('data.adjustment_total', '0.500000')
            ->assertJsonPath('data.grand_total', '7.500003');
    }

    public function test_duplicate_purchase_order_number_is_prevented(): void
    {
        $context = $this->context();
        $payload = $this->payload($context, ['purchase_order_number' => 'PO-MANUAL-1']);

        $this->postJson('/api/v1/purchase/orders', $payload)->assertCreated();
        $this->postJson('/api/v1/purchase/orders', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('error.message', 'Purchase order number already exists for this tenant.');
    }

    public function test_validation_errors_for_missing_lines_and_invalid_quantity(): void
    {
        $context = $this->context();

        $this->postJson('/api/v1/purchase/orders', $this->payload($context, ['lines' => []]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lines']);

        $this->postJson('/api/v1/purchase/orders', $this->payload($context, [
            'lines' => [[
                'item_id' => $context['item_id'],
                'uom_id' => $context['uom_id'],
                'ordered_quantity' => '0.000000',
                'unit_price' => '10.000000',
            ]],
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['lines.0.ordered_quantity']);
    }

    public function test_update_and_delete_draft_purchase_order(): void
    {
        $context = $this->context();
        $created = $this->postJson('/api/v1/purchase/orders', $this->payload($context))->json('data');

        $this->putJson('/api/v1/purchase/orders/'.$created['id'], $this->payload($context, ['notes' => 'Updated draft']))
            ->assertOk()
            ->assertJsonPath('data.notes', 'Updated draft');

        $this->deleteJson('/api/v1/purchase/orders/'.$created['id'], ['tenant_id' => $context['tenant_id']])
            ->assertNoContent();
    }

    public function test_approve_cancel_and_close_purchase_orders(): void
    {
        $context = $this->context();
        $approveId = $this->postJson('/api/v1/purchase/orders', $this->payload($context))->json('data.id');
        $cancelId = $this->postJson('/api/v1/purchase/orders', $this->payload($context))->json('data.id');
        $closeId = $this->postJson('/api/v1/purchase/orders', $this->payload($context))->json('data.id');

        $this->patchJson('/api/v1/purchase/orders/'.$approveId.'/approve', ['tenant_id' => $context['tenant_id']])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->patchJson('/api/v1/purchase/orders/'.$cancelId.'/cancel', ['tenant_id' => $context['tenant_id']])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->patchJson('/api/v1/purchase/orders/'.$closeId.'/approve', ['tenant_id' => $context['tenant_id']])->assertOk();
        $this->patchJson('/api/v1/purchase/orders/'.$closeId.'/close', ['tenant_id' => $context['tenant_id']])
            ->assertOk()
            ->assertJsonPath('data.status', 'closed');
    }

    public function test_invalid_status_transition_is_prevented(): void
    {
        $context = $this->context();
        $id = $this->postJson('/api/v1/purchase/orders', $this->payload($context))->json('data.id');

        $this->patchJson('/api/v1/purchase/orders/'.$id.'/close', ['tenant_id' => $context['tenant_id']])
            ->assertUnprocessable()
            ->assertJsonPath('error.message', 'Invalid purchase order status transition.');
    }

    public function test_received_purchase_order_cannot_be_cancelled(): void
    {
        $context = $this->context();
        $id = $this->postJson('/api/v1/purchase/orders', $this->payload($context))->json('data.id');
        $order = PurchaseOrder::query()->with('lines')->findOrFail($id);
        app(PurchaseOrderService::class)->approve($order);
        app(PurchaseOrderService::class)->applyReceived($order->lines->first(), '1.000000');

        $this->patchJson('/api/v1/purchase/orders/'.$id.'/cancel', ['tenant_id' => $context['tenant_id']])
            ->assertUnprocessable()
            ->assertJsonPath('error.message', 'Purchase orders with received or invoiced quantities cannot be cancelled.');
    }

    public function test_purchase_order_resource_exposes_quantity_aggregates(): void
    {
        $context = $this->context();
        $id = $this->postJson('/api/v1/purchase/orders', $this->payload($context))
            ->json('data.id');
        $order = PurchaseOrder::query()->with('lines')->findOrFail($id);
        $service = app(PurchaseOrderService::class);
        $service->approve($order);
        $service->applyReceived($order->lines->first(), '1.000000');
        $service->applyInvoiced($order->lines->first()->refresh(), '0.500000');
        $service->applyReturned($order->lines->first()->refresh(), '0.250000');

        $this->getJson('/api/v1/purchase/orders/'.$id.'?tenant_id='.$context['tenant_id'])
            ->assertOk()
            ->assertJsonPath('data.received_quantity', '1.000000')
            ->assertJsonPath('data.invoiced_quantity', '0.500000')
            ->assertJsonPath('data.returned_quantity', '0.250000');
    }

    public function test_tenant_isolation_is_enforced_for_references(): void
    {
        $context = $this->context();
        $other = $this->context('OTHER');

        $this->postJson('/api/v1/purchase/orders', $this->payload($context, [
            'lines' => [[
                'item_id' => $other['item_id'],
                'uom_id' => $context['uom_id'],
                'ordered_quantity' => '1.000000',
                'unit_price' => '10.000000',
            ]],
        ]))->assertUnprocessable()
            ->assertJsonPath('error.message', 'Purchase reference belongs to a different tenant.');
    }

    private function payload(array $context, array $overrides = []): array
    {
        return array_replace([
            'tenant_id' => $context['tenant_id'],
            'purchase_order_date' => '2026-06-07',
            'expected_delivery_date' => '2026-06-08',
            'supplier_type' => 'supplier',
            'supplier_id' => $context['supplier_id'],
            'warehouse_id' => $context['warehouse_id'],
            'exchange_rate' => '1.000000',
            'notes' => 'API test PO',
            'lines' => [[
                'item_id' => $context['item_id'],
                'uom_id' => $context['uom_id'],
                'ordered_quantity' => '2.000000',
                'unit_price' => '50.000000',
                'discount_amount' => '1.000000',
                'tax_amount' => '2.000000',
                'charge_amount' => '0.100000',
            ]],
        ], $overrides);
    }

    private function context(string $suffix = ''): array
    {
        $suffix = $suffix !== '' ? $suffix : Str::upper(Str::random(4));
        $tenantId = $this->createTenant($suffix);
        $uomCode = 'PCS-'.$suffix;
        $supplierCode = 'SUP-'.$suffix;
        $warehouseCode = 'WH-'.$suffix;
        $itemCode = 'ITM-'.$suffix;
        $uomId = $this->createUom($tenantId, $uomCode);

        return [
            'tenant_id' => $tenantId,
            'uom_id' => $uomId,
            'uom_code' => $uomCode,
            'supplier_id' => $this->createSupplier($tenantId, $supplierCode),
            'supplier_code' => $supplierCode,
            'warehouse_id' => $this->createWarehouse($tenantId, $warehouseCode),
            'warehouse_code' => $warehouseCode,
            'item_id' => $this->createItem($tenantId, $itemCode, $uomId),
            'item_code' => $itemCode,
        ];
    }

    private function createTenant(string $suffix): int
    {
        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-PO-'.$suffix,
            'name' => 'PO Tenant '.$suffix,
            'slug' => 'po-tenant-'.Str::lower($suffix),
            'status' => 'active',
            'is_active' => true,
            'is_isolated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUom(int $tenantId, string $code): int
    {
        return (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'code' => $code,
            'name' => 'Unit '.$code,
            'symbol' => 'pcs',
            'type' => 'unit',
            'category' => 'quantity',
            'decimal_precision' => 6,
            'allow_fractional_quantity' => true,
            'is_base' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSupplier(int $tenantId, string $code): int
    {
        return (int) DB::table('suppliers')->insertGetId([
            'tenant_id' => $tenantId,
            'supplier_number' => $code,
            'code' => $code,
            'name' => 'Supplier '.$code,
            'display_name' => 'Supplier '.$code,
            'supplier_type' => 'local',
            'status' => 'active',
            'is_credit_allowed' => true,
            'is_advance_allowed' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createWarehouse(int $tenantId, string $code): int
    {
        return (int) DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'name' => 'Warehouse '.$code,
            'code' => $code,
            'type' => 'standard',
            'is_active' => true,
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createItem(int $tenantId, string $code, int $uomId): int
    {
        return (int) DB::table('items')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => 'Item '.$code,
            'item_type' => 'stock',
            'tracking_type' => 'none',
            'costing_method' => 'fifo',
            'base_uom_id' => $uomId,
            'is_stockable' => true,
            'is_combo' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
