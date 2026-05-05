<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Application\Contracts\CompleteCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\CreateCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\StartCycleCountServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseServiceInterface;
use Tests\TestCase;

class InventoryCycleCountIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cycle_count_complete_posts_adjustment_movement_and_trace_log(): void
    {
        $tenantId = 80;
        $this->seedTenant($tenantId);
        $this->seedReferenceData($tenantId);

        /** @var CreateWarehouseServiceInterface $createWarehouseService */
        $createWarehouseService = app(CreateWarehouseServiceInterface::class);
        /** @var CreateWarehouseLocationServiceInterface $createWarehouseLocationService */
        $createWarehouseLocationService = app(CreateWarehouseLocationServiceInterface::class);
        /** @var CreateCycleCountServiceInterface $createCycleCountService */
        $createCycleCountService = app(CreateCycleCountServiceInterface::class);
        /** @var StartCycleCountServiceInterface $startCycleCountService */
        $startCycleCountService = app(StartCycleCountServiceInterface::class);
        /** @var CompleteCycleCountServiceInterface $completeCycleCountService */
        $completeCycleCountService = app(CompleteCycleCountServiceInterface::class);

        $warehouse = $createWarehouseService->execute([
            'tenant_id' => $tenantId,
            'name' => 'Count WH',
            'code' => 'COUNT-WH',
            'is_default' => true,
        ]);

        $location = $createWarehouseLocationService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouse->getId(),
            'name' => 'Cycle Rack',
            'code' => 'CYCLE-RACK',
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
            'unit_cost' => '12.000000',
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
                'unit_cost' => '12.000000',
            ]],
        ]);

        $this->assertSame('draft', $count->getStatus());

        $inProgress = $startCycleCountService->execute($tenantId, (int) $count->getId());
        $this->assertSame('in_progress', $inProgress->getStatus());

        $completed = $completeCycleCountService->execute(
            $tenantId,
            (int) $inProgress->getId(),
            5001,
            [[
                'line_id' => (int) $inProgress->getLines()[0]->getId(),
                'counted_qty' => '13.000000',
            ]]
        );

        $this->assertSame('completed', $completed->getStatus());
        $this->assertNotNull($completed->getLines()[0]->getAdjustmentMovementId());

        $qty = DB::table('stock_levels')
            ->where('tenant_id', $tenantId)
            ->where('product_id', 1001)
            ->where('location_id', $location->getId())
            ->value('quantity_on_hand');

        $this->assertSame(0, bccomp((string) $qty, '13.000000', 6));

        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => $tenantId,
            'product_id' => 1001,
            'movement_type' => 'adjustment_in',
            'to_location_id' => $location->getId(),
            'reference_type' => 'cycle_count_headers',
            'reference_id' => (int) $count->getId(),
            'quantity' => '3.000000',
        ]);

        $this->assertDatabaseHas('trace_logs', [
            'tenant_id' => $tenantId,
            'entity_type' => 'product',
            'entity_id' => 1001,
            'action_type' => 'adjust',
            'destination_location_id' => $location->getId(),
        ]);

        // Current contract: cycle-count adjustments remain inventory-internal and do not post Finance artifacts.
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

        DB::table('users')->insert([
            'id' => 5001,
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'email' => 'counter'.$tenantId.'@example.com',
            'password' => bcrypt('secret'),
            'first_name' => 'Count',
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
            'name' => 'Count Product',
            'slug' => 'count-product',
            'sku' => 'SKU-CP-1001',
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
            'standard_cost' => '12.000000',
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
