<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Contracts\PasswordHasherInterface;
use Modules\User\Models\UserModel;
use Modules\Warehouse\Services\WarehouseAuthorizationService;
use Tests\TestCase;

final class BooleanQueryFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_warehouse_boolean_filters_accept_supported_query_representations(): void
    {
        [$tenantId, $organizationUnitId, $userId] = $this->scope();
        $this->warehouse($tenantId, $organizationUnitId, $userId, 'ACTIVE', true);
        $this->warehouse($tenantId, $organizationUnitId, $userId, 'INACTIVE', false);

        foreach (['true', '1'] as $value) {
            $this->asWarehouseUser($userId)->getJson($this->warehouseUrl($tenantId, $organizationUnitId, $value))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.code', 'ACTIVE')
                ->assertJsonPath('meta.current_page', 1)
                ->assertJsonPath('meta.last_page', 1);
        }

        foreach (['false', '0'] as $value) {
            $this->asWarehouseUser($userId)->getJson($this->warehouseUrl($tenantId, $organizationUnitId, $value))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.code', 'INACTIVE');
        }

        $this->asWarehouseUser($userId)->getJson($this->warehouseUrl($tenantId, $organizationUnitId, 'yes'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');
    }

    public function test_warehouse_record_access_is_tenant_scoped(): void
    {
        [$tenantId, $organizationUnitId, $userId] = $this->scope();
        [$otherTenantId, $otherOrganizationUnitId, $otherUserId] = $this->scope();
        $sameTenantOtherOrganizationId = $this->organization($tenantId);
        $warehouseId = $this->warehouse($tenantId, $organizationUnitId, $userId, 'PRIVATE', true);

        $scope = '?tenant_id='.$otherTenantId.'&organization_unit_id='.$otherOrganizationUnitId;

        $this->asWarehouseUser($otherUserId)->getJson('/api/v1/warehouses/'.$warehouseId.$scope)->assertNotFound();
        $this->asWarehouseUser($otherUserId)->patchJson('/api/v1/warehouses/'.$warehouseId, [
            'tenant_id' => $otherTenantId,
            'organization_unit_id' => $otherOrganizationUnitId,
            'row_version' => 1,
            'name' => 'Cross tenant update',
        ])->assertNotFound();
        $this->asWarehouseUser($otherUserId)->deleteJson('/api/v1/warehouses/'.$warehouseId.$scope)->assertNotFound();

        $organizationScope = '?tenant_id='.$tenantId.'&organization_unit_id='.$sameTenantOtherOrganizationId;

        $this->asWarehouseUser($userId)->getJson('/api/v1/warehouses/'.$warehouseId.$organizationScope)->assertNotFound();
        $this->asWarehouseUser($userId)->patchJson('/api/v1/warehouses/'.$warehouseId, [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $sameTenantOtherOrganizationId,
            'row_version' => 1,
            'name' => 'Cross organization update',
        ])->assertNotFound();
        $this->asWarehouseUser($userId)->deleteJson('/api/v1/warehouses/'.$warehouseId.$organizationScope)->assertNotFound();
    }

    private function warehouseUrl(int $tenantId, int $organizationUnitId, string $isActive): string
    {
        return '/api/v1/warehouses?tenant_id='.$tenantId
            .'&organization_unit_id='.$organizationUnitId
            .'&is_active='.$isActive;
    }

    private function warehouse(int $tenantId, int $organizationUnitId, int $userId, string $code, bool $isActive): int
    {
        return (int) $this->asWarehouseUser($userId)->postJson('/api/v1/warehouses', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => $code,
            'name' => $code.' Warehouse',
            'type' => 'standard',
            'is_active' => $isActive,
        ])->assertCreated()->json('data.id');
    }

    /**
     * @return array{int, int, int}
     */
    private function scope(): array
    {
        $suffix = Str::upper(Str::random(8));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-'.$suffix,
            'name' => 'Tenant '.$suffix,
            'slug' => 'tenant-'.Str::lower($suffix),
            'status' => 'active',
            'is_active' => true,
            'is_isolated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $organizationUnitId = $this->organization($tenantId, $suffix);
        $userId = $this->userWithWarehousePermissions($tenantId, $organizationUnitId, $suffix);

        return [$tenantId, $organizationUnitId, $userId];
    }

    private function organization(int $tenantId, ?string $suffix = null): int
    {
        $suffix ??= Str::upper(Str::random(8));

        return (int) DB::table('organization_units')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Organization '.$suffix,
            'code' => 'ORG-'.$suffix,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function userWithWarehousePermissions(int $tenantId, int $organizationUnitId, string $suffix): int
    {
        $now = now();
        $userId = (int) DB::table('users')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'first_name' => 'Warehouse',
            'last_name' => 'Filter',
            'email' => 'warehouse-filter-'.Str::lower($suffix).'@example.test',
            'password' => app(PasswordHasherInterface::class)->hash('secret-password'),
            'status' => 'active',
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $roleId = (int) DB::table('roles')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'name' => 'Warehouse Filter Role',
            'guard_name' => 'web',
            'description' => 'Warehouse filter test role',
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach (array_keys(WarehouseAuthorizationService::descriptions()) as $permissionName) {
            $permissionId = (int) DB::table('permissions')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'name' => $permissionName,
                'guard_name' => 'web',
                'module' => 'Warehouse',
                'description' => WarehouseAuthorizationService::descriptions()[$permissionName],
                'row_version' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('role_permissions')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'row_version' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('user_roles')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $userId;
    }

    private function asWarehouseUser(int $userId): self
    {
        $this->actingAs(UserModel::query()->findOrFail($userId));

        return $this;
    }
}
