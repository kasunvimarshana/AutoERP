<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Inventory\Application\Contracts\ApproveTransferOrderServiceInterface;
use Modules\Inventory\Application\Contracts\CompleteCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\CreateCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\CreateTransferOrderServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseServiceInterface;
use Tests\TestCase;

class InventoryMutationTenantIsolationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function testApproveTransferOrderServiceRejectsCrossTenantMutation(): void
    {
        $tenantId = 91;
        $wrongTenantId = 92;

        $this->seedTenant($tenantId);
        $this->seedReferenceData($tenantId);

        /** @var CreateWarehouseServiceInterface $createWarehouseService */
        $createWarehouseService = app(CreateWarehouseServiceInterface::class);
        /** @var CreateWarehouseLocationServiceInterface $createWarehouseLocationService */
        $createWarehouseLocationService = app(CreateWarehouseLocationServiceInterface::class);
        /** @var CreateTransferOrderServiceInterface $createTransferOrderService */
        $createTransferOrderService = app(CreateTransferOrderServiceInterface::class);
        /** @var ApproveTransferOrderServiceInterface $approveTransferOrderService */
        $approveTransferOrderService = app(ApproveTransferOrderServiceInterface::class);

        $fromWarehouse = $createWarehouseService->execute([
            'tenant_id' => $tenantId,
            'name' => 'Origin WH 91',
            'code' => 'ORIG-91',
            'is_default' => true,
        ]);

        $toWarehouse = $createWarehouseService->execute([
            'tenant_id' => $tenantId,
            'name' => 'Destination WH 91',
            'code' => 'DEST-91',
            'is_default' => false,
        ]);

        $fromLocation = $createWarehouseLocationService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $fromWarehouse->getId(),
            'name' => 'From Rack 91',
            'code' => 'FROM-91',
            'type' => 'rack',
        ]);

        $toLocation = $createWarehouseLocationService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $toWarehouse->getId(),
            'name' => 'To Rack 91',
            'code' => 'TO-91',
            'type' => 'rack',
        ]);

        $order = $createTransferOrderService->execute([
            'tenant_id' => $tenantId,
            'from_warehouse_id' => $fromWarehouse->getId(),
            'to_warehouse_id' => $toWarehouse->getId(),
            'transfer_number' => 'TO-9101',
            'request_date' => now()->toDateString(),
            'lines' => [[
                'product_id' => 1001,
                'from_location_id' => $fromLocation->getId(),
                'to_location_id' => $toLocation->getId(),
                'uom_id' => 1,
                'requested_qty' => '2.000000',
                'unit_cost' => '10.000000',
            ]],
        ]);

        try {
            $approveTransferOrderService->execute($wrongTenantId, (int) $order->getId());
            $this->fail('Expected cross-tenant transfer approval to be rejected.');
        } catch (NotFoundException) {
            $this->assertDatabaseHas('transfer_orders', [
                'id' => $order->getId(),
                'tenant_id' => $tenantId,
                'transfer_number' => 'TO-9101',
                'status' => 'draft',
            ]);
        }
    }

    public function testCompleteCycleCountServiceRejectsCrossTenantMutation(): void
    {
        $tenantId = 93;
        $wrongTenantId = 94;

        $this->seedTenant($tenantId);
        $this->seedReferenceData($tenantId);

        /** @var CreateWarehouseServiceInterface $createWarehouseService */
        $createWarehouseService = app(CreateWarehouseServiceInterface::class);
        /** @var CreateWarehouseLocationServiceInterface $createWarehouseLocationService */
        $createWarehouseLocationService = app(CreateWarehouseLocationServiceInterface::class);
        /** @var CreateCycleCountServiceInterface $createCycleCountService */
        $createCycleCountService = app(CreateCycleCountServiceInterface::class);
        /** @var CompleteCycleCountServiceInterface $completeCycleCountService */
        $completeCycleCountService = app(CompleteCycleCountServiceInterface::class);

        $warehouse = $createWarehouseService->execute([
            'tenant_id' => $tenantId,
            'name' => 'Cycle WH 93',
            'code' => 'CYCLE-93',
            'is_default' => true,
        ]);

        $location = $createWarehouseLocationService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouse->getId(),
            'name' => 'Cycle Rack 93',
            'code' => 'CYC-RACK-93',
            'type' => 'rack',
        ]);

        DB::table('stock_levels')->insert([
            'tenant_id' => $tenantId,
            'product_id' => 1001,
            'variant_id' => null,
            'location_id' => $location->getId(),
            'batch_id' => null,
            'serial_id' => null,
            'uom_id' => 1,
            'quantity_on_hand' => '10.000000',
            'quantity_reserved' => '0.000000',
            'unit_cost' => '10.000000',
            'last_movement_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $count = $createCycleCountService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouse->getId(),
            'location_id' => $location->getId(),
            'counted_by_user_id' => 5001,
            'lines' => [[
                'product_id' => 1001,
                'uom_id' => 1,
                'unit_cost' => '10.000000',
            ]],
        ]);

        try {
            $completeCycleCountService->execute(
                $wrongTenantId,
                (int) $count->getId(),
                5001,
                [[
                    'line_id' => (int) $count->getLines()[0]->getId(),
                    'counted_qty' => '11.000000',
                ]]
            );
            $this->fail('Expected cross-tenant cycle count complete to be rejected.');
        } catch (NotFoundException) {
            $this->assertDatabaseHas('cycle_count_headers', [
                'id' => $count->getId(),
                'tenant_id' => $tenantId,
                'status' => 'draft',
            ]);
        }
    }

    private function seedTenant(int $tenantId): void
    {
        DB::table('tenants')->insert([
            'id' => $tenantId,
            'name' => 'Tenant ' . $tenantId,
            'slug' => 'tenant-' . $tenantId,
            'domain' => null,
            'logo_path' => null,
            'database_config' => null,
            'mail_config' => null,
            'cache_config' => null,
            'queue_config' => null,
            'feature_flags' => null,
            'api_keys' => null,
            'settings' => null,
            'plan' => 'free',
            'tenant_plan_id' => null,
            'status' => 'active',
            'active' => true,
            'trial_ends_at' => null,
            'subscription_ends_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('users')->insert([
            'id' => 5001,
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'email' => 'inventory' . $tenantId . '@example.com',
            'password' => bcrypt('secret'),
            'first_name' => 'Inv',
            'last_name' => 'User',
            'phone' => null,
            'avatar' => null,
            'email_verified_at' => now(),
            'remember_token' => null,
            'status' => 'active',
            'address' => null,
            'preferences' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function seedReferenceData(int $tenantId): void
    {
        DB::table('units_of_measure')->insert([
            'id' => 1,
            'tenant_id' => $tenantId,
            'name' => 'Each',
            'symbol' => 'ea',
            'type' => 'unit',
            'is_base' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('products')->insert([
            'id' => 1001,
            'tenant_id' => $tenantId,
            'category_id' => null,
            'brand_id' => null,
            'org_unit_id' => null,
            'type' => 'physical',
            'name' => 'Inventory Product',
            'slug' => 'inventory-product-' . $tenantId,
            'sku' => 'SKU-INV-1001-' . $tenantId,
            'description' => null,
            'base_uom_id' => 1,
            'purchase_uom_id' => null,
            'sales_uom_id' => null,
            'tax_group_id' => null,
            'uom_conversion_factor' => '1.0000000000',
            'is_batch_tracked' => false,
            'is_lot_tracked' => false,
            'is_serial_tracked' => false,
            'valuation_method' => 'fifo',
            'standard_cost' => '10.000000',
            'income_account_id' => null,
            'cogs_account_id' => null,
            'inventory_account_id' => null,
            'expense_account_id' => null,
            'is_active' => true,
            'image_path' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }
}
