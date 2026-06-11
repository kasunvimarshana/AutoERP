<?php

declare(strict_types=1);

namespace Tests\Feature\Item;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Contracts\PasswordHasherInterface;
use Tests\TestCase;

final class ItemBaseUomTest extends TestCase
{
    use RefreshDatabase;

    public function test_unused_item_base_uom_edit_is_allowed(): void
    {
        $context = $this->context('UOM-UNUSED');
        $oldUomId = $this->uom($context, 'PCS');
        $newUomId = $this->uom($context, 'BOX');
        $itemId = $this->item($context, $oldUomId, 'UNUSED');

        $this->auth($context)->putJson("/api/v1/items/{$itemId}", ['base_uom_id' => $newUomId])
            ->assertOk()
            ->assertJsonPath('data.base_uom.code', 'BOX');

        $this->assertDatabaseHas('items', ['id' => $itemId, 'base_uom_id' => $newUomId]);
    }

    public function test_used_item_direct_edit_is_blocked_and_usage_audit_detects_inventory(): void
    {
        $context = $this->context('UOM-USED');
        $oldUomId = $this->uom($context, 'PCS');
        $newUomId = $this->uom($context, 'BOX');
        $itemId = $this->item($context, $oldUomId, 'USED');
        $warehouseId = $this->warehouse($context);
        $this->stock($context, $itemId, $warehouseId, '10.000000');

        $this->auth($context)->putJson("/api/v1/items/{$itemId}", ['base_uom_id' => $newUomId])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Base UOM cannot be edited directly after item usage. Use the Base UOM Conversion Wizard.');

        $this->auth($context)->getJson("/api/v1/items/{$itemId}/base-uom/usage-audit")
            ->assertOk()
            ->assertJsonPath('data.has_usage', true)
            ->assertJsonPath('data.can_direct_edit', false)
            ->assertJsonPath('data.affected_modules.0.module', 'inventory')
            ->assertJsonPath('data.affected_modules.0.references.stock_balances', 1);
    }

    public function test_preview_and_apply_convert_operational_state_with_decimal_strings_and_revision(): void
    {
        $context = $this->context('UOM-APPLY');
        $oldUomId = $this->uom($context, 'PCS');
        $newUomId = $this->uom($context, 'BOX');
        $itemId = $this->item($context, $oldUomId, 'APPLY');
        $warehouseId = $this->warehouse($context);
        $balanceId = $this->stock($context, $itemId, $warehouseId, '12.345678', '2.500000', '30.864195');
        $layerId = DB::table('inventory_valuation_layers')->insertGetId([
            ...$this->scope($context),
            'item_id' => $itemId,
            'base_uom_id' => $oldUomId,
            'warehouse_id' => $warehouseId,
            'valuation_method' => 'fifo',
            'original_quantity' => '12.345678',
            'remaining_quantity' => '12.345678',
            'unit_cost' => '2.500000',
            'total_cost' => '30.864195',
            'remaining_value' => '30.864195',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = ['new_base_uom_id' => $newUomId, 'conversion_factor' => '0.100000'];
        $this->auth($context)->postJson("/api/v1/items/{$itemId}/base-uom/preview-change", $payload)
            ->assertOk()
            ->assertJsonPath('data.is_valid', true)
            ->assertJsonPath('data.conversion_factor', '0.100000')
            ->assertJsonFragment([
                'metric' => 'quantity_on_hand',
                'before' => '12.345678',
                'after' => '1.234567',
            ]);

        $this->auth($context)->postJson("/api/v1/items/{$itemId}/base-uom/apply-change", [
            ...$payload,
            'reason' => 'Pack size standardization',
        ])->assertOk()
            ->assertJsonPath('data.old_base_uom.code', 'PCS')
            ->assertJsonPath('data.new_base_uom.code', 'BOX')
            ->assertJsonPath('data.status', 'applied')
            ->assertJsonPath('data.conversion_factor', '0.100000');

        $this->assertDatabaseHas('inventory_stock_balances', [
            'id' => $balanceId,
            'quantity_on_hand' => '1.234567',
            'quantity_available' => '1.234567',
            'average_cost' => '25.000000',
            'total_value' => '30.864195',
        ]);
        $this->assertDatabaseHas('inventory_valuation_layers', [
            'id' => $layerId,
            'base_uom_id' => $newUomId,
            'remaining_quantity' => '1.234567',
            'unit_cost' => '25.000000',
            'remaining_value' => '30.864195',
        ]);
        $this->assertDatabaseHas('item_base_uom_revisions', [
            'item_id' => $itemId,
            'old_base_uom_id' => $oldUomId,
            'new_base_uom_id' => $newUomId,
            'conversion_factor' => '0.100000',
            'status' => 'applied',
        ]);

        $this->auth($context)->getJson("/api/v1/items/{$itemId}/base-uom/revisions")
            ->assertOk()
            ->assertJsonPath('data.0.old_base_uom.code', 'PCS')
            ->assertJsonPath('data.0.new_base_uom.code', 'BOX');
    }

    public function test_conversion_blocks_open_reservation_without_old_uom_snapshot(): void
    {
        $context = $this->context('UOM-RES');
        $oldUomId = $this->uom($context, 'PCS');
        $newUomId = $this->uom($context, 'BOX');
        $itemId = $this->item($context, $oldUomId, 'RES');
        $warehouseId = $this->warehouse($context);
        $this->stock($context, $itemId, $warehouseId, '10.000000', '1.000000', '10.000000', '2.000000');
        DB::table('inventory_reservations')->insert([
            ...$this->scope($context),
            'reservation_number' => 'RES-1',
            'reservation_date' => now()->toDateString(),
            'item_id' => $itemId,
            'base_uom_id' => null,
            'warehouse_id' => $warehouseId,
            'quantity_reserved' => '2.000000',
            'quantity_allocated' => '0.000000',
            'quantity_released' => '0.000000',
            'quantity_remaining' => '2.000000',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auth($context)->postJson("/api/v1/items/{$itemId}/base-uom/preview-change", [
            'new_base_uom_id' => $newUomId,
            'conversion_factor' => '0.100000',
        ])->assertOk()
            ->assertJsonPath('data.is_valid', false)
            ->assertJsonPath('data.blockers.0.code', 'unsafe_reservations');

        $this->auth($context)->postJson("/api/v1/items/{$itemId}/base-uom/apply-change", [
            'new_base_uom_id' => $newUomId,
            'conversion_factor' => '0.100000',
        ])->assertUnprocessable();
    }

    public function test_active_reservations_and_allocations_with_old_uom_snapshot_convert_safely(): void
    {
        $context = $this->context('UOM-FLOW');
        $oldUomId = $this->uom($context, 'PCS');
        $newUomId = $this->uom($context, 'BOX');
        $itemId = $this->item($context, $oldUomId, 'FLOW');
        $warehouseId = $this->warehouse($context);
        $this->stock($context, $itemId, $warehouseId, '10.000000', '1.000000', '10.000000', '2.000000');
        DB::table('inventory_stock_balances')->where('item_id', $itemId)->update([
            'quantity_allocated' => '1.000000',
            'quantity_available' => '7.000000',
        ]);
        $reservationId = DB::table('inventory_reservations')->insertGetId([
            ...$this->scope($context),
            'reservation_number' => 'RES-FLOW',
            'reservation_date' => now()->toDateString(),
            'item_id' => $itemId,
            'base_uom_id' => $oldUomId,
            'warehouse_id' => $warehouseId,
            'quantity_reserved' => '2.000000',
            'quantity_allocated' => '0.000000',
            'quantity_released' => '0.000000',
            'quantity_remaining' => '2.000000',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $allocationId = DB::table('inventory_allocations')->insertGetId([
            ...$this->scope($context),
            'allocation_number' => 'ALC-FLOW',
            'allocation_date' => now()->toDateString(),
            'item_id' => $itemId,
            'base_uom_id' => $oldUomId,
            'warehouse_id' => $warehouseId,
            'quantity_allocated' => '1.000000',
            'quantity_issued' => '0.000000',
            'quantity_released' => '0.000000',
            'quantity_remaining' => '1.000000',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = ['new_base_uom_id' => $newUomId, 'conversion_factor' => '0.500000'];
        $this->auth($context)->postJson("/api/v1/items/{$itemId}/base-uom/preview-change", $payload)
            ->assertOk()
            ->assertJsonFragment(['area' => 'reservation', 'before' => '2.000000', 'after' => '1.000000'])
            ->assertJsonFragment(['area' => 'allocation', 'before' => '1.000000', 'after' => '0.500000']);
        $this->auth($context)->postJson("/api/v1/items/{$itemId}/base-uom/apply-change", $payload)->assertOk();

        $this->assertDatabaseHas('inventory_reservations', [
            'id' => $reservationId,
            'base_uom_id' => $newUomId,
            'quantity_reserved' => '1.000000',
            'quantity_remaining' => '1.000000',
        ]);
        $this->assertDatabaseHas('inventory_allocations', [
            'id' => $allocationId,
            'base_uom_id' => $newUomId,
            'quantity_allocated' => '0.500000',
            'quantity_remaining' => '0.500000',
        ]);
    }

    public function test_historical_document_lines_remain_unchanged_after_conversion(): void
    {
        $context = $this->context('UOM-HISTORY');
        $oldUomId = $this->uom($context, 'PCS');
        $newUomId = $this->uom($context, 'BOX');
        $itemId = $this->item($context, $oldUomId, 'HISTORY');
        $warehouseId = $this->warehouse($context);
        $this->stock($context, $itemId, $warehouseId, '5.000000');
        $lineIds = $this->historicalLines($context, $itemId, $oldUomId, $warehouseId);

        $this->auth($context)->postJson("/api/v1/items/{$itemId}/base-uom/apply-change", [
            'new_base_uom_id' => $newUomId,
            'conversion_factor' => '0.500000',
            'reason' => 'Historical preservation test',
        ])->assertOk();

        $this->assertDatabaseHas('purchase_order_lines', ['id' => $lineIds['po'], 'ordered_quantity' => '5.000000', 'ordered_uom_id' => $oldUomId, 'base_uom_id' => $oldUomId]);
        $this->assertDatabaseHas('goods_receipt_note_lines', ['id' => $lineIds['grn'], 'accepted_quantity' => '5.000000', 'uom_id' => $oldUomId, 'base_uom_id' => $oldUomId]);
        $this->assertDatabaseHas('invoice_lines', ['id' => $lineIds['invoice'], 'quantity' => '5.000000', 'uom_id' => $oldUomId]);
        $this->assertDatabaseHas('vehicle_service_job_lines', ['id' => $lineIds['job'], 'quantity' => '5.000000', 'uom_id' => $oldUomId]);
    }

    public function test_tenant_and_organization_scope_are_enforced(): void
    {
        $tenantA = $this->context('UOM-SCOPE-A', 'scope-a@example.test');
        $tenantB = $this->context('UOM-SCOPE-B', 'scope-b@example.test');
        $oldUomId = $this->uom($tenantA, 'PCS');
        $newUomId = $this->uom($tenantA, 'BOX');
        $itemId = $this->item($tenantA, $oldUomId, 'SCOPE');

        $this->auth($tenantB)->getJson("/api/v1/items/{$itemId}/base-uom/usage-audit")->assertNotFound();
        $this->auth($tenantB)->postJson("/api/v1/items/{$itemId}/base-uom/preview-change", [
            'new_base_uom_id' => $newUomId,
            'conversion_factor' => '0.100000',
        ])->assertNotFound();
    }

    private function historicalLines(array $context, int $itemId, int $uomId, int $warehouseId): array
    {
        $now = now();
        $poId = DB::table('purchase_orders')->insertGetId([
            ...$this->scope($context),
            'purchase_order_number' => 'PO-HISTORY',
            'purchase_order_date' => $now->toDateString(),
            'status' => 'closed',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $poLineId = DB::table('purchase_order_lines')->insertGetId([
            ...$this->scope($context),
            'purchase_order_id' => $poId,
            'line_number' => 1,
            'item_id' => $itemId,
            'uom_id' => $uomId,
            'ordered_uom_id' => $uomId,
            'base_uom_id' => $uomId,
            'uom_conversion_factor' => '1.000000',
            'ordered_quantity' => '5.000000',
            'base_quantity' => '5.000000',
            'received_quantity' => '5.000000',
            'invoiced_quantity' => '5.000000',
            'returned_quantity' => '0.000000',
            'cancelled_quantity' => '0.000000',
            'remaining_quantity' => '0.000000',
            'remaining_receivable_quantity' => '0.000000',
            'remaining_invoiceable_quantity' => '0.000000',
            'remaining_returnable_quantity' => '0.000000',
            'unit_price' => '2.000000',
            'line_subtotal' => '10.000000',
            'discount_amount' => '0.000000',
            'tax_amount' => '0.000000',
            'charge_amount' => '0.000000',
            'line_total' => '10.000000',
            'status' => 'closed',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $grnId = DB::table('goods_receipt_notes')->insertGetId([
            ...$this->scope($context),
            'purchase_order_id' => $poId,
            'warehouse_id' => $warehouseId,
            'grn_number' => 'GRN-HISTORY',
            'received_date' => $now->toDateString(),
            'status' => 'returned',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $grnLineId = DB::table('goods_receipt_note_lines')->insertGetId([
            ...$this->scope($context),
            'goods_receipt_note_id' => $grnId,
            'purchase_order_line_id' => $poLineId,
            'item_id' => $itemId,
            'uom_id' => $uomId,
            'ordered_uom_id' => $uomId,
            'base_uom_id' => $uomId,
            'uom_conversion_factor' => '1.000000',
            'ordered_quantity' => '5.000000',
            'received_quantity' => '5.000000',
            'base_received_quantity' => '5.000000',
            'accepted_quantity' => '5.000000',
            'base_accepted_quantity' => '5.000000',
            'rejected_quantity' => '0.000000',
            'invoiced_quantity' => '5.000000',
            'returned_quantity' => '5.000000',
            'remaining_quantity' => '0.000000',
            'unit_price' => '2.000000',
            'line_subtotal' => '10.000000',
            'discount_amount' => '0.000000',
            'tax_amount' => '0.000000',
            'charge_amount' => '0.000000',
            'line_total' => '10.000000',
            'status' => 'returned',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $invoiceId = DB::table('invoices')->insertGetId([
            ...$this->scope($context),
            'invoice_number' => 'INV-HISTORY',
            'invoice_type' => 'sales',
            'direction' => 'outbound',
            'invoice_date' => $now->toDateString(),
            'status' => 'posted',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $invoiceLineId = DB::table('invoice_lines')->insertGetId([
            ...$this->scope($context),
            'invoice_id' => $invoiceId,
            'line_number' => 1,
            'item_id' => $itemId,
            'description' => 'Historical item',
            'line_type' => 'item',
            'quantity' => '5.000000',
            'uom_id' => $uomId,
            'unit_price' => '2.000000',
            'line_total' => '10.000000',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $customerId = DB::table('customers')->insertGetId([
            ...$this->scope($context),
            'customer_number' => 'CUS-HISTORY',
            'code' => 'CUS-HISTORY',
            'name' => 'History Customer',
            'customer_type' => 'individual',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $vehicleId = DB::table('vehicles')->insertGetId([
            ...$this->scope($context),
            'vehicle_number' => 'VEH-HISTORY',
            'customer_id' => $customerId,
            'odometer_reading' => '0.000000',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $jobId = DB::table('vehicle_service_jobs')->insertGetId([
            ...$this->scope($context),
            'job_number' => 'JOB-HISTORY',
            'job_date' => $now->toDateString(),
            'customer_id' => $customerId,
            'vehicle_id' => $vehicleId,
            'status' => 'cancelled',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $jobLineId = DB::table('vehicle_service_job_lines')->insertGetId([
            ...$this->scope($context),
            'vehicle_service_job_id' => $jobId,
            'line_number' => 1,
            'line_source_type' => 'manual',
            'item_id' => $itemId,
            'uom_id' => $uomId,
            'description' => 'Historical job line',
            'quantity' => '5.000000',
            'unit_cost' => '2.000000',
            'unit_price' => '2.000000',
            'line_total' => '10.000000',
            'is_inventory_tracked' => true,
            'status' => 'cancelled',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return ['po' => $poLineId, 'grn' => $grnLineId, 'invoice' => $invoiceLineId, 'job' => $jobLineId];
    }

    private function stock(
        array $context,
        int $itemId,
        int $warehouseId,
        string $quantity,
        string $averageCost = '1.000000',
        ?string $totalValue = null,
        string $reserved = '0.000000',
    ): int {
        return (int) DB::table('inventory_stock_balances')->insertGetId([
            ...$this->scope($context),
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'quantity_on_hand' => $quantity,
            'quantity_reserved' => $reserved,
            'quantity_allocated' => '0.000000',
            'quantity_available' => app(\Modules\Core\Services\DecimalMath::class)->sub($quantity, $reserved),
            'average_cost' => $averageCost,
            'total_value' => $totalValue ?? $quantity,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function item(array $context, int $uomId, string $code): int
    {
        return (int) DB::table('items')->insertGetId([
            ...$this->scope($context),
            'code' => $code,
            'name' => $code.' Item',
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

    private function uom(array $context, string $code): int
    {
        return (int) DB::table('unit_of_measures')->insertGetId([
            ...$this->scope($context),
            'row_version' => 1,
            'code' => $code,
            'name' => $code.' Unit',
            'symbol' => strtolower($code),
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

    private function warehouse(array $context): int
    {
        return (int) DB::table('warehouses')->insertGetId([
            ...$this->scope($context),
            'row_version' => 1,
            'name' => 'Main Warehouse',
            'code' => 'MAIN',
            'type' => 'standard',
            'is_active' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function scope(array $context): array
    {
        return [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
        ];
    }

    private function auth(array $context): self
    {
        return $this->withToken($context['token'])->withHeaders([
            'X-Tenant-Id' => (string) $context['tenant_id'],
            'X-Organization-Unit-Id' => (string) $context['organization_unit_id'],
        ]);
    }

    private function context(string $code, ?string $email = null): array
    {
        $now = now();
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => $code.' Tenant',
            'slug' => strtolower($code),
            'status' => 'active',
            'is_active' => true,
            'is_isolated' => true,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $organizationUnitId = (int) DB::table('organization_units')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Main',
            'code' => 'MAIN',
            'path' => '/main',
            'is_active' => true,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $email ??= strtolower($code).'@example.test';
        $userId = (int) DB::table('users')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'first_name' => 'UOM',
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

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'token' => $token,
        ];
    }
}
