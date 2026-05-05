<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Inventory\Application\Contracts\ApproveTransferOrderServiceInterface;
use Modules\Inventory\Application\Contracts\CreateTransferOrderServiceInterface;
use Modules\Inventory\Application\Contracts\ReceiveTransferOrderServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseServiceInterface;
use Tests\TestCase;

class InventoryTransferOrderIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_order_create_approve_receive_updates_status_and_stock(): void
    {
        $tenantId = 70;
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
        /** @var ReceiveTransferOrderServiceInterface $receiveTransferOrderService */
        $receiveTransferOrderService = app(ReceiveTransferOrderServiceInterface::class);

        $fromWarehouse = $createWarehouseService->execute([
            'tenant_id' => $tenantId,
            'name' => 'Origin WH',
            'code' => 'ORIG-WH',
            'is_default' => true,
        ]);

        $toWarehouse = $createWarehouseService->execute([
            'tenant_id' => $tenantId,
            'name' => 'Destination WH',
            'code' => 'DEST-WH',
            'is_default' => false,
        ]);

        $fromLocation = $createWarehouseLocationService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $fromWarehouse->getId(),
            'name' => 'From Rack',
            'code' => 'FROM-RACK',
            'type' => 'rack',
        ]);

        $toLocation = $createWarehouseLocationService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $toWarehouse->getId(),
            'name' => 'To Rack',
            'code' => 'TO-RACK',
            'type' => 'rack',
        ]);

        DB::table('stock_levels')->insert([
            'tenant_id' => $tenantId,
            'product_id' => 1001,
            'variant_id' => null,
            'location_id' => $fromLocation->getId(),
            'batch_id' => null,
            'serial_id' => null,
            'uom_id' => 2001,
            'quantity_on_hand' => '30.000000',
            'quantity_reserved' => '0.000000',
            'unit_cost' => '10.000000',
            'last_movement_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $order = $createTransferOrderService->execute([
            'tenant_id' => $tenantId,
            'from_warehouse_id' => $fromWarehouse->getId(),
            'to_warehouse_id' => $toWarehouse->getId(),
            'transfer_number' => 'TO-7001',
            'request_date' => now()->toDateString(),
            'lines' => [[
                'product_id' => 1001,
                'from_location_id' => $fromLocation->getId(),
                'to_location_id' => $toLocation->getId(),
                'uom_id' => 2001,
                'requested_qty' => '5.000000',
                'unit_cost' => '10.000000',
            ]],
        ]);

        $this->assertSame('draft', $order->getStatus());

        $approved = $approveTransferOrderService->execute($tenantId, (int) $order->getId());
        $this->assertSame('approved', $approved->getStatus());

        $received = $receiveTransferOrderService->execute($tenantId, (int) $approved->getId(), [[
            'line_id' => (int) $approved->getLines()[0]->getId(),
            'received_qty' => '5.000000',
        ]]);

        $this->assertSame('received', $received->getStatus());

        $fromQty = DB::table('stock_levels')
            ->where('tenant_id', $tenantId)
            ->where('product_id', 1001)
            ->where('location_id', $fromLocation->getId())
            ->value('quantity_on_hand');

        $toQty = DB::table('stock_levels')
            ->where('tenant_id', $tenantId)
            ->where('product_id', 1001)
            ->where('location_id', $toLocation->getId())
            ->value('quantity_on_hand');

        $this->assertSame(0, bccomp((string) $fromQty, '25.000000', 6));
        $this->assertSame(0, bccomp((string) $toQty, '5.000000', 6));

        // Transfer receipt should materialize two inventory movements (shipment + receipt)
        $this->assertSame(
            2,
            DB::table('stock_movements')
                ->where('tenant_id', $tenantId)
                ->whereIn('movement_type', ['shipment', 'receipt'])
                ->count()
        );

        // Current contract: transfer receiving is inventory-internal and must not post finance artifacts.
        $this->assertSame(0, DB::table('journal_entries')->count());
        $this->assertSame(0, DB::table('ap_transactions')->count());
        $this->assertSame(0, DB::table('ar_transactions')->count());

        $this->assertDatabaseHas('transfer_orders', [
            'tenant_id' => $tenantId,
            'transfer_number' => 'TO-7001',
            'status' => 'received',
        ]);
    }

    public function test_transfer_order_receive_rejects_cross_tenant_mutation(): void
    {
        $tenantId = 80;
        $wrongTenantId = 81;

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
        /** @var ReceiveTransferOrderServiceInterface $receiveTransferOrderService */
        $receiveTransferOrderService = app(ReceiveTransferOrderServiceInterface::class);

        $fromWarehouse = $createWarehouseService->execute([
            'tenant_id' => $tenantId,
            'name' => 'Tenant 80 Origin',
            'code' => 'ORIG-80',
            'is_default' => true,
        ]);

        $toWarehouse = $createWarehouseService->execute([
            'tenant_id' => $tenantId,
            'name' => 'Tenant 80 Destination',
            'code' => 'DEST-80',
            'is_default' => false,
        ]);

        $fromLocation = $createWarehouseLocationService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $fromWarehouse->getId(),
            'name' => 'From Rack 80',
            'code' => 'FROM-80',
            'type' => 'rack',
        ]);

        $toLocation = $createWarehouseLocationService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $toWarehouse->getId(),
            'name' => 'To Rack 80',
            'code' => 'TO-80',
            'type' => 'rack',
        ]);

        DB::table('stock_levels')->insert([
            'tenant_id' => $tenantId,
            'product_id' => 1001,
            'variant_id' => null,
            'location_id' => $fromLocation->getId(),
            'batch_id' => null,
            'serial_id' => null,
            'uom_id' => 2001,
            'quantity_on_hand' => '12.000000',
            'quantity_reserved' => '0.000000',
            'unit_cost' => '10.000000',
            'last_movement_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $order = $createTransferOrderService->execute([
            'tenant_id' => $tenantId,
            'from_warehouse_id' => $fromWarehouse->getId(),
            'to_warehouse_id' => $toWarehouse->getId(),
            'transfer_number' => 'TO-8001',
            'request_date' => now()->toDateString(),
            'lines' => [[
                'product_id' => 1001,
                'from_location_id' => $fromLocation->getId(),
                'to_location_id' => $toLocation->getId(),
                'uom_id' => 2001,
                'requested_qty' => '4.000000',
                'unit_cost' => '10.000000',
            ]],
        ]);

        $approved = $approveTransferOrderService->execute($tenantId, (int) $order->getId());

        try {
            $receiveTransferOrderService->execute($wrongTenantId, (int) $approved->getId(), [[
                'line_id' => (int) $approved->getLines()[0]->getId(),
                'received_qty' => '4.000000',
            ]]);

            $this->fail('Expected cross-tenant transfer receipt to be rejected.');
        } catch (NotFoundException) {
            $this->assertDatabaseHas('transfer_orders', [
                'id' => $approved->getId(),
                'tenant_id' => $tenantId,
                'transfer_number' => 'TO-8001',
                'status' => 'approved',
            ]);

            $this->assertSame(0, DB::table('stock_movements')->where('tenant_id', $wrongTenantId)->count());
        }
    }

    private function seedTenant(int $tenantId): void
    {
        DB::table('tenants')->insert([
            'id' => $tenantId,
            'name' => 'Tenant '.$tenantId,
            'slug' => 'tenant-'.$tenantId,
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
    }

    private function seedReferenceData(int $tenantId): void
    {
        DB::table('units_of_measure')->insert([
            'id' => 2001,
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
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'SKU-1001',
            'description' => null,
            'base_uom_id' => 2001,
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
