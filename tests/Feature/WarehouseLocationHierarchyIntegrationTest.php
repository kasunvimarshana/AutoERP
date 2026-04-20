<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Warehouse\Application\Contracts\CreateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseLocationServiceInterface;
use Tests\TestCase;

class WarehouseLocationHierarchyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_new_default_warehouse_demotes_previous_default(): void
    {
        $this->seedTenant(31);

        /** @var CreateWarehouseServiceInterface $createWarehouseService */
        $createWarehouseService = app(CreateWarehouseServiceInterface::class);

        $first = $createWarehouseService->execute([
            'tenant_id' => 31,
            'name' => 'Main Warehouse',
            'code' => 'MAIN',
            'is_default' => true,
        ]);

        $second = $createWarehouseService->execute([
            'tenant_id' => 31,
            'name' => 'Transit Warehouse',
            'code' => 'TRANSIT',
            'type' => 'transit',
            'is_default' => true,
        ]);

        $this->assertTrue($second->isDefault());
        $this->assertDatabaseHas('warehouses', ['id' => $first->getId(), 'is_default' => false]);
        $this->assertDatabaseHas('warehouses', ['id' => $second->getId(), 'is_default' => true]);
    }

    public function test_location_path_and_depth_are_maintained_for_children_on_parent_move(): void
    {
        $this->seedTenant(32);

        /** @var CreateWarehouseServiceInterface $createWarehouseService */
        $createWarehouseService = app(CreateWarehouseServiceInterface::class);
        /** @var CreateWarehouseLocationServiceInterface $createWarehouseLocationService */
        $createWarehouseLocationService = app(CreateWarehouseLocationServiceInterface::class);
        /** @var UpdateWarehouseLocationServiceInterface $updateWarehouseLocationService */
        $updateWarehouseLocationService = app(UpdateWarehouseLocationServiceInterface::class);

        $warehouse = $createWarehouseService->execute([
            'tenant_id' => 32,
            'name' => 'Operations Warehouse',
            'code' => 'OPS',
            'is_default' => true,
        ]);

        $zone = $createWarehouseLocationService->execute([
            'tenant_id' => 32,
            'warehouse_id' => $warehouse->getId(),
            'name' => 'Zone A',
            'code' => 'ZONE-A',
            'type' => 'zone',
        ]);

        $bin = $createWarehouseLocationService->execute([
            'tenant_id' => 32,
            'warehouse_id' => $warehouse->getId(),
            'parent_id' => $zone->getId(),
            'name' => 'Bin 1',
            'code' => 'BIN-1',
            'type' => 'bin',
        ]);

        $this->assertSame('zone-a', $zone->getPath());
        $this->assertSame('zone-a/bin-1', $bin->getPath());
        $this->assertSame(1, $bin->getDepth());

        $updatedZone = $updateWarehouseLocationService->execute([
            'id' => $zone->getId(),
            'tenant_id' => 32,
            'warehouse_id' => $warehouse->getId(),
            'name' => 'Zone Alpha',
            'code' => 'ZONE-ALPHA',
            'type' => 'zone',
            'is_active' => true,
            'is_pickable' => true,
            'is_receivable' => true,
        ]);

        $this->assertSame('zone-alpha', $updatedZone->getPath());

        $childPath = DB::table('warehouse_locations')->where('id', $bin->getId())->value('path');
        $childDepth = (int) DB::table('warehouse_locations')->where('id', $bin->getId())->value('depth');

        $this->assertSame('zone-alpha/bin-1', $childPath);
        $this->assertSame(1, $childDepth);
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
}
