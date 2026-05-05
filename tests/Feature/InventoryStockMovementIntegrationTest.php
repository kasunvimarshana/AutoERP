<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Application\Contracts\RecordStockMovementServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseServiceInterface;
use Tests\TestCase;

class InventoryStockMovementIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_movement_updates_stock_levels_for_both_locations(): void
    {
        $tenantId = 60;
        $this->seedTenant($tenantId);
        $this->seedReferenceData($tenantId);

        /** @var CreateWarehouseServiceInterface $createWarehouseService */
        $createWarehouseService = app(CreateWarehouseServiceInterface::class);
        /** @var CreateWarehouseLocationServiceInterface $createWarehouseLocationService */
        $createWarehouseLocationService = app(CreateWarehouseLocationServiceInterface::class);
        /** @var RecordStockMovementServiceInterface $recordStockMovementService */
        $recordStockMovementService = app(RecordStockMovementServiceInterface::class);

        $warehouse = $createWarehouseService->execute([
            'tenant_id' => $tenantId,
            'name' => 'Main DC',
            'code' => 'MDC',
            'is_default' => true,
        ]);

        $fromLocation = $createWarehouseLocationService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouse->getId(),
            'name' => 'Staging A',
            'code' => 'STAGE-A',
            'type' => 'staging',
        ]);

        $toLocation = $createWarehouseLocationService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouse->getId(),
            'name' => 'Rack B1',
            'code' => 'RACK-B1',
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
            'quantity_on_hand' => '25.000000',
            'quantity_reserved' => '0.000000',
            'unit_cost' => '10.000000',
            'last_movement_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $movement = $recordStockMovementService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouse->getId(),
            'product_id' => 1001,
            'from_location_id' => $fromLocation->getId(),
            'to_location_id' => $toLocation->getId(),
            'movement_type' => 'transfer',
            'uom_id' => 2001,
            'quantity' => '5.000000',
            'unit_cost' => '10.000000',
        ]);

        $this->assertSame('transfer', $movement->getMovementType());

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

        $this->assertSame(0, bccomp((string) $fromQty, '20.000000', 6));
        $this->assertSame(0, bccomp((string) $toQty, '5.000000', 6));

        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => $tenantId,
            'product_id' => 1001,
            'from_location_id' => $fromLocation->getId(),
            'to_location_id' => $toLocation->getId(),
            'movement_type' => 'transfer',
            'quantity' => '5.000000',
        ]);

        // Current contract: direct stock movements remain inventory-internal.
        $this->assertSame(0, DB::table('journal_entries')->count());
        $this->assertSame(0, DB::table('ap_transactions')->count());
        $this->assertSame(0, DB::table('ar_transactions')->count());
    }

    public function test_transfer_movement_normalizes_quantity_to_product_base_uom(): void
    {
        $tenantId = 61;
        $this->seedTenant($tenantId);
        $this->seedReferenceData($tenantId);

        /** @var CreateWarehouseServiceInterface $createWarehouseService */
        $createWarehouseService = app(CreateWarehouseServiceInterface::class);
        /** @var CreateWarehouseLocationServiceInterface $createWarehouseLocationService */
        $createWarehouseLocationService = app(CreateWarehouseLocationServiceInterface::class);
        /** @var RecordStockMovementServiceInterface $recordStockMovementService */
        $recordStockMovementService = app(RecordStockMovementServiceInterface::class);

        $warehouse = $createWarehouseService->execute([
            'tenant_id' => $tenantId,
            'name' => 'Main DC',
            'code' => 'MDC',
            'is_default' => true,
        ]);

        $fromLocation = $createWarehouseLocationService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouse->getId(),
            'name' => 'Staging A',
            'code' => 'STAGE-A',
            'type' => 'staging',
        ]);

        $toLocation = $createWarehouseLocationService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouse->getId(),
            'name' => 'Rack B1',
            'code' => 'RACK-B1',
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
            'quantity_on_hand' => '24.000000',
            'quantity_reserved' => '0.000000',
            'unit_cost' => '10.000000',
            'last_movement_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $movement = $recordStockMovementService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouse->getId(),
            'product_id' => 1001,
            'from_location_id' => $fromLocation->getId(),
            'to_location_id' => $toLocation->getId(),
            'movement_type' => 'transfer',
            'uom_id' => 2002,
            'quantity' => '2.000000',
            'unit_cost' => '10.000000',
        ]);

        $this->assertSame(2001, $movement->getUomId());
        $this->assertSame(0, bccomp($movement->getQuantity(), '24.000000', 6));

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

        $this->assertSame(0, bccomp((string) $fromQty, '0.000000', 6));
        $this->assertSame(0, bccomp((string) $toQty, '24.000000', 6));

        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => $tenantId,
            'product_id' => 1001,
            'movement_type' => 'transfer',
            'uom_id' => 2001,
            'quantity' => '24.000000',
        ]);

        // Current contract: direct stock movements remain inventory-internal.
        $this->assertSame(0, DB::table('journal_entries')->count());
        $this->assertSame(0, DB::table('ap_transactions')->count());
        $this->assertSame(0, DB::table('ar_transactions')->count());
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

        DB::table('units_of_measure')->insert([
            'id' => 2002,
            'tenant_id' => $tenantId,
            'name' => 'Box',
            'symbol' => 'box',
            'type' => 'unit',
            'is_base' => false,
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

        DB::table('uom_conversions')->insert([
            'tenant_id' => $tenantId,
            'product_id' => 1001,
            'from_uom_id' => 2002,
            'to_uom_id' => 2001,
            'factor' => '12.0000000000',
            'is_bidirectional' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }
}
