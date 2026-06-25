<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Contracts\PasswordHasherInterface;
use Modules\User\Constants\UserPermission;
use Modules\User\Constants\UserStatus;
use Tests\TestCase;

final class UserAccessApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_list_search_filter_pagination_and_safe_resource_shape(): void
    {
        $context = $this->createAuthContext();
        $roleId = $this->createRole($context['tenant_id'], 'Service Manager');
        $branchId = $this->createOrganizationUnit($context['tenant_id'], 'Service Branch', 'SVC');
        $userId = $this->createUser($context['tenant_id'], $branchId, 'alice.user@gmail.com', [
            'first_name' => 'Alice',
            'last_name' => 'User',
        ]);
        $this->assignRole($context['tenant_id'], $userId, $roleId, $branchId);
        $this->assignOrganizationUnit($context['tenant_id'], $userId, $branchId, true);
        $this->createUser($context['tenant_id'], $context['organization_unit_id'], 'bob.user@gmail.com', [
            'first_name' => 'Bob',
        ]);

        $this->withAuth($context)
            ->getJson("/api/user/users?search=Alice&status=active&role_id={$roleId}&organization_unit_filter_id={$branchId}&per_page=1&page=1")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.email', 'alice.user@gmail.com')
            ->assertJsonPath('data.0.roles.0.name', 'Service Manager')
            ->assertJsonPath('data.0.organization_units.0.name', 'Service Branch')
            ->assertJsonMissingPath('data.0.password')
            ->assertJsonMissingPath('data.0.remember_token');
    }

    public function test_create_user_assigns_roles_and_organization_access_atomically(): void
    {
        $context = $this->createAuthContext();
        $roleId = $this->createRole($context['tenant_id'], 'Counter Staff');
        $branchId = $this->createOrganizationUnit($context['tenant_id'], 'Counter Branch', 'CNT');

        $response = $this->withAuth($context)->postJson('/api/user/users', [
            'first_name' => 'Created',
            'last_name' => 'User',
            'username' => 'created.user',
            'email' => 'created.user@gmail.com',
            'phone' => '+94115550000',
            'password' => 'secure-password',
            'status' => UserStatus::ACTIVE,
            'role_ids' => [$roleId],
            'organization_unit_ids' => [$context['organization_unit_id'], $branchId],
            'default_organization_unit_id' => $branchId,
        ])->assertCreated()
            ->assertJsonPath('data.email', 'created.user@gmail.com')
            ->assertJsonPath('data.roles.0.name', 'Counter Staff')
            ->assertJsonPath('data.organization_units.0.name', 'Counter Branch')
            ->assertJsonMissingPath('data.password');

        $userId = (int) $response->json('data.id');
        $this->assertDatabaseHas('user_roles', [
            'tenant_id' => $context['tenant_id'],
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);
        $this->assertDatabaseHas('user_organization_units', [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $branchId,
            'user_id' => $userId,
            'is_default' => true,
        ]);
        $this->assertNotSame(
            'secure-password',
            (string) DB::table('users')->where('id', $userId)->value('password'),
        );
    }

    public function test_edit_user_syncs_assignments_and_blocks_password_or_protected_identity_fields(): void
    {
        $context = $this->createAuthContext();
        $oldRoleId = $this->createRole($context['tenant_id'], 'Old Role');
        $newRoleId = $this->createRole($context['tenant_id'], 'New Role');
        $oldOrgId = $this->createOrganizationUnit($context['tenant_id'], 'Old Branch', 'OLD');
        $newOrgId = $this->createOrganizationUnit($context['tenant_id'], 'New Branch', 'NEW');
        $userId = $this->createUser($context['tenant_id'], $oldOrgId, 'editable.user@gmail.com');
        $this->assignRole($context['tenant_id'], $userId, $oldRoleId, $oldOrgId);
        $this->assignOrganizationUnit($context['tenant_id'], $userId, $oldOrgId, true);

        $hashBefore = (string) DB::table('users')->where('id', $userId)->value('password');
        $this->withAuth($context)->putJson("/api/user/users/{$userId}", [
            'row_version' => 1,
            'first_name' => 'Edited',
            'last_name' => 'User',
            'username' => 'edited.user',
            'email' => 'edited.user@gmail.com',
            'phone' => '+94112223333',
            'status' => UserStatus::ACTIVE,
            'role_ids' => [$newRoleId],
            'organization_unit_ids' => [$newOrgId],
            'default_organization_unit_id' => $newOrgId,
        ])->assertOk()
            ->assertJsonPath('data.first_name', 'Edited')
            ->assertJsonPath('data.roles.0.name', 'New Role')
            ->assertJsonPath('data.organization_units.0.name', 'New Branch');

        $this->assertDatabaseMissing('user_roles', [
            'tenant_id' => $context['tenant_id'],
            'user_id' => $userId,
            'role_id' => $oldRoleId,
        ]);
        $this->assertDatabaseHas('user_roles', [
            'tenant_id' => $context['tenant_id'],
            'user_id' => $userId,
            'role_id' => $newRoleId,
        ]);

        $this->withAuth($context)->putJson("/api/user/users/{$userId}", [
            'password' => 'another-password',
            'metadata' => ['identity_references' => ['internal' => 'rewired']],
            'email_verified_at' => now()->toISOString(),
        ])->assertUnprocessable();

        $this->assertSame($hashBefore, (string) DB::table('users')->where('id', $userId)->value('password'));
    }

    public function test_cross_tenant_assignment_ids_are_rejected_without_clearing_current_assignments(): void
    {
        $tenantA = $this->createAuthContext(['code' => 'TENA', 'email' => 'tenant.a.admin@gmail.com']);
        $tenantB = $this->createAuthContext(['code' => 'TENB', 'email' => 'tenant.b.admin@gmail.com']);
        $targetId = $this->createUser($tenantA['tenant_id'], $tenantA['organization_unit_id'], 'tenant.a.user@gmail.com');
        $roleA = $this->createRole($tenantA['tenant_id'], 'Tenant A Role');
        $roleB = $this->createRole($tenantB['tenant_id'], 'Tenant B Role');
        $this->assignRole($tenantA['tenant_id'], $targetId, $roleA, $tenantA['organization_unit_id']);
        $this->assignOrganizationUnit($tenantA['tenant_id'], $targetId, $tenantA['organization_unit_id'], true);

        $this->withAuth($tenantA)->putJson("/api/user/users/{$targetId}", [
            'row_version' => 1,
            'first_name' => 'Tenant',
            'email' => 'tenant.a.user@gmail.com',
            'role_ids' => [$roleB],
            'organization_unit_ids' => [$tenantB['organization_unit_id']],
            'default_organization_unit_id' => $tenantB['organization_unit_id'],
        ])->assertUnprocessable();

        $this->assertDatabaseHas('user_roles', [
            'tenant_id' => $tenantA['tenant_id'],
            'user_id' => $targetId,
            'role_id' => $roleA,
        ]);
        $this->assertDatabaseMissing('user_roles', [
            'tenant_id' => $tenantA['tenant_id'],
            'user_id' => $targetId,
            'role_id' => $roleB,
        ]);
    }

    public function test_self_deactivation_is_blocked(): void
    {
        $admin = $this->createAuthContext(['email' => 'sole.admin@gmail.com']);

        $this->withAuth($admin)->patchJson('/api/user/users/'.$admin['user_id'].'/deactivate')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'You cannot deactivate or delete your own active account.');
    }

    public function test_last_protected_admin_deactivation_is_blocked(): void
    {
        $operator = $this->createAuthContext(
            ['code' => 'LOCK', 'email' => 'operator.user@gmail.com'],
            [UserPermission::USERS_VIEW, UserPermission::USERS_DEACTIVATE],
            'Operator',
        );

        $protectedRoleId = $this->createRole($operator['tenant_id'], UserPermission::SUPER_ADMIN_ROLE);
        $protectedUserId = $this->createUser(
            $operator['tenant_id'],
            $operator['organization_unit_id'],
            'protected.admin@gmail.com',
        );
        $this->assignRole($operator['tenant_id'], $protectedUserId, $protectedRoleId, $operator['organization_unit_id']);

        $this->withAuth($operator)->patchJson("/api/user/users/{$protectedUserId}/deactivate")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'At least one active protected administrator must remain.');
    }

    public function test_exact_permissions_are_enforced_for_mutations(): void
    {
        $viewer = $this->createAuthContext(
            ['email' => 'viewer.user@gmail.com'],
            [UserPermission::USERS_VIEW],
            'Viewer',
        );

        $this->withAuth($viewer)->getJson('/api/user/users')
            ->assertOk();

        $this->withAuth($viewer)->postJson('/api/user/users', [
            'first_name' => 'Forbidden',
            'email' => 'forbidden.user@gmail.com',
            'password' => 'secure-password',
        ])->assertForbidden();
    }

    public function test_roles_create_update_detail_and_delete_guards(): void
    {
        $context = $this->createAuthContext();
        $permissionId = $this->permissionId($context['tenant_id'], UserPermission::USERS_VIEW);

        $roleId = (int) $this->withAuth($context)->postJson('/api/user/roles', [
            'name' => 'Auditor',
            'guard_name' => 'web',
            'description' => 'Read-only user auditor',
            'permission_ids' => [$permissionId],
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Auditor')
            ->assertJsonPath('data.permissions_count', 1)
            ->json('data.id');

        $this->withAuth($context)->getJson("/api/user/roles/{$roleId}")
            ->assertOk()
            ->assertJsonPath('data.permissions.0.name', UserPermission::USERS_VIEW);

        $assignedUserId = $this->createUser($context['tenant_id'], $context['organization_unit_id'], 'assigned.role@gmail.com');
        $this->assignRole($context['tenant_id'], $assignedUserId, $roleId, $context['organization_unit_id']);
        $this->withAuth($context)->deleteJson("/api/user/roles/{$roleId}")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Role is assigned to users and cannot be deleted.');

        $superRoleId = (int) DB::table('roles')
            ->where('tenant_id', $context['tenant_id'])
            ->where('name', UserPermission::SUPER_ADMIN_ROLE)
            ->value('id');
        $this->withAuth($context)->putJson("/api/user/roles/{$superRoleId}", ['name' => 'Renamed Admin'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Protected system roles cannot be modified.');
    }

    public function test_role_permission_sync_rejects_cross_tenant_permissions_and_preserves_existing_assignments(): void
    {
        $tenantA = $this->createAuthContext(['code' => 'RPA', 'email' => 'role.a.admin@gmail.com']);
        $tenantB = $this->createAuthContext(['code' => 'RPB', 'email' => 'role.b.admin@gmail.com']);
        $roleId = $this->createRole($tenantA['tenant_id'], 'Assignable Role');
        $allowedPermissionId = $this->permissionId($tenantA['tenant_id'], UserPermission::USERS_VIEW);
        $foreignPermissionId = $this->permissionId($tenantB['tenant_id'], UserPermission::USERS_VIEW);
        $this->assignPermission($tenantA['tenant_id'], $roleId, $allowedPermissionId);

        $this->withAuth($tenantA)->putJson("/api/user/roles/{$roleId}", [
            'row_version' => 1,
            'name' => 'Assignable Role',
            'guard_name' => 'web',
            'permission_ids' => [$foreignPermissionId],
        ])->assertUnprocessable();

        $this->assertDatabaseHas('role_permissions', [
            'tenant_id' => $tenantA['tenant_id'],
            'role_id' => $roleId,
            'permission_id' => $allowedPermissionId,
        ]);
        $this->assertDatabaseMissing('role_permissions', [
            'tenant_id' => $tenantA['tenant_id'],
            'role_id' => $roleId,
            'permission_id' => $foreignPermissionId,
        ]);
    }

    public function test_permissions_catalogue_is_read_only_and_grouped_metadata_is_returned(): void
    {
        $context = $this->createAuthContext();

        $this->withAuth($context)->getJson('/api/user/permissions?module=Users&search=users.view')
            ->assertOk()
            ->assertJsonPath('data.0.name', UserPermission::USERS_VIEW)
            ->assertJsonPath('data.0.module', 'Users')
            ->assertJsonPath('data.0.resource', 'users')
            ->assertJsonPath('data.0.action', 'view')
            ->assertJsonPath('data.0.status', 'system_defined')
            ->assertJsonPath('data.0.is_read_only', true);

        $this->withAuth($context)->postJson('/api/user/permissions', [
            'name' => 'unsafe.dynamic',
        ])->assertStatus(405);
    }

    /**
     * @param  array<string,mixed>  $overrides
     * @param  list<string>|null  $permissions
     * @return array{tenant_id:int,organization_unit_id:int,user_id:int,role_id:int,token:string}
     */
    private function createAuthContext(
        array $overrides = [],
        ?array $permissions = null,
        string $roleName = UserPermission::SUPER_ADMIN_ROLE,
    ): array {
        $now = now();
        $code = strtoupper((string) ($overrides['code'] ?? 'AUTOERP'));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => $code.' Tenant',
            'slug' => strtolower($code).'-tenant',
            'status' => 'active',
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now]);
        $organizationUnitId = $this->createOrganizationUnit($tenantId, 'Head Office', 'HO');
        $userId = $this->createUser(
            $tenantId,
            $organizationUnitId,
            (string) ($overrides['email'] ?? 'admin.user@gmail.com'),
            ['first_name' => 'Admin', 'last_name' => 'User'],
        );
        $roleId = $this->createRole($tenantId, $roleName);
        foreach ($this->seedPermissions($tenantId, $permissions ?? UserPermission::values()) as $permissionId) {
            $this->assignPermission($tenantId, $roleId, $permissionId);
        }
        $this->assignRole($tenantId, $userId, $roleId, $organizationUnitId);
        $this->assignOrganizationUnit($tenantId, $userId, $organizationUnitId, true);
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

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'login_identifier' => (string) ($overrides['email'] ?? 'admin.user@gmail.com'),
            'password' => 'secret-password',
        ]);
        $token = (string) $loginResponse->assertOk()->json('token');

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'token' => $token,
        ];
    }

    /**
     * @return list<int>
     */
    private function seedPermissions(int $tenantId, array $names): array
    {
        $ids = [];
        foreach ($names as $name) {
            $description = UserPermission::descriptions()[$name] ?? 'Test permission';
            $ids[] = (int) DB::table('permissions')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'name' => $name,
                'guard_name' => 'web',
                'module' => 'Users',
                'description' => $description,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $ids;
    }

    private function permissionId(int $tenantId, string $name): int
    {
        return (int) DB::table('permissions')
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->value('id');
    }

    private function createOrganizationUnit(int $tenantId, string $name, string $code): int
    {
        return (int) DB::table('organization_units')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => $name,
            'code' => $code,
            'path' => '/'.strtolower($code),
            'is_active' => true,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUser(int $tenantId, ?int $organizationUnitId, string $email, array $overrides = []): int
    {
        return (int) DB::table('users')->insertGetId([
            'tenant_id' => $tenantId,
            'first_name' => $overrides['first_name'] ?? 'Test',
            'last_name' => $overrides['last_name'] ?? 'User',
            'username' => $overrides['username'] ?? null,
            'email' => $email,
            'password' => app(PasswordHasherInterface::class)->hash('secret-password'),
            'status' => $overrides['status'] ?? UserStatus::ACTIVE,
            'row_version' => $overrides['row_version'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createRole(int $tenantId, string $name): int
    {
        return (int) DB::table('roles')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'name' => $name,
            'guard_name' => 'web',
            'description' => $name.' role',
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assignPermission(int $tenantId, int $roleId, int $permissionId): void
    {
        DB::table('role_permissions')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assignRole(int $tenantId, int $userId, int $roleId, ?int $organizationUnitId): void
    {
        DB::table('user_roles')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assignOrganizationUnit(int $tenantId, int $userId, int $organizationUnitId, bool $default): void
    {
        DB::table('user_organization_units')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
            'status' => 'active',
            'is_default' => $default,
            'default_marker' => $default ? 'default' : null,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function withAuth(array $context): self
    {
        return $this->withToken($context['token'])->withHeaders([
            'X-Tenant-Id' => (string) $context['tenant_id'],
            'X-Organization-Unit-Id' => (string) $context['organization_unit_id'],
        ]);
    }
}
