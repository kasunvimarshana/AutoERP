<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Contracts\PasswordHasherInterface;
use Modules\Warehouse\Services\WarehouseAuthorizationService;
use Tests\TestCase;

final class WarehouseApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_warehouse_switches_within_scope_and_remains_independent_across_organizations(): void
    {
        $context = $this->createAuthContext();

        $firstId = $this->createWarehouse($context, ['code' => 'WH-A', 'name' => 'Warehouse A', 'is_default' => true]);
        $secondId = $this->createWarehouse($context, ['code' => 'WH-B', 'name' => 'Warehouse B', 'is_default' => true]);

        $this->assertDatabaseHas('warehouses', ['id' => $firstId, 'is_default' => false]);
        $this->assertDatabaseHas('warehouses', ['id' => $secondId, 'is_default' => true]);
        $this->assertSame(1, DB::table('warehouses')
            ->where('tenant_id', $context['tenant_id'])
            ->where('organization_unit_id', $context['organization_unit_id'])
            ->where('is_default', true)
            ->count());

        $otherOrgId = $this->createOrganizationUnit($context['tenant_id'], 'Branch', 'BRN');
        $this->assignOrganizationUnit($context, $otherOrgId);
        $thirdId = $this->createWarehouse($context, [
            'code' => 'WH-C',
            'name' => 'Warehouse C',
            'is_default' => true,
        ], $otherOrgId);

        $this->assertDatabaseHas('warehouses', ['id' => $secondId, 'is_default' => true]);
        $this->assertDatabaseHas('warehouses', ['id' => $thirdId, 'is_default' => true]);
        $this->assertSame(1, DB::table('warehouses')->where('organization_unit_id', $otherOrgId)->where('is_default', true)->count());
    }

    public function test_inactive_records_cannot_be_saved_as_defaults_and_deactivation_clears_default(): void
    {
        $context = $this->createAuthContext();

        $this->withAuth($context)->postJson('/api/v1/warehouses', $this->warehousePayload([
            'code' => 'INACTIVE',
            'name' => 'Inactive Default',
            'is_active' => false,
            'is_default' => true,
        ]))->assertUnprocessable()->assertJsonValidationErrors(['is_default']);

        $warehouseId = $this->createWarehouse($context, ['code' => 'WH-DEF', 'name' => 'Default Warehouse', 'is_default' => true]);
        $this->withAuth($context)->patchJson('/api/v1/warehouses/'.$warehouseId.'/deactivate')
            ->assertOk()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.is_default', false);

        $this->assertDatabaseHas('warehouses', ['id' => $warehouseId, 'is_active' => false, 'is_default' => false]);

        $locationWarehouseId = $this->createWarehouse($context, ['code' => 'WH-LOC', 'name' => 'Location Warehouse']);
        $locationId = $this->createLocation($context, $locationWarehouseId, [
            'code' => 'LOC-A',
            'name' => 'Default Location',
            'is_default' => true,
        ]);

        $this->withAuth($context)->postJson('/api/v1/warehouse-locations', $this->locationPayload($locationWarehouseId, [
            'code' => 'LOC-X',
            'name' => 'Inactive Location',
            'is_active' => false,
            'is_default' => true,
        ]))->assertUnprocessable()->assertJsonValidationErrors(['is_default']);

        $this->withAuth($context)->patchJson('/api/v1/warehouse-locations/'.$locationId.'/deactivate')
            ->assertOk()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.is_default', false);
    }

    public function test_default_locations_switch_within_warehouse_and_are_independent_across_warehouses(): void
    {
        $context = $this->createAuthContext();
        $warehouseA = $this->createWarehouse($context, ['code' => 'WH-A', 'name' => 'Warehouse A']);
        $warehouseB = $this->createWarehouse($context, ['code' => 'WH-B', 'name' => 'Warehouse B']);

        $firstId = $this->createLocation($context, $warehouseA, ['code' => 'A-1', 'name' => 'A One', 'is_default' => true]);
        $secondId = $this->createLocation($context, $warehouseA, ['code' => 'A-2', 'name' => 'A Two', 'is_default' => true]);
        $thirdId = $this->createLocation($context, $warehouseB, ['code' => 'B-1', 'name' => 'B One', 'is_default' => true]);

        $this->assertDatabaseHas('warehouse_locations', ['id' => $firstId, 'is_default' => false]);
        $this->assertDatabaseHas('warehouse_locations', ['id' => $secondId, 'is_default' => true]);
        $this->assertDatabaseHas('warehouse_locations', ['id' => $thirdId, 'is_default' => true]);
        $this->assertSame(1, DB::table('warehouse_locations')->where('warehouse_id', $warehouseA)->where('is_default', true)->count());
        $this->assertSame(1, DB::table('warehouse_locations')->where('warehouse_id', $warehouseB)->where('is_default', true)->count());
    }

    public function test_location_scope_and_hierarchy_are_backend_managed(): void
    {
        $context = $this->createAuthContext();
        $warehouseId = $this->createWarehouse($context, ['code' => 'WH-H', 'name' => 'Hierarchy Warehouse']);
        $zoneId = $this->createLocation($context, $warehouseId, ['code' => 'ZA', 'name' => 'Zone A', 'type' => 'zone']);
        $rackId = $this->createLocation($context, $warehouseId, [
            'code' => 'RB',
            'name' => 'Rack B',
            'type' => 'rack',
            'parent_id' => $zoneId,
            'path' => '/client/path',
            'depth' => 99,
        ]);

        $this->assertDatabaseHas('warehouse_locations', [
            'id' => $rackId,
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'path' => '/za/rb',
            'depth' => 1,
        ]);

        $rackVersion = (int) DB::table('warehouse_locations')->where('id', $rackId)->value('row_version');
        $this->withAuth($context)->putJson('/api/v1/warehouse-locations/'.$rackId, [
            'row_version' => $rackVersion,
            'parent_id' => $rackId,
        ])->assertUnprocessable();

        $zoneVersion = (int) DB::table('warehouse_locations')->where('id', $zoneId)->value('row_version');
        $this->withAuth($context)->putJson('/api/v1/warehouse-locations/'.$zoneId, [
            'row_version' => $zoneVersion,
            'parent_id' => $rackId,
        ])->assertUnprocessable();

        $this->withAuth($context)->putJson('/api/v1/warehouse-locations/'.$zoneId, [
            'row_version' => $zoneVersion,
            'code' => 'ZC',
            'name' => 'Zone C',
        ])->assertOk();

        $this->assertDatabaseHas('warehouse_locations', ['id' => $zoneId, 'path' => '/zc', 'depth' => 0]);
        $this->assertDatabaseHas('warehouse_locations', ['id' => $rackId, 'path' => '/zc/rb', 'depth' => 1]);
    }

    public function test_cross_tenant_cross_organization_and_foreign_parent_access_are_rejected(): void
    {
        $contextA = $this->createAuthContext(['code' => 'TEN-A', 'email' => 'tenant-a@example.test']);
        $contextB = $this->createAuthContext(['code' => 'TEN-B', 'email' => 'tenant-b@example.test']);
        $warehouseA = $this->createWarehouse($contextA, ['code' => 'WH-A', 'name' => 'Tenant A Warehouse']);

        $this->withAuth($contextB)->getJson('/api/v1/warehouses/'.$warehouseA)->assertForbidden();

        $otherOrgId = $this->createOrganizationUnit($contextA['tenant_id'], 'Other Branch', 'OTH');
        $this->assignOrganizationUnit($contextA, $otherOrgId);
        $this->withAuth($contextA, $otherOrgId)->getJson('/api/v1/warehouses/'.$warehouseA)->assertNotFound();

        $warehouseOtherOrg = $this->createWarehouse($contextA, ['code' => 'WH-O', 'name' => 'Other Warehouse'], $otherOrgId);
        $parentOtherOrg = $this->createLocation($contextA, $warehouseOtherOrg, ['code' => 'OTH-P', 'name' => 'Other Parent'], $otherOrgId);

        $this->withAuth($contextA)->postJson('/api/v1/warehouse-locations', $this->locationPayload($warehouseA, [
            'code' => 'BAD-PARENT',
            'name' => 'Bad Parent',
            'parent_id' => $parentOtherOrg,
        ]))->assertUnprocessable();
    }

    public function test_row_version_conflict_returns_conflict_and_resources_are_readable(): void
    {
        $context = $this->createAuthContext();
        $warehouseId = $this->createWarehouse($context, ['code' => 'WH-RV', 'name' => 'Versioned Warehouse']);

        $this->withAuth($context)->putJson('/api/v1/warehouses/'.$warehouseId, [
            'row_version' => 1,
            'name' => 'Updated Warehouse',
        ])->assertOk()->assertJsonPath('data.row_version', 2);

        $this->withAuth($context)->putJson('/api/v1/warehouses/'.$warehouseId, [
            'row_version' => 1,
            'name' => 'Stale Warehouse',
        ])->assertStatus(409);

        $this->withAuth($context)->getJson('/api/v1/warehouses/'.$warehouseId)
            ->assertOk()
            ->assertJsonPath('data.organization_unit.id', $context['organization_unit_id'])
            ->assertJsonPath('data.locations_count', 0);

        $locationId = $this->createLocation($context, $warehouseId, ['code' => 'RV-LOC', 'name' => 'Versioned Location']);
        $this->withAuth($context)->putJson('/api/v1/warehouse-locations/'.$locationId, [
            'row_version' => 1,
            'name' => 'Updated Location',
        ])->assertOk()->assertJsonPath('data.row_version', 2);

        $this->withAuth($context)->putJson('/api/v1/warehouse-locations/'.$locationId, [
            'row_version' => 1,
            'name' => 'Stale Location',
        ])->assertStatus(409);
    }

    public function test_lifecycle_safety_and_lookup_filters(): void
    {
        $context = $this->createAuthContext();
        $warehouseId = $this->createWarehouse($context, ['code' => 'WH-LIFE', 'name' => 'Lifecycle Warehouse']);
        $parentId = $this->createLocation($context, $warehouseId, ['code' => 'PARENT', 'name' => 'Parent', 'type' => 'zone']);
        $this->createLocation($context, $warehouseId, ['code' => 'CHILD', 'name' => 'Child', 'parent_id' => $parentId]);

        $this->withAuth($context)->deleteJson('/api/v1/warehouses/'.$warehouseId)->assertUnprocessable();
        $this->withAuth($context)->deleteJson('/api/v1/warehouse-locations/'.$parentId)->assertUnprocessable();

        $inactiveWarehouseId = $this->createWarehouse($context, ['code' => 'WH-IN', 'name' => 'Inactive Warehouse', 'is_active' => false]);
        $this->withAuth($context)->getJson('/api/v1/warehouses?is_active=true')
            ->assertOk()
            ->assertJsonMissing(['id' => $inactiveWarehouseId]);

        $this->withAuth($context)->getJson('/api/v1/warehouses?is_active=0')
            ->assertOk()
            ->assertJsonFragment(['code' => 'WH-IN']);
    }

    public function test_exact_permissions_are_required_for_mutations(): void
    {
        $context = $this->createAuthContext([
            'permissions' => [WarehouseAuthorizationService::WAREHOUSES_VIEW],
            'code' => 'VIEW',
            'email' => 'view-only@example.test',
        ]);

        $this->withAuth($context)->getJson('/api/v1/warehouses')->assertOk();

        $this->withAuth($context)->postJson('/api/v1/warehouses', $this->warehousePayload([
            'code' => 'DENIED',
            'name' => 'Denied Warehouse',
        ]))->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function warehousePayload(array $overrides = []): array
    {
        return [
            'code' => 'MAIN',
            'name' => 'Main Warehouse',
            'type' => 'standard',
            'is_active' => true,
            'is_default' => false,
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function locationPayload(int $warehouseId, array $overrides = []): array
    {
        return [
            'warehouse_id' => $warehouseId,
            'parent_id' => null,
            'code' => 'BIN-A',
            'name' => 'Bin A',
            'type' => 'bin',
            'capacity' => null,
            'is_active' => true,
            'is_pickable' => true,
            'is_receivable' => true,
            'is_default' => false,
            ...$overrides,
        ];
    }

    private function createWarehouse(array $context, array $overrides = [], ?int $organizationUnitId = null): int
    {
        return (int) $this->withAuth($context, $organizationUnitId)
            ->postJson('/api/v1/warehouses', $this->warehousePayload($overrides))
            ->assertCreated()
            ->json('data.id');
    }

    private function createLocation(array $context, int $warehouseId, array $overrides = [], ?int $organizationUnitId = null): int
    {
        return (int) $this->withAuth($context, $organizationUnitId)
            ->postJson('/api/v1/warehouse-locations', $this->locationPayload($warehouseId, $overrides))
            ->assertCreated()
            ->json('data.id');
    }

    /**
     * @param  array{permissions?:list<string>,code?:string,email?:string}  $overrides
     * @return array{tenant_id:int,organization_unit_id:int,user_id:int,role_id:int,token:string}
     */
    private function createAuthContext(array $overrides = []): array
    {
        $now = now();
        $code = strtoupper((string) ($overrides['code'] ?? 'WH-'.Str::random(6)));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => $code.' Tenant',
            'slug' => Str::lower($code).'-tenant',
            'status' => 'active',
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now]);
        $organizationUnitId = $this->createOrganizationUnit($tenantId, 'Main', 'MAIN');
        $email = (string) ($overrides['email'] ?? Str::lower($code).'@example.test');
        $userId = (int) \Tests\Support\TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'Warehouse',
            'last_name' => 'Tester',
            'email' => $email,
            'password' => app(PasswordHasherInterface::class)->hash('secret-password'),
            'status' => 'active',
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $roleId = (int) DB::table('roles')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Warehouse Test Role',
            'guard_name' => 'auth-api',
            'description' => 'Warehouse test role',
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->assignOrganizationUnit(['tenant_id' => $tenantId, 'user_id' => $userId, 'role_id' => $roleId], $organizationUnitId, true);
        $this->assignRolePermissions($tenantId, $roleId, $overrides['permissions'] ?? array_keys(WarehouseAuthorizationService::descriptions()));

        DB::table('user_roles')->insert([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->assignOrganizationUnit([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
        ], $organizationUnitId, true);

        DB::table('auth_providers')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'provider_key' => 'internal',
            'name' => 'Internal password login',
            'guard_name' => 'auth-api',
            'provider_name' => 'users',
            'driver' => 'internal',
            'status' => 'active',
            'is_sso' => false,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $token = (string) $this->postJson('/api/v1/auth/login', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'login_identifier' => $email,
            'password' => 'secret-password',
        ])->assertOk()->json('token');

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'token' => $token,
        ];
    }

    private function createOrganizationUnit(int $tenantId, string $name, string $code): int
    {
        return (int) \Tests\Support\OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'code' => $code,
            'path' => '/'.Str::lower($code),
            'is_active' => true,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assignOrganizationUnit(array $context, int $organizationUnitId, bool $isDefault = false): void
    {
        DB::table('user_organization_units')->updateOrInsert([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $context['user_id'],
        ], [
            'status' => 'active',
            'is_default' => $isDefault,
            'default_marker' => $isDefault ? 'default' : null,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function assignRolePermissions(int $tenantId, int $roleId, array $permissions): void
    {
        foreach ($permissions as $name) {
            $permissionId = (int) DB::table('permissions')->insertGetId([
                'tenant_id' => $tenantId,
                'name' => $name,
                'guard_name' => 'auth-api',
                'module' => 'Warehouse',
                'description' => WarehouseAuthorizationService::descriptions()[$name] ?? 'Warehouse test permission',
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('role_permissions')->insert([
                'tenant_id' => $tenantId,
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function withAuth(array $context, ?int $organizationUnitId = null): self
    {
        $targetOrganizationUnitId = $organizationUnitId ?? $context['organization_unit_id'];
        $this->withToken($context['token'])
            ->withHeaders(['X-Tenant-Id' => (string) $context['tenant_id']])
            ->postJson('/api/v1/auth/organization-unit/switch', [
                'target_organization_unit_id' => $targetOrganizationUnitId,
            ])
            ->assertOk();

        return $this->withToken($context['token'])->withHeaders([
            'X-Tenant-Id' => (string) $context['tenant_id'],
        ]);
    }
}
