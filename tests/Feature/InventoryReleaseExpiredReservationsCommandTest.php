<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Inventory\Application\Contracts\CreateStockReservationServiceInterface;
use Modules\Inventory\Domain\Events\ExpiredStockReservationsReleased;
use Modules\Warehouse\Application\Contracts\CreateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseServiceInterface;
use Tests\TestCase;

class InventoryReleaseExpiredReservationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_releases_only_expired_reservations_for_active_tenant(): void
    {
        Event::fake([ExpiredStockReservationsReleased::class]);

        $tenantId = 92;
        $this->seedTenant($tenantId, true);
        $this->seedReferenceData($tenantId);

        /** @var CreateWarehouseServiceInterface $createWarehouseService */
        $createWarehouseService = app(CreateWarehouseServiceInterface::class);
        /** @var CreateWarehouseLocationServiceInterface $createWarehouseLocationService */
        $createWarehouseLocationService = app(CreateWarehouseLocationServiceInterface::class);
        /** @var CreateStockReservationServiceInterface $createReservationService */
        $createReservationService = app(CreateStockReservationServiceInterface::class);

        $warehouse = $createWarehouseService->execute([
            'tenant_id' => $tenantId,
            'name' => 'Cmd WH',
            'code' => 'CMD-WH',
            'is_default' => true,
        ]);

        $location = $createWarehouseLocationService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouse->getId(),
            'name' => 'Cmd Rack',
            'code' => 'CMD-RACK',
            'type' => 'rack',
        ]);

        DB::table('stock_levels')->insert([
            'tenant_id' => $tenantId,
            'product_id' => 1001,
            'variant_id' => null,
            'location_id' => $location->getId(),
            'batch_id' => null,
            'serial_id' => null,
            'uom_id' => 2001,
            'quantity_on_hand' => '30.000000',
            'quantity_reserved' => '0.000000',
            'unit_cost' => '8.000000',
            'last_movement_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $expired = $createReservationService->execute([
            'tenant_id' => $tenantId,
            'product_id' => 1001,
            'location_id' => $location->getId(),
            'quantity' => '4.000000',
            'expires_at' => now()->subMinutes(30)->format('Y-m-d H:i:s'),
        ]);

        $active = $createReservationService->execute([
            'tenant_id' => $tenantId,
            'product_id' => 1001,
            'location_id' => $location->getId(),
            'quantity' => '2.000000',
            'expires_at' => now()->addHour()->format('Y-m-d H:i:s'),
        ]);

        $exitCode = Artisan::call('inventory:release-expired-reservations');
        $this->assertSame(0, $exitCode);

        Event::assertDispatched(ExpiredStockReservationsReleased::class, function (ExpiredStockReservationsReleased $event) use ($tenantId): bool {
            return $event->tenantId === $tenantId
                && $event->releasedCount === 1
                && $event->expiresBefore === null;
        });

        $reservedQty = DB::table('stock_levels')
            ->where('tenant_id', $tenantId)
            ->where('product_id', 1001)
            ->where('location_id', $location->getId())
            ->value('quantity_reserved');

        $this->assertSame(0, bccomp((string) $reservedQty, '2.000000', 6));

        $this->assertDatabaseMissing('stock_reservations', [
            'id' => (int) $expired->getId(),
            'tenant_id' => $tenantId,
        ]);

        $this->assertDatabaseHas('stock_reservations', [
            'id' => (int) $active->getId(),
            'tenant_id' => $tenantId,
        ]);
    }

    private function seedTenant(int $tenantId, bool $active): void
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
            'active' => $active,
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
            'name' => 'Command Product',
            'slug' => 'command-product',
            'sku' => 'SKU-CMD-1001',
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
            'standard_cost' => '8.000000',
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
