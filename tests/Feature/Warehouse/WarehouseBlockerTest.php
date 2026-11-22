<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Tests\Feature\Warehouse;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;
use Modules\Warehouse\Services\WarehouseDomainService;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class WarehouseBlockerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
    }

    public function test_warehouse_cannot_be_deleted_when_referenced_by_sales(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $suffix = Str::upper(Str::random(6));
            $orgId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'Org WH '.$suffix,
                'code' => 'ORGWH-'.$suffix,
            ]);

            $warehouse = WarehouseModel::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'name' => 'WH Sales '.$suffix,
                'code' => 'WHS-'.$suffix,
                'type' => 'standard',
                'is_active' => true,
                'is_default' => false,
            ]);

            $customer = DB::table('customers')->insertGetId([
                'tenant_id' => $tenantId,
                'customer_number' => 'CN-'.$suffix,
                'customer_type' => 'individual',
                'name' => 'Sales Customer',
                'code' => 'CUS-'.$suffix,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('sales')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'customer_id' => $customer,
                'warehouse_id' => $warehouse->getKey(),
                'sale_number' => 'SALE-WH-'.$suffix,
                'sale_date' => '2026-11-22',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Warehouse cannot be deleted while inventory or operational documents reference it.');

            app(WarehouseDomainService::class)->assertWarehouseCanBeDeleted($warehouse);
        });
    }

    public function test_warehouse_location_cannot_be_deleted_when_referenced_by_sales(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $suffix = Str::upper(Str::random(6));
            $orgId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'Org Loc '.$suffix,
                'code' => 'ORGL-'.$suffix,
            ]);

            $warehouse = WarehouseModel::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'name' => 'WH Loc '.$suffix,
                'code' => 'WHL-'.$suffix,
                'type' => 'standard',
                'is_active' => true,
                'is_default' => false,
            ]);

            $location = WarehouseLocationModel::query()->create([
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouse->getKey(),
                'name' => 'Aisle 1',
                'code' => 'A1-'.$suffix,
                'type' => 'shelf',
                'path' => '/a1-'.$suffix,
                'depth' => 0,
                'is_active' => true,
            ]);

            $customer = DB::table('customers')->insertGetId([
                'tenant_id' => $tenantId,
                'customer_number' => 'CN-LOC-'.$suffix,
                'customer_type' => 'individual',
                'name' => 'Sales Customer Loc',
                'code' => 'CUS-LOC-'.$suffix,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('sales')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'customer_id' => $customer,
                'warehouse_id' => $warehouse->getKey(),
                'warehouse_location_id' => $location->getKey(),
                'sale_number' => 'SALE-LOC-'.$suffix,
                'sale_date' => '2026-11-22',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Warehouse location cannot be deleted while inventory or operational documents reference it.');

            app(WarehouseDomainService::class)->assertLocationCanBeDeleted($location);
        });
    }

    public function test_unreferenced_warehouse_can_be_deleted(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $suffix = Str::upper(Str::random(6));
            $orgId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'Org Clean '.$suffix,
                'code' => 'ORGCLN-'.$suffix,
            ]);

            $warehouse = WarehouseModel::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'name' => 'WH Clean '.$suffix,
                'code' => 'WHC-'.$suffix,
                'type' => 'standard',
                'is_active' => true,
                'is_default' => false,
            ]);

            // No exception thrown
            app(WarehouseDomainService::class)->assertWarehouseCanBeDeleted($warehouse);
            $warehouse->delete();

            $this->assertSoftDeleted('warehouses', ['id' => $warehouse->getKey()]);
        });
    }

    private function tenant(): int
    {
        $suffix = Str::upper(Str::random(8));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Warehouse Test Tenant',
            'code' => 'wh-test-'.$suffix,
            'slug' => 'warehouse-test-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
