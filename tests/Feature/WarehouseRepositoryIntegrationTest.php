<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\Entities\WarehouseLocation;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseLocationRepositoryInterface;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;
use Tests\TestCase;

class WarehouseRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;
    private int $tenant2Id = 2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenants();
    }

    // ── WarehouseRepository ───────────────────────────────────────────────────

    public function test_warehouse_save_and_find(): void
    {
        /** @var WarehouseRepositoryInterface $repository */
        $repository = app(WarehouseRepositoryInterface::class);

        $saved = $repository->save(new Warehouse(
            tenantId: $this->tenantId,
            name: 'Main Warehouse',
            type: 'standard',
            code: 'WH-MAIN',
            isActive: true,
            isDefault: true,
        ));

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('Main Warehouse', $found->getName());
        $this->assertSame('standard', $found->getType());
        $this->assertSame('WH-MAIN', $found->getCode());
        $this->assertTrue($found->isActive());
        $this->assertTrue($found->isDefault());
        $this->assertSame($this->tenantId, $found->getTenantId());
    }

    public function test_warehouse_find_by_tenant_and_code(): void
    {
        /** @var WarehouseRepositoryInterface $repository */
        $repository = app(WarehouseRepositoryInterface::class);

        $saved = $repository->save(new Warehouse(
            tenantId: $this->tenantId,
            name: 'Transit Hub',
            type: 'transit',
            code: 'WH-TRANSIT',
        ));

        $found = $repository->findByTenantAndCode($this->tenantId, 'WH-TRANSIT');
        $wrongTenant = $repository->findByTenantAndCode($this->tenant2Id, 'WH-TRANSIT');
        $notFound = $repository->findByTenantAndCode($this->tenantId, 'WH-MISSING');

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertNull($wrongTenant);
        $this->assertNull($notFound);
    }

    public function test_warehouse_clear_default_for_tenant(): void
    {
        /** @var WarehouseRepositoryInterface $repository */
        $repository = app(WarehouseRepositoryInterface::class);

        $wh1 = $repository->save(new Warehouse(
            tenantId: $this->tenantId,
            name: 'Warehouse A',
            type: 'standard',
            isDefault: true,
        ));
        $wh2 = $repository->save(new Warehouse(
            tenantId: $this->tenantId,
            name: 'Warehouse B',
            type: 'standard',
            isDefault: true,
        ));
        // Warehouse for different tenant — must remain unaffected
        $wh3 = $repository->save(new Warehouse(
            tenantId: $this->tenant2Id,
            name: 'Tenant2 Warehouse',
            type: 'standard',
            isDefault: true,
        ));

        // Clear default for tenant 1, excluding wh2 (the new default)
        $repository->clearDefaultForTenant($this->tenantId, $wh2->getId());

        $this->assertDatabaseHas('warehouses', ['id' => $wh1->getId(), 'is_default' => false]);
        $this->assertDatabaseHas('warehouses', ['id' => $wh2->getId(), 'is_default' => true]);
        $this->assertDatabaseHas('warehouses', ['id' => $wh3->getId(), 'is_default' => true]);
    }

    public function test_warehouse_clear_default_for_tenant_without_exclude(): void
    {
        /** @var WarehouseRepositoryInterface $repository */
        $repository = app(WarehouseRepositoryInterface::class);

        $wh1 = $repository->save(new Warehouse(
            tenantId: $this->tenantId,
            name: 'Default WH',
            type: 'standard',
            isDefault: true,
        ));
        $wh2 = $repository->save(new Warehouse(
            tenantId: $this->tenantId,
            name: 'Also Default',
            type: 'standard',
            isDefault: true,
        ));

        $repository->clearDefaultForTenant($this->tenantId);

        $this->assertDatabaseHas('warehouses', ['id' => $wh1->getId(), 'is_default' => false]);
        $this->assertDatabaseHas('warehouses', ['id' => $wh2->getId(), 'is_default' => false]);
    }

    // ── WarehouseLocationRepository ───────────────────────────────────────────

    public function test_warehouse_location_save_and_find(): void
    {
        /** @var WarehouseRepositoryInterface $whRepo */
        $whRepo = app(WarehouseRepositoryInterface::class);

        /** @var WarehouseLocationRepositoryInterface $repository */
        $repository = app(WarehouseLocationRepositoryInterface::class);

        $warehouse = $whRepo->save(new Warehouse(
            tenantId: $this->tenantId,
            name: 'Storage',
            type: 'standard',
        ));

        $saved = $repository->save(new WarehouseLocation(
            tenantId: $this->tenantId,
            warehouseId: $warehouse->getId(),
            name: 'Aisle 1',
            type: 'aisle',
            code: 'A1',
            isActive: true,
            isPickable: true,
            isReceivable: false,
        ));

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('Aisle 1', $found->getName());
        $this->assertSame('A1', $found->getCode());
        $this->assertSame('aisle', $found->getType());
        $this->assertTrue($found->isActive());
        $this->assertTrue($found->isPickable());
        $this->assertFalse($found->isReceivable());
        $this->assertSame($warehouse->getId(), $found->getWarehouseId());
    }

    public function test_warehouse_location_find_by_tenant_warehouse_and_code(): void
    {
        /** @var WarehouseRepositoryInterface $whRepo */
        $whRepo = app(WarehouseRepositoryInterface::class);

        /** @var WarehouseLocationRepositoryInterface $repository */
        $repository = app(WarehouseLocationRepositoryInterface::class);

        $wh1 = $whRepo->save(new Warehouse(tenantId: $this->tenantId, name: 'WH1', type: 'standard'));
        $wh2 = $whRepo->save(new Warehouse(tenantId: $this->tenantId, name: 'WH2', type: 'standard'));

        $saved = $repository->save(new WarehouseLocation(
            tenantId: $this->tenantId,
            warehouseId: $wh1->getId(),
            name: 'Bin A',
            type: 'bin',
            code: 'BIN-A',
        ));

        $found = $repository->findByTenantWarehouseAndCode($this->tenantId, $wh1->getId(), 'BIN-A');
        $wrongWarehouse = $repository->findByTenantWarehouseAndCode($this->tenantId, $wh2->getId(), 'BIN-A');
        $notFound = $repository->findByTenantWarehouseAndCode($this->tenantId, $wh1->getId(), 'MISSING');

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertNull($wrongWarehouse);
        $this->assertNull($notFound);
    }

    public function test_warehouse_location_list_by_warehouse(): void
    {
        /** @var WarehouseRepositoryInterface $whRepo */
        $whRepo = app(WarehouseRepositoryInterface::class);

        /** @var WarehouseLocationRepositoryInterface $repository */
        $repository = app(WarehouseLocationRepositoryInterface::class);

        $wh1 = $whRepo->save(new Warehouse(tenantId: $this->tenantId, name: 'WH-Alpha', type: 'standard'));
        $wh2 = $whRepo->save(new Warehouse(tenantId: $this->tenantId, name: 'WH-Beta', type: 'standard'));

        $loc1 = $repository->save(new WarehouseLocation(
            tenantId: $this->tenantId,
            warehouseId: $wh1->getId(),
            name: 'Zone A',
            type: 'zone',
            code: 'Z-A',
        ));
        $loc2 = $repository->save(new WarehouseLocation(
            tenantId: $this->tenantId,
            warehouseId: $wh1->getId(),
            name: 'Zone B',
            type: 'zone',
            code: 'Z-B',
        ));
        // Location in different warehouse — should not appear
        $repository->save(new WarehouseLocation(
            tenantId: $this->tenantId,
            warehouseId: $wh2->getId(),
            name: 'Zone X',
            type: 'zone',
            code: 'Z-X',
        ));

        $list = $repository->listByWarehouse($this->tenantId, $wh1->getId());

        $this->assertCount(2, $list);
        $ids = array_map(fn ($l) => $l->getId(), $list);
        $this->assertContains($loc1->getId(), $ids);
        $this->assertContains($loc2->getId(), $ids);
    }

    public function test_warehouse_location_update_descendant_paths(): void
    {
        /** @var WarehouseRepositoryInterface $whRepo */
        $whRepo = app(WarehouseRepositoryInterface::class);

        /** @var WarehouseLocationRepositoryInterface $repository */
        $repository = app(WarehouseLocationRepositoryInterface::class);

        $wh = $whRepo->save(new Warehouse(tenantId: $this->tenantId, name: 'Main', type: 'standard'));

        // Simulate a parent location with path "WH1/ZONE-A"
        $child1 = $repository->save(new WarehouseLocation(
            tenantId: $this->tenantId,
            warehouseId: $wh->getId(),
            name: 'Bin 1',
            type: 'bin',
            code: 'BIN-1',
            path: 'WH1/ZONE-A/BIN-1',
        ));
        $child2 = $repository->save(new WarehouseLocation(
            tenantId: $this->tenantId,
            warehouseId: $wh->getId(),
            name: 'Bin 2',
            type: 'bin',
            code: 'BIN-2',
            path: 'WH1/ZONE-A/BIN-2',
        ));
        // A location with a non-matching path — should be unaffected
        $other = $repository->save(new WarehouseLocation(
            tenantId: $this->tenantId,
            warehouseId: $wh->getId(),
            name: 'Other',
            type: 'bin',
            code: 'OTHER',
            path: 'WH1/ZONE-B/OTHER',
        ));

        $repository->updateDescendantPaths(
            tenantId: $this->tenantId,
            warehouseId: $wh->getId(),
            oldPrefix: 'WH1/ZONE-A',
            newPrefix: 'WH1/ZONE-X',
        );

        $this->assertDatabaseHas('warehouse_locations', ['id' => $child1->getId(), 'path' => 'WH1/ZONE-X/BIN-1']);
        $this->assertDatabaseHas('warehouse_locations', ['id' => $child2->getId(), 'path' => 'WH1/ZONE-X/BIN-2']);
        $this->assertDatabaseHas('warehouse_locations', ['id' => $other->getId(), 'path' => 'WH1/ZONE-B/OTHER']);
    }

    // ── Seed ──────────────────────────────────────────────────────────────────

    private function seedTenants(): void
    {
        foreach ([$this->tenantId, $this->tenant2Id] as $tid) {
            DB::table('tenants')->insert([
                'id' => $tid,
                'name' => 'Tenant '.$tid,
                'slug' => 'tenant-'.$tid,
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
}
