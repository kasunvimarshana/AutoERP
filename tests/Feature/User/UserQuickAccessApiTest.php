<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\User\Constants\UserGuard;
use Modules\User\Constants\UserPermission;
use Modules\User\Constants\UserStatus;
use Modules\User\Constants\UserSystemRole;
use Modules\User\Services\UserQuickAccessService;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class UserQuickAccessApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_quick_access_returns_sensible_defaults_when_no_record_exists(): void
    {
        $context = $this->createAuthContext();

        $response = $this->withAuth($context)
            ->getJson('/api/v1/user/quick-access')
            ->assertOk()
            ->assertJsonPath('data.is_default', true)
            ->assertJsonPath('data.row_version', null);

        $items = $response->json('data.items');
        self::assertIsArray($items);
        self::assertCount(4, $items);
        self::assertSame('create-service-job', $items[0]['id']);
        self::assertSame('/vehicle-service/jobs/create', $items[0]['to']);
    }

    public function test_put_quick_access_saves_preferences_and_increments_row_version(): void
    {
        $context = $this->createAuthContext();

        $customItems = [
            [
                'id' => 'custom-1',
                'label' => 'Customers',
                'to' => '/customers',
                'group' => 'Sales',
                'icon' => 'customer',
                'enabled' => true,
                'order' => 1,
            ],
            [
                'id' => 'custom-2',
                'label' => 'Invoices',
                'to' => '/invoices?view=service',
                'group' => 'Billing',
                'icon' => 'invoice',
                'enabled' => false,
                'order' => 2,
            ],
        ];

        $saveResponse = $this->withAuth($context)
            ->putJson('/api/v1/user/quick-access', [
                'items' => $customItems,
            ])
            ->assertOk()
            ->assertJsonPath('data.is_default', false)
            ->assertJsonPath('data.row_version', 1)
            ->assertJsonPath('data.items.0.label', 'Customers')
            ->assertJsonPath('data.items.1.to', '/invoices?view=service');

        $this->assertDatabaseHas('user_quick_access_settings', [
            'tenant_id' => $context['tenant_id'],
            'user_id' => $context['user_id'],
            'row_version' => 1,
        ]);

        // Subsequent GET returns the persisted items
        $this->withAuth($context)
            ->getJson('/api/v1/user/quick-access')
            ->assertOk()
            ->assertJsonPath('data.is_default', false)
            ->assertJsonPath('data.row_version', 1)
            ->assertJsonPath('data.items.0.id', 'custom-1')
            ->assertJsonPath('data.items.1.enabled', false);
    }

    public function test_concurrency_conflict_returns_409_when_row_version_is_stale(): void
    {
        $context = $this->createAuthContext();

        // Initial save -> row_version = 1
        $this->withAuth($context)
            ->putJson('/api/v1/user/quick-access', [
                'items' => [
                    [
                        'id' => 'first',
                        'label' => 'First',
                        'to' => '/selling',
                        'group' => 'General',
                        'icon' => 'list',
                        'enabled' => true,
                        'order' => 1,
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.row_version', 1);

        // Update with stale row_version 99 fails with 409
        $this->withAuth($context)
            ->putJson('/api/v1/user/quick-access', [
                'items' => [
                    [
                        'id' => 'stale',
                        'label' => 'Stale update',
                        'to' => '/selling',
                        'group' => 'General',
                        'icon' => 'list',
                        'enabled' => true,
                        'order' => 1,
                    ],
                ],
                'row_version' => 99,
            ])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'USER_STALE_RECORD');

        // Update existing record with row_version null (acting as if default) also fails with 409
        $this->withAuth($context)
            ->putJson('/api/v1/user/quick-access', [
                'items' => [
                    [
                        'id' => 'stale-null',
                        'label' => 'Stale null version',
                        'to' => '/selling',
                        'group' => 'General',
                        'icon' => 'list',
                        'enabled' => true,
                        'order' => 1,
                    ],
                ],
            ])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'USER_STALE_RECORD');

        // Update with matching row_version 1 succeeds and increments to 2
        $this->withAuth($context)
            ->putJson('/api/v1/user/quick-access', [
                'items' => [
                    [
                        'id' => 'valid',
                        'label' => 'Valid update',
                        'to' => '/selling',
                        'group' => 'General',
                        'icon' => 'list',
                        'enabled' => true,
                        'order' => 1,
                    ],
                ],
                'row_version' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('data.row_version', 2);
    }

    public function test_delete_resets_preferences_to_defaults(): void
    {
        $context = $this->createAuthContext();

        // Save custom
        $this->withAuth($context)
            ->putJson('/api/v1/user/quick-access', [
                'items' => [
                    [
                        'id' => 'custom',
                        'label' => 'Custom Item',
                        'to' => '/custom',
                        'group' => 'Custom',
                        'icon' => 'list',
                        'enabled' => true,
                        'order' => 1,
                    ],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('user_quick_access_settings', [
            'tenant_id' => $context['tenant_id'],
            'user_id' => $context['user_id'],
        ]);

        // Delete / Reset
        $this->withAuth($context)
            ->deleteJson('/api/v1/user/quick-access')
            ->assertOk()
            ->assertJsonPath('data.is_default', true)
            ->assertJsonPath('data.row_version', null);

        $this->assertDatabaseMissing('user_quick_access_settings', [
            'tenant_id' => $context['tenant_id'],
            'user_id' => $context['user_id'],
        ]);
    }

    public function test_tenant_and_user_isolation_are_strictly_enforced(): void
    {
        $contextA = $this->createAuthContext();
        $contextB = $this->createAuthContext(); // separate tenant

        // User A saves custom items
        $this->withAuth($contextA)
            ->putJson('/api/v1/user/quick-access', [
                'items' => [
                    [
                        'id' => 'item-tenant-a',
                        'label' => 'Tenant A Item',
                        'to' => '/tenanta',
                        'group' => 'A',
                        'icon' => 'list',
                        'enabled' => true,
                        'order' => 1,
                    ],
                ],
            ])
            ->assertOk();

        // User B in Tenant B sees default items, NOT Tenant A's items
        $responseB = $this->withAuth($contextB)
            ->getJson('/api/v1/user/quick-access')
            ->assertOk()
            ->assertJsonPath('data.is_default', true);

        self::assertNotSame('item-tenant-a', $responseB->json('data.items.0.id'));

        // Another user in the same tenant also has independent preferences
        $user2Id = $this->createUser($contextA['tenant_id'], 'user2@example.test', ['first_name' => 'User2']);
        $this->assignRole($contextA['tenant_id'], $user2Id, $contextA['role_id']);
        $this->assignOrganizationUnit($contextA['tenant_id'], $user2Id, $contextA['organization_unit_id'], true);
        
        $providerId = (int) DB::table('auth_providers')->where('tenant_id', $contextA['tenant_id'])->where('provider_key', 'internal')->value('id');
        DB::table('auth_identities')->insert([
            'tenant_id' => $contextA['tenant_id'],
            'provider_id' => $providerId,
            'user_id' => $user2Id,
            'provider_user_key' => 'user2@example.test',
            'status' => 'active',
            'primary_marker' => 'primary',
            'verified_at' => now(),
            'last_authenticated_at' => null,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tokenUser2 = (string) $this->withHeader('X-Tenant-Id', (string) $contextA['tenant_id'])->postJson('/api/v1/auth/login', [
            'organization_unit_id' => $contextA['organization_unit_id'],
            'identifier' => 'user2@example.test',
            'password' => 'secret-password',
        ])->assertOk()->json('token');

        $contextAUser2 = [
            'tenant_id' => $contextA['tenant_id'],
            'user_id' => $user2Id,
            'token' => $tokenUser2,
        ];

        // User 2 in Tenant A sees defaults initially
        $this->withAuth($contextAUser2)
            ->getJson('/api/v1/user/quick-access')
            ->assertOk()
            ->assertJsonPath('data.is_default', true);
    }

    public function test_validation_rejects_invalid_routes_and_prohibited_fields(): void
    {
        $context = $this->createAuthContext();

        // Route must start with a single forward slash /
        $this->withAuth($context)
            ->putJson('/api/v1/user/quick-access', [
                'items' => [
                    [
                        'id' => 'inv',
                        'label' => 'Invalid Route',
                        'to' => 'not-starting-with-slash',
                        'group' => 'General',
                        'icon' => 'list',
                        'enabled' => true,
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.to']);

        // Protocol-relative paths (//evil.com) must be rejected
        $this->withAuth($context)
            ->putJson('/api/v1/user/quick-access', [
                'items' => [
                    [
                        'id' => 'evil-protocol',
                        'label' => 'Evil Protocol',
                        'to' => '//evil.com',
                        'group' => 'General',
                        'icon' => 'list',
                        'enabled' => true,
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.to']);

        // Backslash paths (/\\evil.com) must be rejected
        $this->withAuth($context)
            ->putJson('/api/v1/user/quick-access', [
                'items' => [
                    [
                        'id' => 'evil-slash',
                        'label' => 'Evil Backslash',
                        'to' => '/\\evil.com',
                        'group' => 'General',
                        'icon' => 'list',
                        'enabled' => true,
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.to']);

        // Prohibited tenant_id and user_id in body
        $this->withAuth($context)
            ->putJson('/api/v1/user/quick-access', [
                'items' => [],
                'tenant_id' => 999,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tenant_id']);
    }

    public function test_empty_shortcuts_list_is_saved_and_preserved(): void
    {
        $context = $this->createAuthContext();

        // Save empty list
        $this->withAuth($context)
            ->putJson('/api/v1/user/quick-access', [
                'items' => [],
            ])
            ->assertOk()
            ->assertJsonPath('data.is_default', false)
            ->assertJsonPath('data.row_version', 1)
            ->assertJsonPath('data.items', []);

        // Subsequent GET returns empty list, NOT defaults
        $this->withAuth($context)
            ->getJson('/api/v1/user/quick-access')
            ->assertOk()
            ->assertJsonPath('data.is_default', false)
            ->assertJsonPath('data.row_version', 1)
            ->assertJsonPath('data.items', []);
    }

    public function test_duplicate_routes_are_deduplicated_by_service(): void
    {
        $context = $this->createAuthContext();

        $response = $this->withAuth($context)
            ->putJson('/api/v1/user/quick-access', [
                'items' => [
                    [
                        'id' => 'first',
                        'label' => 'Customers',
                        'to' => '/customers',
                        'group' => 'CRM',
                        'icon' => 'customer',
                        'enabled' => true,
                    ],
                    [
                        'id' => 'second',
                        'label' => 'Duplicate Customers',
                        'to' => '/customers/',
                        'group' => 'CRM',
                        'icon' => 'customer',
                        'enabled' => true,
                    ],
                ],
            ])
            ->assertOk();

        $items = $response->json('data.items');
        self::assertCount(1, $items);
        self::assertSame('first', $items[0]['id']);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/v1/user/quick-access')->assertUnauthorized();
        $this->putJson('/api/v1/user/quick-access', ['items' => []])->assertUnauthorized();
        $this->deleteJson('/api/v1/user/quick-access')->assertUnauthorized();
    }

    /** @return array{tenant_id: int, organization_unit_id: int, userId: int, user_id: int, roleId: int, role_id: int, token: string} */
    private function createAuthContext(array $overrides = []): array
    {
        $now = now();
        $code = strtoupper((string) ($overrides['code'] ?? Str::upper(Str::random(6))));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => $code.' Tenant',
            'slug' => strtolower($code).'-tenant',
            'status' => 'active',
            'row_version' => 1,
            'status_reason' => 'Integration test tenant.',
            'status_changed_at' => $now,
            'activated_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        \Tests\Support\ActiveTenantSubscriptionFixture::create($tenantId);

        $organizationUnitId = $this->createOrganizationUnit($tenantId, 'Head Office', 'HO');
        $email = (string) ($overrides['email'] ?? 'user.'.Str::lower(Str::random(6)).'@example.test');
        $userId = $this->createUser($tenantId, $email, ['first_name' => 'Admin']);
        $roleId = $this->createRole($tenantId, 'Super Admin', UserSystemRole::SUPER_ADMIN);
        foreach ($this->seedPermissions($tenantId, UserPermission::values()) as $permissionId) {
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

        return [
            'tenantId' => $tenantId,
            'organizationUnitId' => $organizationUnitId,
            'userId' => $userId,
            'roleId' => $roleId,
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'token' => $token,
        ];
    }

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

    private function withAuth(array $context): self
    {
        return $this->withToken((string) $context['token'])->withHeaders([
            'X-Tenant-Id' => (string) $context['tenant_id'],
        ]);
    }
}
