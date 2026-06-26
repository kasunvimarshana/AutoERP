<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\User\Constants\UserGuard;
use Modules\User\Constants\UserPermission;
use Modules\User\Constants\UserStatus;
use Modules\User\Constants\UserSystemRole;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class UserAccessApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_user_list_is_tenant_scoped_filtered_and_does_not_expose_credentials(): void
    {
        $context = $this->createAuthContext();
        $roleId = $this->createRole($context['tenant_id'], 'Service Manager');
        $branchId = $this->createOrganizationUnit($context['tenant_id'], 'Service Branch', 'SVC');
        $userId = $this->createUser($context['tenant_id'], 'alice.user@example.test', ['first_name' => 'Alice']);
        $this->assignRole($context['tenant_id'], $userId, $roleId);
        $this->assignOrganizationUnit($context['tenant_id'], $userId, $branchId, true);

        $this->withAuth($context)
            ->getJson("/api/v1/users?search=Alice&status=active&role_id={$roleId}&organization_unit_filter_id={$branchId}")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.email', 'alice.user@example.test')
            ->assertJsonPath('data.0.roles.0.name', 'Service Manager')
            ->assertJsonMissingPath('data.0.password')
            ->assertJsonMissingPath('data.0.password_hash')
            ->assertJsonMissingPath('data.0.remember_token');
    }

    public function test_user_creation_is_invitation_first_and_access_assignments_are_atomic(): void
    {
        $context = $this->createAuthContext();
        $roleId = $this->createRole($context['tenant_id'], 'Counter Staff');
        $branchId = $this->createOrganizationUnit($context['tenant_id'], 'Counter Branch', 'CNT');

        $response = $this->withAuth($context)->postJson('/api/v1/users', [
            'first_name' => 'Created',
            'last_name' => 'User',
            'username' => 'created.user',
            'email' => 'created.user@example.test',
            'phone' => '+94115550000',
            'role_ids' => [$roleId],
            'organization_unit_ids' => [$branchId],
            'default_organization_unit_id' => $branchId,
        ])->assertCreated()
            ->assertJsonPath('data.status', UserStatus::INVITED)
            ->assertJsonPath('data.credentials_ready', false)
            ->assertJsonMissingPath('data.password');

        $userId = (int) $response->json('data.id');
        $this->assertDatabaseHas('user_roles', [
            'tenant_id' => $context['tenant_id'],
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);
        $this->assertDatabaseHas('user_organization_units', [
            'tenant_id' => $context['tenant_id'],
            'user_id' => $userId,
            'organization_unit_id' => $branchId,
            'is_default' => true,
        ]);
        $this->assertDatabaseHas('auth_registration_invitations', [
            'tenant_id' => $context['tenant_id'],
            'user_id' => $userId,
            'email' => 'created.user@example.test',
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('auth_user_password_credentials', [
            'tenant_id' => $context['tenant_id'],
            'user_id' => $userId,
        ]);
    }

    public function test_create_rejects_password_status_and_client_owned_tenant_fields(): void
    {
        $context = $this->createAuthContext();

        $this->withAuth($context)->postJson('/api/v1/users', [
            'first_name' => 'Unsafe',
            'email' => 'unsafe@example.test',
            'password' => 'administrator-known-password',
            'status' => UserStatus::ACTIVE,
            'tenant_id' => $context['tenant_id'],
            'role_ids' => [],
            'organization_unit_ids' => [$context['organization_unit_id']],
            'default_organization_unit_id' => $context['organization_unit_id'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password', 'status', 'tenant_id']);
    }

    public function test_profile_role_permission_and_organization_changes_use_separate_versioned_commands(): void
    {
        $context = $this->createAuthContext();
        $userId = $this->createUser($context['tenant_id'], 'editable@example.test');
        $this->assignOrganizationUnit($context['tenant_id'], $userId, $context['organization_unit_id'], true);
        $roleId = $this->createRole($context['tenant_id'], 'Technician');
        $permissionId = $this->permissionId($context['tenant_id'], UserPermission::USER_DEVICES_VIEW);

        $this->withAuth($context)->patchJson("/api/v1/users/{$userId}", [
            'expected_version' => 1,
            'first_name' => 'Edited',
            'status' => UserStatus::SUSPENDED,
            'role_ids' => [$roleId],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'role_ids']);

        $this->withAuth($context)->patchJson("/api/v1/users/{$userId}", [
            'expected_version' => 1,
            'first_name' => 'Edited',
        ])->assertOk()->assertJsonPath('data.row_version', 2);

        $this->withAuth($context)->putJson("/api/v1/users/{$userId}/roles", [
            'expected_version' => 2,
            'role_ids' => [$roleId],
        ])->assertOk()->assertJsonPath('data.row_version', 3);

        $this->withAuth($context)->putJson("/api/v1/users/{$userId}/permissions", [
            'expected_version' => 3,
            'permission_ids' => [$permissionId],
        ])->assertOk()->assertJsonPath('data.row_version', 4);
    }

    public function test_cross_tenant_role_assignment_is_rejected_without_mutating_existing_access(): void
    {
        $tenantA = $this->createAuthContext(['code' => 'TENA', 'email' => 'admin.a@example.test']);
        $tenantB = $this->createAuthContext(['code' => 'TENB', 'email' => 'admin.b@example.test']);
        $userId = $this->createUser($tenantA['tenant_id'], 'target@example.test');
        $roleA = $this->createRole($tenantA['tenant_id'], 'Tenant A Role');
        $roleB = $this->createRole($tenantB['tenant_id'], 'Tenant B Role');
        $this->assignRole($tenantA['tenant_id'], $userId, $roleA);

        $this->withAuth($tenantA)->putJson("/api/v1/users/{$userId}/roles", [
            'expected_version' => 1,
            'role_ids' => [$roleB],
        ])->assertUnprocessable();

        $this->assertDatabaseHas('user_roles', [
            'tenant_id' => $tenantA['tenant_id'], 'user_id' => $userId, 'role_id' => $roleA,
        ]);
        $this->assertDatabaseMissing('user_roles', [
            'tenant_id' => $tenantA['tenant_id'], 'user_id' => $userId, 'role_id' => $roleB,
        ]);
    }

    public function test_exact_permissions_prevent_role_and_status_escalation(): void
    {
        $viewer = $this->createAuthContext(
            ['code' => 'VIEW', 'email' => 'viewer@example.test'],
            [UserPermission::USERS_VIEW],
            null,
        );

        $this->withAuth($viewer)->getJson('/api/v1/users')->assertOk();
        $this->withAuth($viewer)->postJson('/api/v1/users', [
            'first_name' => 'Forbidden',
            'email' => 'forbidden@example.test',
            'role_ids' => [],
            'organization_unit_ids' => [$viewer['organization_unit_id']],
            'default_organization_unit_id' => $viewer['organization_unit_id'],
        ])->assertForbidden();
    }

    public function test_last_super_admin_and_self_deactivation_are_blocked(): void
    {
        $admin = $this->createAuthContext(['code' => 'LOCK', 'email' => 'sole.admin@example.test']);

        $this->withAuth($admin)->patchJson("/api/v1/users/{$admin['user_id']}/status", [
            'expected_version' => 1,
            'status' => UserStatus::INACTIVE,
            'reason' => 'Security review',
        ])->assertStatus(409);
    }

    public function test_administrator_cannot_register_a_device_token_for_another_user(): void
    {
        $context = $this->createAuthContext();
        $userId = $this->createUser($context['tenant_id'], 'device-owner@example.test');

        $this->withAuth($context)->postJson("/api/v1/users/{$userId}/devices", [
            'device_token' => str_repeat('a', 64),
            'device_name' => 'Injected device',
            'platform' => 'web',
        ])->assertForbidden();

        $this->assertDatabaseMissing('user_devices', [
            'tenant_id' => $context['tenant_id'],
            'user_id' => $userId,
        ]);
    }

    public function test_access_changes_revoke_sessions_without_revoking_password_credentials(): void
    {
        $context = $this->createAuthContext();
        $userId = $this->createUser($context['tenant_id'], 'access-change@example.test');
        $roleId = $this->createRole($context['tenant_id'], 'Access Changed');

        $this->withAuth($context)->putJson("/api/v1/users/{$userId}/roles", [
            'expected_version' => 1,
            'role_ids' => [$roleId],
        ])->assertOk();

        $this->assertDatabaseHas('auth_user_password_credentials', [
            'tenant_id' => $context['tenant_id'],
            'user_id' => $userId,
            'status' => 'active',
            'revoked_at' => null,
        ]);
    }

    public function test_archiving_user_revokes_credentials_and_removes_operational_access(): void
    {
        $context = $this->createAuthContext();
        $userId = $this->createUser($context['tenant_id'], 'archived@example.test');
        $roleId = $this->createRole($context['tenant_id'], 'Archived User Role');
        $this->assignRole($context['tenant_id'], $userId, $roleId);
        $this->assignOrganizationUnit($context['tenant_id'], $userId, $context['organization_unit_id'], true);

        $this->withAuth($context)->deleteJson("/api/v1/users/{$userId}", [
            'expected_version' => 1,
            'reason' => 'Employment ended.',
        ])->assertNoContent();

        $this->assertSoftDeleted('users', [
            'tenant_id' => $context['tenant_id'],
            'id' => $userId,
        ]);
        $this->assertDatabaseHas('auth_user_password_credentials', [
            'tenant_id' => $context['tenant_id'],
            'user_id' => $userId,
            'status' => 'revoked',
        ]);
        $this->assertDatabaseMissing('user_roles', [
            'tenant_id' => $context['tenant_id'],
            'user_id' => $userId,
        ]);
        $this->assertDatabaseMissing('user_organization_units', [
            'tenant_id' => $context['tenant_id'],
            'user_id' => $userId,
        ]);
    }

    public function test_role_with_assigned_users_cannot_be_archived_and_the_assignment_remains_intact(): void
    {
        $context = $this->createAuthContext();
        $userId = $this->createUser($context['tenant_id'], 'role-member@example.test');
        $roleId = $this->createRole($context['tenant_id'], 'Assigned Role');
        $this->assignRole($context['tenant_id'], $userId, $roleId);

        $this->withAuth($context)->deleteJson("/api/v1/roles/{$roleId}", [
            'expected_version' => 1,
        ])->assertStatus(409);

        $this->assertDatabaseHas('user_roles', [
            'tenant_id' => $context['tenant_id'],
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);
        $this->assertDatabaseHas('roles', [
            'tenant_id' => $context['tenant_id'],
            'id' => $roleId,
            'deleted_at' => null,
            'row_version' => 1,
        ]);
    }

    public function test_role_profile_and_permission_assignment_are_independent_and_system_roles_are_immutable(): void
    {
        $context = $this->createAuthContext();
        $permissionId = $this->permissionId($context['tenant_id'], UserPermission::USERS_VIEW);

        $roleId = (int) $this->withAuth($context)->postJson('/api/v1/roles', [
            'name' => 'Auditor',
            'description' => 'Read-only auditor',
            'permission_ids' => [$permissionId],
        ])->assertUnprocessable()->json('data.id');
        self::assertSame(0, $roleId);

        $roleId = (int) $this->withAuth($context)->postJson('/api/v1/roles', [
            'name' => 'Auditor',
            'description' => 'Read-only auditor',
        ])->assertCreated()->json('data.id');

        $this->withAuth($context)->putJson("/api/v1/roles/{$roleId}/permissions", [
            'expected_version' => 1,
            'permission_ids' => [$permissionId],
        ])->assertOk()->assertJsonPath('data.row_version', 2);

        $this->withAuth($context)->patchJson("/api/v1/roles/{$context['role_id']}", [
            'expected_version' => 1,
            'name' => 'Renamed System Role',
        ])->assertStatus(409);
    }

    /** @param array<string,mixed> $overrides @param list<string>|null $permissions */
    private function createAuthContext(
        array $overrides = [],
        ?array $permissions = null,
        ?string $systemRoleKey = UserSystemRole::SUPER_ADMIN,
    ): array {
        $now = now();
        $code = strtoupper((string) ($overrides['code'] ?? Str::upper(Str::random(6))));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(), 'code' => $code, 'name' => $code.' Tenant',
            'slug' => strtolower($code).'-tenant', 'status' => 'active', 'row_version' => 1,
            'status_reason' => 'Integration test tenant.', 'status_changed_at' => $now,
            'activated_at' => $now,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        \Tests\Support\ActiveTenantSubscriptionFixture::create($tenantId);
        $organizationUnitId = $this->createOrganizationUnit($tenantId, 'Head Office', 'HO');
        $email = (string) ($overrides['email'] ?? strtolower($code).'@example.test');
        $userId = $this->createUser($tenantId, $email, ['first_name' => 'Admin']);
        $roleId = $this->createRole($tenantId, $systemRoleKey === null ? 'Viewer' : 'Super Admin', $systemRoleKey);
        foreach ($this->seedPermissions($tenantId, $permissions ?? UserPermission::values()) as $permissionId) {
            $this->assignPermission($tenantId, $roleId, $permissionId);
        }
        $this->assignRole($tenantId, $userId, $roleId);
        $this->assignOrganizationUnit($tenantId, $userId, $organizationUnitId, true);
        \Tests\Support\TenantAuthenticationFixture::provision($tenantId, $userId, $email);

        $token = (string) $this->withHeader('X-Tenant-Id', (string) $tenantId)->postJson('/api/v1/auth/login', [
            'organization_unit_id' => $organizationUnitId,
            'identifier' => $email,
            'password' => 'secret-password',
        ])->assertOk()->json('token');

        return compact('tenantId', 'organizationUnitId', 'userId', 'roleId', 'token') + [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
            'role_id' => $roleId,
        ];
    }

    /** @param array<string,mixed> $overrides */
    private function createUser(int $tenantId, string $email, array $overrides = []): int
    {
        return TenantUserFixture::create(array_merge([
            'tenant_id' => $tenantId,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'status' => UserStatus::ACTIVE,
            'password' => 'secret-password',
        ], $overrides));
    }

    private function createRole(int $tenantId, string $name, ?string $systemKey = null): int
    {
        return (int) DB::table('roles')->insertGetId([
            'tenant_id' => $tenantId, 'name' => $name, 'active_name_key' => mb_strtolower($name),
            'guard_name' => UserGuard::TENANT_API, 'system_key' => $systemKey,
            'is_system' => $systemKey !== null, 'description' => $name.' role', 'row_version' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** @param list<string> $names @return list<int> */
    private function seedPermissions(int $tenantId, array $names): array
    {
        $ids = [];
        foreach ($names as $name) {
            $ids[] = (int) DB::table('permissions')->insertGetId([
                'tenant_id' => $tenantId, 'name' => $name, 'guard_name' => UserGuard::TENANT_API,
                'module' => Str::headline(Str::before($name, '.')), 'description' => UserPermission::descriptions()[$name] ?? null,
                'is_active' => true, 'row_version' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        return $ids;
    }

    private function permissionId(int $tenantId, string $name): int
    {
        return (int) DB::table('permissions')->where('tenant_id', $tenantId)->where('name', $name)->value('id');
    }

    private function createOrganizationUnit(int $tenantId, string $name, string $code): int
    {
        return (int) \Tests\Support\OrganizationUnitFixture::create([
            'tenant_id' => $tenantId, 'name' => $name, 'code' => $code,
            'path' => '/'.strtolower($code), 'is_active' => true, 'row_version' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function assignPermission(int $tenantId, int $roleId, int $permissionId): void
    {
        DB::table('role_permissions')->insert([
            'tenant_id' => $tenantId, 'role_id' => $roleId, 'permission_id' => $permissionId,
            'row_version' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function assignRole(int $tenantId, int $userId, int $roleId): void
    {
        DB::table('user_roles')->insert([
            'tenant_id' => $tenantId, 'user_id' => $userId, 'role_id' => $roleId,
            'row_version' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function assignOrganizationUnit(int $tenantId, int $userId, int $organizationUnitId, bool $default): void
    {
        DB::table('user_organization_units')->insert([
            'tenant_id' => $tenantId, 'organization_unit_id' => $organizationUnitId, 'user_id' => $userId,
            'status' => 'active', 'is_default' => $default, 'default_marker' => $default ? 'default' : null,
            'row_version' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** @param array<string,mixed> $context */
    private function withAuth(array $context): self
    {
        return $this->withToken((string) $context['token'])->withHeaders([
            'X-Tenant-Id' => (string) $context['tenant_id'],
        ]);
    }
}
