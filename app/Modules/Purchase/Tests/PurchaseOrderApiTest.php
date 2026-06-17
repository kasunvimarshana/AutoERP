<?php

declare(strict_types=1);

namespace Modules\Purchase\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Contracts\PasswordHasherInterface;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Services\PurchaseAuthorizationService;
use Modules\Purchase\Services\PurchaseOrderService;
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
            ->assertJsonPath('data.received_quantity', '1.000000')
            ->assertJsonPath('data.invoiced_quantity', '0.500000')
            ->assertJsonPath('data.returned_quantity', '0.250000');
    }

    public function test_tenant_isolation_is_enforced_for_references(): void
    {
        $context = $this->context();
        $other = $this->context('OTHER');

        $this->withAuth($context)->postJson('/api/v1/purchase/orders', $this->payload($context, [
            'lines' => [[
                'item_id' => $other['item_id'],
                'uom_id' => $context['uom_id'],
                'ordered_quantity' => '1.000000',
                'unit_price' => '10.000000',
            ]],
        ]))->assertUnprocessable()
            ->assertJsonPath('error.message', 'Purchase reference belongs to a different tenant.');
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

    private function createUom(int $tenantId, int $organizationUnitId, string $code): int
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

    private function createSupplier(int $tenantId, int $organizationUnitId, string $code): int
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

    private function createWarehouse(int $tenantId, int $organizationUnitId, string $code): int
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

    private function createItem(int $tenantId, int $organizationUnitId, string $code, int $uomId): int
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
     * @return array{token: string}
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

        return ['token' => $token];
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
