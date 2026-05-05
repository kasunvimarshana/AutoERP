<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Application\Contracts\CreateStockReservationServiceInterface;
use Modules\Inventory\Application\Contracts\ReleaseExpiredStockReservationsServiceInterface;
use Modules\Inventory\Application\Contracts\ReleaseStockReservationServiceInterface;
use Modules\Inventory\Domain\Exceptions\InsufficientAvailableStockException;
use Modules\Warehouse\Application\Contracts\CreateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseServiceInterface;
use Tests\TestCase;

class InventoryStockReservationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_cannot_exceed_available_quantity(): void
    {
        $tenantId = 89;
        $this->seedTenant($tenantId);
        $this->seedReferenceData($tenantId);

        /** @var CreateWarehouseServiceInterface $createWarehouseService */
        $createWarehouseService = app(CreateWarehouseServiceInterface::class);
        /** @var CreateWarehouseLocationServiceInterface $createWarehouseLocationService */
        $createWarehouseLocationService = app(CreateWarehouseLocationServiceInterface::class);
        /** @var CreateStockReservationServiceInterface $createReservationService */
        $createReservationService = app(CreateStockReservationServiceInterface::class);

        $warehouse = $createWarehouseService->execute([
            'tenant_id' => $tenantId,
            'name' => 'Guard WH',
            'code' => 'GRD-WH',
            'is_default' => true,
        ]);

        $location = $createWarehouseLocationService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouse->getId(),
            'name' => 'Guard Rack',
            'code' => 'GRD-RACK',
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
            'quantity_on_hand' => '5.000000',
            'quantity_reserved' => '0.000000',
            'unit_cost' => '8.000000',
            'last_movement_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $createReservationService->execute([
                'tenant_id' => $tenantId,
                'product_id' => 1001,
                'location_id' => $location->getId(),
                'quantity' => '6.000000',
            ]);

            $this->fail('Expected RuntimeException was not thrown.');
        } catch (InsufficientAvailableStockException $exception) {
            $this->assertSame('Insufficient available stock for reservation.', $exception->getMessage());
        }

        $reservedQty = DB::table('stock_levels')
            ->where('tenant_id', $tenantId)
            ->where('product_id', 1001)
            ->where('location_id', $location->getId())
            ->value('quantity_reserved');

        $this->assertSame(0, bccomp((string) $reservedQty, '0.000000', 6));

        $this->assertDatabaseMissing('stock_reservations', [
            'tenant_id' => $tenantId,
            'product_id' => 1001,
            'location_id' => $location->getId(),
            'quantity' => '6.000000',
        ]);

        // Current contract: reservation validation failures must not create Finance artifacts.
        $this->assertSame(0, DB::table('journal_entries')->count());
        $this->assertSame(0, DB::table('ap_transactions')->count());
        $this->assertSame(0, DB::table('ar_transactions')->count());
    }

    public function test_reserve_then_release_updates_quantity_reserved(): void
    {
        $tenantId = 90;
        $this->seedTenant($tenantId);
        $this->seedReferenceData($tenantId);

        /** @var CreateWarehouseServiceInterface $createWarehouseService */
        $createWarehouseService = app(CreateWarehouseServiceInterface::class);
        /** @var CreateWarehouseLocationServiceInterface $createWarehouseLocationService */
        $createWarehouseLocationService = app(CreateWarehouseLocationServiceInterface::class);
        /** @var CreateStockReservationServiceInterface $createReservationService */
        $createReservationService = app(CreateStockReservationServiceInterface::class);
        /** @var ReleaseStockReservationServiceInterface $releaseReservationService */
        $releaseReservationService = app(ReleaseStockReservationServiceInterface::class);

        $warehouse = $createWarehouseService->execute([
            'tenant_id' => $tenantId,
            'name' => 'Reserve WH',
            'code' => 'RES-WH',
            'is_default' => true,
        ]);

        $location = $createWarehouseLocationService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouse->getId(),
            'name' => 'Reserve Rack',
            'code' => 'RES-RACK',
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
            'quantity_on_hand' => '40.000000',
            'quantity_reserved' => '0.000000',
            'unit_cost' => '8.000000',
            'last_movement_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reservation = $createReservationService->execute([
            'tenant_id' => $tenantId,
            'product_id' => 1001,
            'location_id' => $location->getId(),
            'quantity' => '7.500000',
            'reserved_for_type' => 'sales_order_lines',
            'reserved_for_id' => 777,
        ]);

        $this->assertNotNull($reservation->getId());

        $reservedQty = DB::table('stock_levels')
            ->where('tenant_id', $tenantId)
            ->where('product_id', 1001)
            ->where('location_id', $location->getId())
            ->value('quantity_reserved');

        $this->assertSame(0, bccomp((string) $reservedQty, '7.500000', 6));

        $this->assertDatabaseHas('stock_reservations', [
            'tenant_id' => $tenantId,
            'product_id' => 1001,
            'location_id' => $location->getId(),
            'quantity' => '7.500000',
            'reserved_for_type' => 'sales_order_lines',
            'reserved_for_id' => 777,
        ]);

        $released = $releaseReservationService->execute($tenantId, (int) $reservation->getId());
        $this->assertTrue($released);

        $reservedQtyAfterRelease = DB::table('stock_levels')
            ->where('tenant_id', $tenantId)
            ->where('product_id', 1001)
            ->where('location_id', $location->getId())
            ->value('quantity_reserved');

        $this->assertSame(0, bccomp((string) $reservedQtyAfterRelease, '0.000000', 6));

        $this->assertDatabaseMissing('stock_reservations', [
            'id' => (int) $reservation->getId(),
            'tenant_id' => $tenantId,
        ]);

        // Current contract: reservation/release lifecycle remains inventory-internal.
        $this->assertSame(0, DB::table('journal_entries')->count());
        $this->assertSame(0, DB::table('ap_transactions')->count());
        $this->assertSame(0, DB::table('ar_transactions')->count());
    }

    public function test_release_expired_releases_only_expired_reservations(): void
    {
        $tenantId = 91;
        $this->seedTenant($tenantId);
        $this->seedReferenceData($tenantId);

        /** @var CreateWarehouseServiceInterface $createWarehouseService */
        $createWarehouseService = app(CreateWarehouseServiceInterface::class);
        /** @var CreateWarehouseLocationServiceInterface $createWarehouseLocationService */
        $createWarehouseLocationService = app(CreateWarehouseLocationServiceInterface::class);
        /** @var CreateStockReservationServiceInterface $createReservationService */
        $createReservationService = app(CreateStockReservationServiceInterface::class);
        /** @var ReleaseExpiredStockReservationsServiceInterface $releaseExpiredService */
        $releaseExpiredService = app(ReleaseExpiredStockReservationsServiceInterface::class);

        $warehouse = $createWarehouseService->execute([
            'tenant_id' => $tenantId,
            'name' => 'Expire WH',
            'code' => 'EXP-WH',
            'is_default' => true,
        ]);

        $location = $createWarehouseLocationService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouse->getId(),
            'name' => 'Expire Rack',
            'code' => 'EXP-RACK',
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
            'quantity_on_hand' => '25.000000',
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
            'quantity' => '2.500000',
            'expires_at' => now()->subHour()->format('Y-m-d H:i:s'),
        ]);

        $active = $createReservationService->execute([
            'tenant_id' => $tenantId,
            'product_id' => 1001,
            'location_id' => $location->getId(),
            'quantity' => '1.250000',
            'expires_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ]);

        $releasedCount = $releaseExpiredService->execute($tenantId);
        $this->assertSame(1, $releasedCount);

        $reservedQtyAfterRelease = DB::table('stock_levels')
            ->where('tenant_id', $tenantId)
            ->where('product_id', 1001)
            ->where('location_id', $location->getId())
            ->value('quantity_reserved');

        $this->assertSame(0, bccomp((string) $reservedQtyAfterRelease, '1.250000', 6));

        $this->assertDatabaseMissing('stock_reservations', [
            'id' => (int) $expired->getId(),
            'tenant_id' => $tenantId,
        ]);

        $this->assertDatabaseHas('stock_reservations', [
            'id' => (int) $active->getId(),
            'tenant_id' => $tenantId,
            'quantity' => '1.250000',
        ]);

        // Current contract: expired-reservation release remains inventory-internal.
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

        DB::table('products')->insert([
            'id' => 1001,
            'tenant_id' => $tenantId,
            'category_id' => null,
            'brand_id' => null,
            'org_unit_id' => null,
            'type' => 'physical',
            'name' => 'Reserve Product',
            'slug' => 'reserve-product',
            'sku' => 'SKU-RSV-1001',
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
