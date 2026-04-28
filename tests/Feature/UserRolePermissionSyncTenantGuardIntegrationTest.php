<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\User\Domain\Entities\Role;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Domain\Entities\User;
use Tests\TestCase;

class UserRolePermissionSyncTenantGuardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenants();
        $this->seedUsers();
        $this->seedRoles();
        $this->seedPermissions();
        $this->seedPivotRows();
    }

    public function test_sync_roles_keeps_updates_within_tenant_scope(): void
    {
        /** @var UserRepositoryInterface $userRepository */
        $userRepository = app(UserRepositoryInterface::class);

        /** @var User|null $user */
        $user = $userRepository->findByTenantAndId(11, 1101);

        $this->assertInstanceOf(User::class, $user);

        $userRepository->syncRoles($user, [2101, 2201]);

        $tenant11RoleIds = DB::table('role_user')
            ->where('tenant_id', 11)
            ->where('user_id', 1101)
            ->orderBy('role_id')
            ->pluck('role_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $tenant12RoleIds = DB::table('role_user')
            ->where('tenant_id', 12)
            ->where('user_id', 1201)
            ->orderBy('role_id')
            ->pluck('role_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $this->assertSame([2101], $tenant11RoleIds);
        $this->assertSame([2201], $tenant12RoleIds);
    }

    public function test_sync_permissions_keeps_updates_within_tenant_scope(): void
    {
        /** @var RoleRepositoryInterface $roleRepository */
        $roleRepository = app(RoleRepositoryInterface::class);

        $role = new Role(
            tenantId: 11,
            name: 'Manager 11',
            guardName: 'api',
            description: null,
            id: 2101,
        );

        $roleRepository->syncPermissions($role, [3101, 3201]);

        $tenant11PermissionIds = DB::table('permission_role')
            ->where('tenant_id', 11)
            ->where('role_id', 2101)
            ->orderBy('permission_id')
            ->pluck('permission_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $tenant12PermissionIds = DB::table('permission_role')
            ->where('tenant_id', 12)
            ->where('role_id', 2201)
            ->orderBy('permission_id')
            ->pluck('permission_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $this->assertSame([3101], $tenant11PermissionIds);
        $this->assertSame([3201], $tenant12PermissionIds);
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

    private function seedUsers(): void
    {
        DB::table('users')->insert([
            [
                'id' => 1101,
                'tenant_id' => 11,
                'org_unit_id' => null,
                'row_version' => 1,
                'first_name' => 'Tenant',
                'last_name' => 'Eleven',
                'email' => 'tenant11.role@example.com',
                'email_verified_at' => null,
                'password' => Hash::make('tenant-11-password'),
                'phone' => null,
                'avatar' => null,
                'status' => 'active',
                'preferences' => null,
                'address' => null,
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => 1201,
                'tenant_id' => 12,
                'org_unit_id' => null,
                'row_version' => 1,
                'first_name' => 'Tenant',
                'last_name' => 'Twelve',
                'email' => 'tenant12.role@example.com',
                'email_verified_at' => null,
                'password' => Hash::make('tenant-12-password'),
                'phone' => null,
                'avatar' => null,
                'status' => 'active',
                'preferences' => null,
                'address' => null,
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ]);
    }

    private function seedRoles(): void
    {
        DB::table('roles')->insert([
            [
                'id' => 2101,
                'tenant_id' => 11,
                'org_unit_id' => null,
                'row_version' => 1,
                'name' => 'Manager 11',
                'guard_name' => 'api',
                'description' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => 2102,
                'tenant_id' => 11,
                'org_unit_id' => null,
                'row_version' => 1,
                'name' => 'Operator 11',
                'guard_name' => 'api',
                'description' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => 2201,
                'tenant_id' => 12,
                'org_unit_id' => null,
                'row_version' => 1,
                'name' => 'Manager 12',
                'guard_name' => 'api',
                'description' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ]);
    }

    private function seedPermissions(): void
    {
        DB::table('permissions')->insert([
            [
                'id' => 3101,
                'tenant_id' => 11,
                'org_unit_id' => null,
                'row_version' => 1,
                'name' => 'users.view',
                'guard_name' => 'api',
                'module' => 'user',
                'description' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => 3102,
                'tenant_id' => 11,
                'org_unit_id' => null,
                'row_version' => 1,
                'name' => 'users.edit',
                'guard_name' => 'api',
                'module' => 'user',
                'description' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => 3201,
                'tenant_id' => 12,
                'org_unit_id' => null,
                'row_version' => 1,
                'name' => 'users.view',
                'guard_name' => 'api',
                'module' => 'user',
                'description' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ]);
    }

    private function seedPivotRows(): void
    {
        DB::table('role_user')->insert([
            [
                'tenant_id' => 11,
                'org_unit_id' => null,
                'row_version' => 1,
                'role_id' => 2102,
                'user_id' => 1101,
            ],
            [
                'tenant_id' => 12,
                'org_unit_id' => null,
                'row_version' => 1,
                'role_id' => 2201,
                'user_id' => 1201,
            ],
        ]);

        DB::table('permission_role')->insert([
            [
                'tenant_id' => 11,
                'org_unit_id' => null,
                'row_version' => 1,
                'permission_id' => 3102,
                'role_id' => 2101,
            ],
            [
                'tenant_id' => 12,
                'org_unit_id' => null,
                'row_version' => 1,
                'permission_id' => 3201,
                'role_id' => 2201,
            ],
        ]);
    }
}
