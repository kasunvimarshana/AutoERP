<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseServiceInterface;
use Tests\TestCase;

class WarehouseDeleteTenantGuardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_warehouse_rejects_cross_tenant_id(): void
    {
        $this->seedTenants();
        $this->seedWarehouses();

        /** @var DeleteWarehouseServiceInterface $deleteWarehouseService */
        $deleteWarehouseService = app(DeleteWarehouseServiceInterface::class);

        $deleted = $deleteWarehouseService->execute([
            'id' => 9201,
            'tenant_id' => 11,
        ]);

        $this->assertFalse($deleted);
        $this->assertDatabaseHas('warehouses', [
            'id' => 9201,
            'tenant_id' => 12,
            'name' => 'Warehouse Tenant 12',
        ]);
    }

    public function test_delete_warehouse_allows_matching_tenant(): void
    {
        $this->seedTenants();
        $this->seedWarehouses();

        /** @var DeleteWarehouseServiceInterface $deleteWarehouseService */
        $deleteWarehouseService = app(DeleteWarehouseServiceInterface::class);

        $deleted = $deleteWarehouseService->execute([
            'id' => 9101,
            'tenant_id' => 11,
        ]);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('warehouses', ['id' => 9101]);
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
                'id' => 9101,
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
                'id' => 9201,
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
}
