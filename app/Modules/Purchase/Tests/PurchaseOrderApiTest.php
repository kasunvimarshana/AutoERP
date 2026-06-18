<?php

declare(strict_types=1);

namespace Modules\Purchase\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Contracts\PasswordHasherInterface;
use Modules\Purchase\DTOs\CreateGoodsReceiptNoteData;
use Modules\Purchase\DTOs\CreatePurchaseOrderData;
use Modules\Purchase\DTOs\GoodsReceiptNoteLineData;
use Modules\Purchase\DTOs\PurchaseOrderLineData;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Services\GoodsReceiptNoteService;
use Modules\Purchase\Services\PurchaseAuthorizationService;
use Modules\Purchase\Services\PurchaseOrderService;
use Modules\User\Services\UserAccessResolver;
use Tests\TestCase;

final class PurchaseOrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_create_purchase_order_with_lines_returns_readable_resource(): void
    {
        $context = $this->context();

        $response = $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context));

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

        $response = $this->withAuth($context)->postJson('/api/v1/purchase/orders', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.lines.0.line_total', '7.000003')
            ->assertJsonPath('data.adjustment_total', '0.500000')
            ->assertJsonPath('data.grand_total', '7.500003');
    }

    public function test_duplicate_purchase_order_number_is_prevented(): void
    {
        $context = $this->context();
        $payload = $this->payload($context, ['purchase_order_number' => 'PO-MANUAL-1']);

        $this->withAuth($context)->postJson('/api/v1/purchase/orders', $payload)->assertCreated();
        $this->withAuth($context)->postJson('/api/v1/purchase/orders', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('error.message', 'Purchase order number already exists for this tenant.');
    }

    public function test_validation_errors_for_missing_lines_and_invalid_quantity(): void
    {
        $context = $this->context();

        $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context, ['lines' => []]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lines']);

        $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context, [
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
        $created = $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context))->json('data');

        $this->withAuth($context)->putJson('/api/v1/purchase/orders/'.$created['id'], $this->payload($context, ['notes' => 'Updated draft']))
            ->assertOk()
            ->assertJsonPath('data.notes', 'Updated draft');

        $this->withAuth($context)->deleteJson('/api/v1/purchase/orders/'.$created['id'], ['tenant_id' => $context['tenant_id']])
            ->assertNoContent();
    }

    public function test_approve_cancel_and_close_purchase_orders(): void
    {
        $context = $this->context();
        $approveId = $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context))->json('data.id');
        $cancelId = $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context))->json('data.id');
        $closeId = $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context))->json('data.id');

        $this->withAuth($context)->patchJson('/api/v1/purchase/orders/'.$approveId.'/submit', ['tenant_id' => $context['tenant_id']])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending_approval');
        $this->withAuth($context)->patchJson('/api/v1/purchase/orders/'.$approveId.'/approve', ['tenant_id' => $context['tenant_id']])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->withAuth($context)->patchJson('/api/v1/purchase/orders/'.$cancelId.'/cancel', ['tenant_id' => $context['tenant_id']])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->withAuth($context)->patchJson('/api/v1/purchase/orders/'.$closeId.'/submit', ['tenant_id' => $context['tenant_id']])->assertOk();
        $this->withAuth($context)->patchJson('/api/v1/purchase/orders/'.$closeId.'/approve', ['tenant_id' => $context['tenant_id']])->assertOk();
        $closeOrder = PurchaseOrder::query()->with('lines')->findOrFail($closeId);
        app(PurchaseOrderService::class)->applyReceived($closeOrder->lines->first(), '2.000000');
        app(PurchaseOrderService::class)->applyInvoiced($closeOrder->lines->first(), '2.000000');
        $this->withAuth($context)->patchJson('/api/v1/purchase/orders/'.$closeId.'/close', ['tenant_id' => $context['tenant_id']])
            ->assertOk()
            ->assertJsonPath('data.status', 'closed');
    }

    public function test_invalid_status_transition_is_prevented(): void
    {
        $context = $this->context();
        $id = $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context))->json('data.id');

        $this->withAuth($context)->patchJson('/api/v1/purchase/orders/'.$id.'/close', ['tenant_id' => $context['tenant_id']])
            ->assertUnprocessable()
            ->assertJsonPath('error.message', 'Invalid purchase order status transition.');
    }

    public function test_received_purchase_order_cannot_be_cancelled(): void
    {
        $context = $this->context();
        $id = $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context))->json('data.id');
        $order = PurchaseOrder::query()->with('lines')->findOrFail($id);
        $service = app(PurchaseOrderService::class);
        $service->approve($service->submit($order));
        $service->applyReceived($order->lines->first(), '1.000000');

        $this->withAuth($context)->patchJson('/api/v1/purchase/orders/'.$id.'/cancel', ['tenant_id' => $context['tenant_id']])
            ->assertUnprocessable()
            ->assertJsonPath('error.message', 'Purchase orders with received or invoiced quantities cannot be cancelled.');
    }

    public function test_purchase_order_resource_exposes_quantity_aggregates(): void
    {
        $context = $this->context();
        $id = $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context))
            ->json('data.id');
        $order = PurchaseOrder::query()->with('lines')->findOrFail($id);
        $service = app(PurchaseOrderService::class);
        $service->approve($service->submit($order));
        $service->applyReceived($order->lines->first(), '1.000000');
        $service->applyInvoiced($order->lines->first()->refresh(), '0.500000');
        $service->applyReturned($order->lines->first()->refresh(), '0.250000');

        $this->withAuth($context)->getJson('/api/v1/purchase/orders/'.$id.'?tenant_id='.$context['tenant_id'])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.receipt_status', 'partially_received')
            ->assertJsonPath('data.invoice_status', 'partially_invoiced')
            ->assertJsonPath('data.return_status', 'partially_returned')
            ->assertJsonPath('data.capabilities.can_receive', true)
            ->assertJsonPath('data.received_quantity', '1.000000')
            ->assertJsonPath('data.invoiced_quantity', '0.500000')
            ->assertJsonPath('data.returned_quantity', '0.250000');
    }

    public function test_purchase_order_create_context_uses_tenant_currency_and_warehouse_defaults(): void
    {
        $context = $this->context();
        $currencyId = $this->createCurrency('BASE-'.Str::upper(Str::random(4)));
        $locationId = $this->createWarehouseLocation(
            $context['tenant_id'],
            $context['organization_unit_id'],
            $context['warehouse_id'],
            'DEFAULT-'.Str::upper(Str::random(4)),
            isDefault: true,
        );
        DB::table('tenants')->where('id', $context['tenant_id'])->update(['currency_id' => $currencyId]);
        DB::table('warehouses')->where('id', $context['warehouse_id'])->update(['is_default' => true]);

        $this->withAuth($context)->getJson('/api/v1/purchase/orders/create-context')
            ->assertOk()
            ->assertJsonPath('data.defaults.currency_id', $currencyId)
            ->assertJsonPath('data.defaults.currency_source', 'tenant_default')
            ->assertJsonPath('data.defaults.exchange_rate', '1.000000')
            ->assertJsonPath('data.exchange_rate_context.foreign_currency_behavior', 'manual_required')
            ->assertJsonPath('data.defaults.warehouse_id', $context['warehouse_id'])
            ->assertJsonPath('data.defaults.warehouse_location_id', $locationId)
            ->assertJsonPath('data.defaults.warehouse_location_source', 'warehouse_default');
    }

    public function test_referenced_purchase_return_http_contract_derives_backend_controlled_fields(): void
    {
        $context = $this->context();
        $orders = app(PurchaseOrderService::class);
        $order = $orders->create(new CreatePurchaseOrderData(
            tenantId: $context['tenant_id'],
            purchaseOrderDate: '2026-06-18',
            organizationUnitId: $context['organization_unit_id'],
            supplierType: 'supplier',
            supplierId: $context['supplier_id'],
            warehouseId: $context['warehouse_id'],
            lines: [new PurchaseOrderLineData($context['item_id'], '2.000000', '10.000000', uomId: $context['uom_id'])],
        ));
        $order = $orders->approve($orders->submit($order));
        $grn = app(GoodsReceiptNoteService::class)->create(new CreateGoodsReceiptNoteData(
            tenantId: $context['tenant_id'],
            receivedDate: '2026-06-18',
            warehouseId: $context['warehouse_id'],
            organizationUnitId: $context['organization_unit_id'],
            purchaseOrderId: (int) $order->getKey(),
            lines: [new GoodsReceiptNoteLineData($context['item_id'], '2.000000', '2.000000', '10.000000', purchaseOrderLineId: (int) $order->lines->first()->getKey(), orderedQuantity: '2.000000')],
        ));
        $grn = app(GoodsReceiptNoteService::class)->post($grn)->load('lines');

        $invalidPayload = [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'return_date' => '2026-06-19',
            'return_type' => 'referenced',
            'source_id' => (int) $grn->getKey(),
            'warehouse_id' => $context['warehouse_id'],
            'approval_required' => true,
            'affects_supplier_balance' => false,
            'lines' => [[
                'source_line_type' => 'goods_receipt_note_line',
                'source_line_id' => (int) $grn->lines->first()->getKey(),
                'returned_quantity' => '1.000000',
                'item_id' => $context['item_id'],
            ]],
        ];

        $this->withAuth($context)->postJson('/api/v1/purchase/returns', $invalidPayload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'warehouse_id',
                'approval_required',
                'affects_supplier_balance',
                'lines.0.item_id',
            ]);

        $validPayload = [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'return_date' => '2026-06-19',
            'return_type' => 'referenced',
            'source_id' => (int) $grn->getKey(),
            'lines' => [[
                'source_line_type' => 'goods_receipt_note_line',
                'source_line_id' => (int) $grn->lines->first()->getKey(),
                'returned_quantity' => '1.000000',
                'reason' => 'Damaged',
            ]],
        ];

        $this->withAuth($context)->postJson('/api/v1/purchase/returns', $validPayload)
            ->assertCreated()
            ->assertJsonPath('data.supplier.id', $context['supplier_id'])
            ->assertJsonPath('data.warehouse.id', $context['warehouse_id'])
            ->assertJsonPath('data.approval_required', false)
            ->assertJsonPath('data.affects_supplier_balance', true)
            ->assertJsonMissingPath('data.capabilities.can_reverse');
    }

    public function test_manual_supplier_return_requires_manual_permission_and_separate_route(): void
    {
        $context = $this->context('RTNSEC', [PurchaseAuthorizationService::ORDERS_VIEW]);
        $manualPayload = $this->manualReturnPayload($context);

        $this->withAuth($context)->postJson('/api/v1/purchase/manual-supplier-returns', $manualPayload)
            ->assertForbidden();

        $this->grantPurchasePermission($context, PurchaseAuthorizationService::RETURNS_CREATE);

        $this->withAuth($context)->postJson('/api/v1/purchase/manual-supplier-returns', $manualPayload)
            ->assertForbidden();

        $this->withAuth($context)->postJson('/api/v1/purchase/returns', $manualPayload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['return_type', 'warehouse_id', 'supplier_id']);

        $this->grantPurchasePermission($context, PurchaseAuthorizationService::RETURNS_CREATE_MANUAL);

        $this->withAuth($context)->postJson('/api/v1/purchase/manual-supplier-returns', $manualPayload)
            ->assertCreated()
            ->assertJsonPath('data.return_type', 'manual_supplier_return')
            ->assertJsonPath('data.approval_required', true)
            ->assertJsonPath('data.affects_supplier_balance', true)
            ->assertJsonPath('data.lines.0.client_line_key', 'manual-line-1')
            ->assertJsonPath('data.lines.0.source_line_id', null);
    }

    public function test_manual_supplier_return_contract_rejects_mixed_sources_and_cross_scope_records(): void
    {
        $context = $this->context('RTNSCOPE', [PurchaseAuthorizationService::RETURNS_CREATE_MANUAL]);
        $other = $this->context('RTNFOREIGN', [PurchaseAuthorizationService::RETURNS_CREATE_MANUAL]);

        $mixedPayload = $this->manualReturnPayload($context, [
            'return_type' => 'referenced',
            'lines' => [[
                'client_line_key' => 'manual-line-1',
                'source_line_type' => 'goods_receipt_note_line',
                'source_line_id' => 1,
                'item_id' => $context['item_id'],
                'uom_id' => $context['uom_id'],
                'returned_quantity' => '1.000000',
                'cost_basis' => '10.000000',
            ]],
        ]);

        $this->withAuth($context)->postJson('/api/v1/purchase/manual-supplier-returns', $mixedPayload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['return_type', 'lines.0.source_line_type', 'lines.0.source_line_id']);

        $crossScopePayload = $this->manualReturnPayload($context, [
            'supplier_id' => $other['supplier_id'],
            'warehouse_id' => $other['warehouse_id'],
            'lines' => [[
                'client_line_key' => 'manual-line-1',
                'item_id' => $other['item_id'],
                'uom_id' => $other['uom_id'],
                'returned_quantity' => '1.000000',
                'cost_basis' => '10.000000',
            ]],
        ]);

        $this->withAuth($context)->postJson('/api/v1/purchase/manual-supplier-returns', $crossScopePayload)
            ->assertUnprocessable();
    }

    public function test_manual_supplier_return_rejects_client_controlled_supplier_type(): void
    {
        $context = $this->context('RTNSPOOF', [PurchaseAuthorizationService::RETURNS_CREATE_MANUAL]);

        $this->withAuth($context)->postJson('/api/v1/purchase/manual-supplier-returns', $this->manualReturnPayload($context, [
            'supplier_type' => 'local',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['supplier_type']);
    }

    public function test_multi_line_manual_supplier_return_does_not_require_fake_source_ids(): void
    {
        $context = $this->context('RTNMULTI', [PurchaseAuthorizationService::RETURNS_CREATE_MANUAL]);
        $payload = $this->manualReturnPayload($context, [
            'lines' => [
                [
                    'client_line_key' => 'manual-line-1',
                    'item_id' => $context['item_id'],
                    'uom_id' => $context['uom_id'],
                    'returned_quantity' => '1.000000',
                    'cost_basis' => '10.000000',
                ],
                [
                    'client_line_key' => 'manual-line-2',
                    'item_id' => $context['item_id'],
                    'uom_id' => $context['uom_id'],
                    'returned_quantity' => '2.000000',
                    'cost_basis' => '11.000000',
                    'reason' => 'Different cost batch',
                ],
            ],
        ]);

        $response = $this->withAuth($context)->postJson('/api/v1/purchase/manual-supplier-returns', $payload)
            ->assertCreated()
            ->assertJsonCount(2, 'data.lines')
            ->assertJsonPath('data.lines.0.line_number', 1)
            ->assertJsonPath('data.lines.1.line_number', 2)
            ->assertJsonPath('data.lines.0.source_line_type', null)
            ->assertJsonPath('data.lines.1.source_line_id', null);

        $this->assertDatabaseHas('purchase_return_lines', [
            'purchase_return_id' => $response->json('data.id'),
            'line_number' => 2,
            'client_line_key' => 'manual-line-2',
            'source_line_type' => null,
            'source_line_id' => null,
        ]);

        $duplicatePayload = $this->manualReturnPayload($context, [
            'lines' => [
                [
                    'client_line_key' => 'duplicate',
                    'item_id' => $context['item_id'],
                    'uom_id' => $context['uom_id'],
                    'returned_quantity' => '1.000000',
                    'cost_basis' => '10.000000',
                ],
                [
                    'client_line_key' => 'duplicate',
                    'item_id' => $context['item_id'],
                    'uom_id' => $context['uom_id'],
                    'returned_quantity' => '1.000000',
                    'cost_basis' => '10.000000',
                ],
            ],
        ]);

        $this->withAuth($context)->postJson('/api/v1/purchase/manual-supplier-returns', $duplicatePayload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lines.1.client_line_key']);
    }

    public function test_item_purchase_context_uses_item_scoped_uom_and_purchase_price(): void
    {
        $context = $this->context();
        $currencyId = $this->createCurrency('PRC-'.Str::upper(Str::random(4)));
        $purchaseUomId = $this->createUom(
            $context['tenant_id'],
            $context['organization_unit_id'],
            'BOX-'.Str::upper(Str::random(4)),
        );
        $this->createItemUnit($context['tenant_id'], $context['organization_unit_id'], $context['item_id'], $context['uom_id'], 'base');
        $this->createItemUnit($context['tenant_id'], $context['organization_unit_id'], $context['item_id'], $purchaseUomId, 'purchase', true);

        DB::table('supplier_item_mappings')->insert([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'supplier_id' => $context['supplier_id'],
            'item_id' => $context['item_id'],
            'default_purchase_uom_id' => $purchaseUomId,
            'minimum_order_quantity' => '0.000000',
            'is_preferred' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('item_prices')->insert([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'item_id' => $context['item_id'],
            'price_type' => 'purchase',
            'currency_id' => $currencyId,
            'uom_id' => $purchaseUomId,
            'amount' => '12.500000',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withAuth($context)->getJson(sprintf(
            '/api/v1/purchase/items/%d/purchase-context?supplier_id=%d&currency_id=%d',
            $context['item_id'],
            $context['supplier_id'],
            $currencyId,
        ));

        $response->assertOk()
            ->assertJsonPath('data.default_purchase_uom_id', $purchaseUomId)
            ->assertJsonPath('data.unit_price', '12.500000')
            ->assertJsonPath('data.price_source', 'purchase_price_list')
            ->assertJsonPath('data.supplier_mapping.default_purchase_uom_id', $purchaseUomId);

        $this->assertSame(
            [$purchaseUomId, $context['uom_id']],
            collect($response->json('data.allowed_purchase_uoms'))->pluck('id')->all(),
        );
    }

    public function test_adjustment_catalogue_and_effect_matrix_are_authoritative(): void
    {
        $context = $this->context();

        $catalogue = $this->withAuth($context)->getJson('/api/v1/purchase/adjustments/catalogue')
            ->assertOk()
            ->json('data');
        $discount = collect($catalogue)->firstWhere('type', 'discount');
        $freight = collect($catalogue)->firstWhere('type', 'freight');

        $this->assertSame('Order Discount', $discount['default_name'] ?? null);
        $this->assertSame(['decrease'], $discount['allowed_effects'] ?? null);
        $this->assertSame('Freight-in / landed cost', $freight['finance_mapping_label'] ?? null);

        $response = $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context, [
            'adjustments' => [[
                'name' => 'Invalid discount',
                'adjustment_type' => 'discount',
                'effect' => 'increase',
                'amount' => '1.000000',
            ]],
        ]));

        $response->assertUnprocessable()->assertJsonValidationErrors(['adjustments.0.effect']);
        $this->assertSame(
            'Order Discount adjustments cannot use the increase effect.',
            $response->json('errors')['adjustments.0.effect'][0] ?? null,
        );
    }

    public function test_tenant_base_currency_requires_exchange_rate_one(): void
    {
        $context = $this->context();
        $currencyId = $this->createCurrency('RTE-'.Str::upper(Str::random(4)));
        DB::table('tenants')->where('id', $context['tenant_id'])->update(['currency_id' => $currencyId]);

        $response = $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context, [
            'currency_id' => $currencyId,
            'exchange_rate' => '1.250000',
        ]));

        $response->assertUnprocessable()->assertJsonValidationErrors(['exchange_rate']);
        $this->assertSame(
            'Tenant base currency must use an exchange rate of 1.000000.',
            $response->json('errors.exchange_rate.0'),
        );
    }

    public function test_active_global_currency_can_be_used_without_frontend_tenant_override(): void
    {
        $context = $this->context();
        $currencyId = $this->createCurrency('CUR-'.Str::upper(Str::random(5)));
        $payload = $this->payload($context, [
            'purchase_order_date' => '2026-06-18',
            'expected_delivery_date' => '2026-06-19',
            'currency_id' => $currencyId,
            'lines' => [[
                'item_id' => $context['item_id'],
                'uom_id' => $context['uom_id'],
                'ordered_quantity' => '1.000000',
                'unit_price' => '10.000000',
            ]],
        ]);
        unset($payload['tenant_id']);

        $this->withAuth($context)->postJson('/api/v1/purchase/orders', $payload)
            ->assertCreated()
            ->assertJsonPath('data.currency_id', $currencyId)
            ->assertJsonPath('data.grand_total', '10.000000');
    }

    public function test_currency_reference_errors_are_field_specific(): void
    {
        $context = $this->context();
        $inactiveCurrencyId = $this->createCurrency('INACT-'.Str::upper(Str::random(4)), active: false);

        $inactive = $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context, [
            'currency_id' => $inactiveCurrencyId,
        ]));
        $inactive->assertUnprocessable()->assertJsonValidationErrors(['currency_id']);
        $this->assertSame('The selected currency is not active.', $inactive->json('errors.currency_id.0'));
        $this->assertNotSame('Purchase reference belongs to a different tenant.', $inactive->json('error.message'));

        $unknown = $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context, [
            'currency_id' => 999999,
        ]));
        $unknown->assertUnprocessable()->assertJsonValidationErrors(['currency_id']);
        $this->assertSame('The selected currency is not available.', $unknown->json('errors.currency_id.0'));
        $this->assertNotSame('Purchase reference belongs to a different tenant.', $unknown->json('error.message'));
    }

    public function test_tenant_isolation_is_enforced_for_purchase_references(): void
    {
        $context = $this->context();
        $other = $this->context('OTHER');

        $supplier = $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context, [
            'supplier_id' => $other['supplier_id'],
        ]));
        $supplier->assertUnprocessable()->assertJsonValidationErrors(['supplier_id']);
        $this->assertSame('The selected supplier is not available.', $supplier->json('errors.supplier_id.0'));

        $item = $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context, [
            'lines' => [[
                'item_id' => $other['item_id'],
                'uom_id' => $context['uom_id'],
                'ordered_quantity' => '1.000000',
                'unit_price' => '10.000000',
            ]],
        ]));
        $item->assertUnprocessable()->assertJsonValidationErrors(['lines.0.item_id']);
        $this->assertSame('The selected item is not available.', $item->json('errors')['lines.0.item_id'][0] ?? null);

        $uom = $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context, [
            'lines' => [[
                'item_id' => $context['item_id'],
                'uom_id' => $other['uom_id'],
                'ordered_quantity' => '1.000000',
                'unit_price' => '10.000000',
            ]],
        ]));
        $uom->assertUnprocessable()->assertJsonValidationErrors(['lines.0.uom_id']);
        $this->assertSame('The selected UOM is not available.', $uom->json('errors')['lines.0.uom_id'][0] ?? null);

        $warehouse = $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context, [
            'warehouse_id' => $other['warehouse_id'],
        ]));
        $warehouse->assertUnprocessable()->assertJsonValidationErrors(['warehouse_id']);
        $this->assertSame('The selected warehouse is not available.', $warehouse->json('errors.warehouse_id.0'));
    }

    public function test_item_variant_must_match_selected_item_and_scope(): void
    {
        $context = $this->context();
        $other = $this->context('VAROTHER');
        $secondItemId = $this->createItem(
            $context['tenant_id'],
            $context['organization_unit_id'],
            'ITM-VAR-'.Str::upper(Str::random(4)),
            $context['uom_id'],
        );
        $otherItemVariantId = $this->createItemVariant(
            $context['tenant_id'],
            $context['organization_unit_id'],
            $secondItemId,
            'VAR-'.Str::upper(Str::random(4)),
        );
        $crossTenantVariantId = $this->createItemVariant(
            $other['tenant_id'],
            $other['organization_unit_id'],
            $other['item_id'],
            'VAR-'.Str::upper(Str::random(4)),
        );

        $wrongItem = $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context, [
            'lines' => [[
                'item_id' => $context['item_id'],
                'item_variant_id' => $otherItemVariantId,
                'uom_id' => $context['uom_id'],
                'ordered_quantity' => '1.000000',
                'unit_price' => '10.000000',
            ]],
        ]));
        $wrongItem->assertUnprocessable()->assertJsonValidationErrors(['lines.0.item_variant_id']);
        $this->assertSame(
            'The selected item variant does not belong to the selected item.',
            $wrongItem->json('errors')['lines.0.item_variant_id'][0] ?? null,
        );

        $crossTenant = $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context, [
            'lines' => [[
                'item_id' => $context['item_id'],
                'item_variant_id' => $crossTenantVariantId,
                'uom_id' => $context['uom_id'],
                'ordered_quantity' => '1.000000',
                'unit_price' => '10.000000',
            ]],
        ]));
        $crossTenant->assertUnprocessable()->assertJsonValidationErrors(['lines.0.item_variant_id']);
        $this->assertSame('The selected item variant is not available.', $crossTenant->json('errors')['lines.0.item_variant_id'][0] ?? null);
    }

    public function test_organization_scoped_records_and_tenant_level_records_are_handled_consistently(): void
    {
        $context = $this->context();
        $otherOrganizationUnitId = $this->createOrganizationUnit($context['tenant_id'], 'ORG-B-'.Str::upper(Str::random(4)));
        $tenantLevelUomId = $this->createUom($context['tenant_id'], null, 'TEN-UOM-'.Str::upper(Str::random(4)));
        $tenantLevelItemId = $this->createItem(
            $context['tenant_id'],
            $context['organization_unit_id'],
            'TEN-ITM-'.Str::upper(Str::random(4)),
            $tenantLevelUomId,
        );
        $sameOrgLocationId = $this->createWarehouseLocation(
            $context['tenant_id'],
            $context['organization_unit_id'],
            $context['warehouse_id'],
            'LOC-A-'.Str::upper(Str::random(4)),
        );
        $otherOrgWarehouseId = $this->createWarehouse(
            $context['tenant_id'],
            $otherOrganizationUnitId,
            'WH-ORG-B-'.Str::upper(Str::random(4)),
        );
        $otherOrgLocationId = $this->createWarehouseLocation(
            $context['tenant_id'],
            $otherOrganizationUnitId,
            $context['warehouse_id'],
            'LOC-B-'.Str::upper(Str::random(4)),
        );

        $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context, [
            'warehouse_location_id' => $sameOrgLocationId,
            'lines' => [[
                'item_id' => $tenantLevelItemId,
                'uom_id' => $tenantLevelUomId,
                'ordered_quantity' => '1.000000',
                'unit_price' => '10.000000',
            ]],
        ]))->assertCreated();

        $warehouse = $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context, [
            'warehouse_id' => $otherOrgWarehouseId,
        ]));
        $warehouse->assertUnprocessable()->assertJsonValidationErrors(['warehouse_id']);
        $this->assertSame(
            'The selected warehouse is not available for this organization unit.',
            $warehouse->json('errors.warehouse_id.0'),
        );

        $location = $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context, [
            'warehouse_location_id' => $otherOrgLocationId,
        ]));
        $location->assertUnprocessable()->assertJsonValidationErrors(['warehouse_location_id']);
        $this->assertSame(
            'The selected warehouse location is not available for this organization unit.',
            $location->json('errors.warehouse_location_id.0'),
        );
    }

    public function test_purchase_lookups_do_not_return_cross_tenant_data_and_keep_global_currencies_available(): void
    {
        $context = $this->context();
        $other = $this->context('LOOKUPOTHER');
        $activeCurrencyCode = 'LOOK-'.Str::upper(Str::random(4));
        $inactiveCurrencyCode = 'NOLOOK-'.Str::upper(Str::random(4));
        $this->createCurrency($activeCurrencyCode);
        $this->createCurrency($inactiveCurrencyCode, active: false);

        $fastPurchaseContext = $this->withAuth($context)->getJson('/api/v1/purchase/fast-purchases/context');
        $fastPurchaseContext->assertOk();
        $currencyCodes = collect($fastPurchaseContext->json('data.currencies'))->pluck('code');
        $warehouseCodes = collect($fastPurchaseContext->json('data.warehouses'))->pluck('code');
        $this->assertTrue($currencyCodes->contains($activeCurrencyCode));
        $this->assertFalse($currencyCodes->contains($inactiveCurrencyCode));
        $this->assertFalse($warehouseCodes->contains($other['warehouse_code']));

        $supplierLookup = $this->withAuth($context)->getJson('/api/v1/suppliers/lookup/active?search='.$other['supplier_code']);
        $supplierLookup->assertOk();
        $this->assertFalse(collect($supplierLookup->json('data'))->pluck('code')->contains($other['supplier_code']));
    }

    public function test_exact_purchase_order_permissions_are_enforced(): void
    {
        $viewer = $this->context('VIEW', [PurchaseAuthorizationService::ORDERS_VIEW]);

        $this->withAuth($viewer)->getJson('/api/v1/purchase/orders')->assertOk();
        $this->withAuth($viewer)->postJson('/api/v1/purchase/orders', $this->payload($viewer))
            ->assertForbidden();
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

    private function manualReturnPayload(array $context, array $overrides = []): array
    {
        return array_replace_recursive([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'return_date' => '2026-06-19',
            'return_type' => 'manual_supplier_return',
            'warehouse_id' => $context['warehouse_id'],
            'supplier_id' => $context['supplier_id'],
            'reason' => 'Manual supplier return',
            'lines' => [[
                'client_line_key' => 'manual-line-1',
                'item_id' => $context['item_id'],
                'uom_id' => $context['uom_id'],
                'returned_quantity' => '1.000000',
                'cost_basis' => '10.000000',
                'reason' => 'Manual line',
            ]],
        ], $overrides);
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
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createOrganizationUnit(int $tenantId, string $suffix): int
    {
        return (int) DB::table('organization_units')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Main '.$suffix,
            'code' => 'MAIN-'.$suffix,
            'path' => '/main-'.Str::lower($suffix),
            'is_active' => true,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCurrency(string $code, bool $active = true): int
    {
        return (int) DB::table('currencies')->insertGetId([
            'row_version' => 1,
            'code' => $code,
            'name' => 'Currency '.$code,
            'symbol' => substr($code, 0, 1),
            'decimal_places' => 2,
            'is_active' => $active,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUom(int $tenantId, ?int $organizationUnitId, string $code): int
    {
        return (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
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

    private function createSupplier(int $tenantId, ?int $organizationUnitId, string $code): int
    {
        return (int) DB::table('suppliers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
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

    private function createWarehouse(int $tenantId, ?int $organizationUnitId, string $code): int
    {
        return (int) DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
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

    private function createWarehouseLocation(int $tenantId, ?int $organizationUnitId, int $warehouseId, string $code, bool $active = true, bool $isDefault = false): int
    {
        return (int) DB::table('warehouse_locations')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'row_version' => 1,
            'warehouse_id' => $warehouseId,
            'name' => 'Location '.$code,
            'code' => $code,
            'type' => 'bin',
            'is_active' => $active,
            'is_default' => $isDefault,
            'is_pickable' => true,
            'is_receivable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createItem(int $tenantId, ?int $organizationUnitId, string $code, int $uomId): int
    {
        return (int) DB::table('items')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
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

    private function createItemUnit(int $tenantId, ?int $organizationUnitId, int $itemId, int $uomId, string $unitRole, bool $isDefault = false): int
    {
        return (int) DB::table('item_units')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'item_id' => $itemId,
            'uom_id' => $uomId,
            'unit_role' => $unitRole,
            'conversion_factor' => '1.000000',
            'is_default' => $isDefault,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createItemVariant(int $tenantId, ?int $organizationUnitId, int $itemId, string $code, bool $active = true): int
    {
        return (int) DB::table('item_variants')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'item_id' => $itemId,
            'code' => $code,
            'name' => 'Variant '.$code,
            'is_active' => $active,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  list<string>|null  $permissions
     */
    private function context(string $suffix = '', ?array $permissions = null): array
    {
        $suffix = $suffix !== '' ? $suffix : Str::upper(Str::random(4));
        $tenantId = $this->createTenant($suffix);
        $organizationUnitId = $this->createOrganizationUnit($tenantId, $suffix);
        $user = $this->createAuthContext($tenantId, $organizationUnitId, $suffix, $permissions);
        $uomCode = 'PCS-'.$suffix;
        $supplierCode = 'SUP-'.$suffix;
        $warehouseCode = 'WH-'.$suffix;
        $itemCode = 'ITM-'.$suffix;
        $uomId = $this->createUom($tenantId, $organizationUnitId, $uomCode);

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'token' => $user['token'],
            'user_id' => $user['user_id'],
            'role_id' => $user['role_id'],
            'uom_id' => $uomId,
            'uom_code' => $uomCode,
            'supplier_id' => $this->createSupplier($tenantId, $organizationUnitId, $supplierCode),
            'supplier_code' => $supplierCode,
            'warehouse_id' => $this->createWarehouse($tenantId, $organizationUnitId, $warehouseCode),
            'warehouse_code' => $warehouseCode,
            'item_id' => $this->createItem($tenantId, $organizationUnitId, $itemCode, $uomId),
            'item_code' => $itemCode,
        ];
    }

    /**
     * @param  list<string>|null  $permissions
     *
     * @return array{token: string, user_id: int, role_id: int}
     */
    private function createAuthContext(int $tenantId, int $organizationUnitId, string $suffix, ?array $permissions = null): array
    {
        $now = now();
        $email = 'purchase-'.Str::lower($suffix).'@example.test';
        $userId = (int) DB::table('users')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'first_name' => 'Purchase',
            'last_name' => 'Tester',
            'email' => $email,
            'password' => app(PasswordHasherInterface::class)->hash('secret-password'),
            'status' => 'active',
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('user_tenants')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
            'is_default' => true,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $roleId = (int) DB::table('roles')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'name' => 'Purchase Test Role',
            'guard_name' => 'web',
            'description' => 'Purchase test role',
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($this->seedPurchasePermissions($tenantId, $permissions ?? array_keys(PurchaseAuthorizationService::descriptions())) as $permissionId) {
            DB::table('role_permissions')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'row_version' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('user_roles')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('auth_providers')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'provider_key' => 'internal',
            'name' => 'Internal password login',
            'guard_name' => 'auth-api',
            'provider_name' => 'users',
            'driver' => 'internal',
            'status' => 'active',
            'is_sso' => false,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $token = (string) $this->postJson('/api/v1/auth/login', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'login_identifier' => $email,
            'password' => 'secret-password',
        ])->assertOk()->json('token');

        return ['token' => $token, 'user_id' => $userId, 'role_id' => $roleId];
    }

    private function grantPurchasePermission(array $context, string $permission): void
    {
        $permissionId = (int) DB::table('permissions')
            ->where('tenant_id', $context['tenant_id'])
            ->where('name', $permission)
            ->where('guard_name', 'web')
            ->value('id');

        if ($permissionId < 1) {
            $permissionId = (int) DB::table('permissions')->insertGetId([
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => null,
                'name' => $permission,
                'guard_name' => 'web',
                'module' => 'Purchase',
                'description' => PurchaseAuthorizationService::descriptions()[$permission] ?? 'Purchase test permission',
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $exists = DB::table('role_permissions')
            ->where('tenant_id', $context['tenant_id'])
            ->where('role_id', $context['role_id'])
            ->where('permission_id', $permissionId)
            ->exists();

        if (! $exists) {
            DB::table('role_permissions')->insert([
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => null,
                'role_id' => $context['role_id'],
                'permission_id' => $permissionId,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        app(UserAccessResolver::class)->forgetForUserTenant((int) $context['user_id'], (int) $context['tenant_id']);
    }

    /**
     * @param  list<string>  $names
     *
     * @return list<int>
     */
    private function seedPurchasePermissions(int $tenantId, array $names): array
    {
        $ids = [];
        foreach ($names as $name) {
            $ids[] = (int) DB::table('permissions')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'name' => $name,
                'guard_name' => 'web',
                'module' => 'Purchase',
                'description' => PurchaseAuthorizationService::descriptions()[$name] ?? 'Purchase test permission',
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $ids;
    }

    private function withAuth(array $context): self
    {
        return $this->withToken($context['token'])->withHeaders([
            'X-Tenant-Id' => (string) $context['tenant_id'],
            'X-Organization-Unit-Id' => (string) $context['organization_unit_id'],
        ]);
    }
}
