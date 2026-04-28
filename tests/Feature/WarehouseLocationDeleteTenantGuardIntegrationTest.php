<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseLocationServiceInterface;
use Tests\TestCase;

class WarehouseLocationDeleteTenantGuardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_location_rejects_cross_tenant_id(): void
    {
        $this->seedTenants();
        $this->seedWarehouses();
        $this->seedLocations();

        /** @var DeleteWarehouseLocationServiceInterface $deleteWarehouseLocationService */
        $deleteWarehouseLocationService = app(DeleteWarehouseLocationServiceInterface::class);

        $deleted = $deleteWarehouseLocationService->execute([
            'id' => 7201,
            'tenant_id' => 11,
            'warehouse_id' => 7101,
        ]);

        $this->assertFalse($deleted);
        $this->assertDatabaseHas('warehouse_locations', [
            'id' => 7201,
            'tenant_id' => 12,
            'warehouse_id' => 7102,
            'name' => 'Location Tenant 12',
        ]);
    }

    public function test_delete_location_rejects_wrong_warehouse_context(): void
    {
        $this->seedTenants();
        $this->seedWarehouses();
        $this->seedLocations();

        /** @var DeleteWarehouseLocationServiceInterface $deleteWarehouseLocationService */
        $deleteWarehouseLocationService = app(DeleteWarehouseLocationServiceInterface::class);

        $deleted = $deleteWarehouseLocationService->execute([
            'id' => 7101,
            'tenant_id' => 11,
            'warehouse_id' => 7102,
        ]);

        $this->assertFalse($deleted);
        $this->assertDatabaseHas('warehouse_locations', [
            'id' => 7101,
            'tenant_id' => 11,
            'warehouse_id' => 7101,
            'name' => 'Location Tenant 11',
        ]);
    }

    public function test_delete_location_allows_matching_tenant_and_warehouse(): void
    {
        $this->seedTenants();
        $this->seedWarehouses();
        $this->seedLocations();

        /** @var DeleteWarehouseLocationServiceInterface $deleteWarehouseLocationService */
        $deleteWarehouseLocationService = app(DeleteWarehouseLocationServiceInterface::class);

        $deleted = $deleteWarehouseLocationService->execute([
            'id' => 7101,
            'tenant_id' => 11,
            'warehouse_id' => 7101,
        ]);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('warehouse_locations', ['id' => 7101]);
    }

    private function seedTenants(): void
    {
        foreach ([11, 12] as $tenantId) {
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
    }

    private function seedWarehouses(): void
    {
        DB::table('warehouses')->insert([
            [
                'id' => 7101,
                'tenant_id' => 11,
                'org_unit_id' => null,
                'row_version' => 1,
                'name' => 'Warehouse Tenant 11',
                'code' => 'W11',
                'image_path' => null,
                'type' => 'standard',
                'address_id' => null,
                'is_active' => true,
                'is_default' => false,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7102,
                'tenant_id' => 12,
                'org_unit_id' => null,
                'row_version' => 1,
                'name' => 'Warehouse Tenant 12',
                'code' => 'W12',
                'image_path' => null,
                'type' => 'standard',
                'address_id' => null,
                'is_active' => true,
                'is_default' => false,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function seedLocations(): void
    {
        DB::table('warehouse_locations')->insert([
            [
                'id' => 7101,
                'tenant_id' => 11,
                'org_unit_id' => null,
                'row_version' => 1,
                'warehouse_id' => 7101,
                'parent_id' => null,
                'name' => 'Location Tenant 11',
                'code' => 'L11',
                'path' => 'l11',
                'depth' => 0,
                'type' => 'bin',
                'is_active' => true,
                'is_pickable' => true,
                'is_receivable' => true,
                'capacity' => null,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7201,
                'tenant_id' => 12,
                'org_unit_id' => null,
                'row_version' => 1,
                'warehouse_id' => 7102,
                'parent_id' => null,
                'name' => 'Location Tenant 12',
                'code' => 'L12',
                'path' => 'l12',
                'depth' => 0,
                'type' => 'bin',
                'is_active' => true,
                'is_pickable' => true,
                'is_receivable' => true,
                'capacity' => null,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
