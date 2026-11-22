<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Tests\Feature\Sequence;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Sequence\Constants\SequencePermission;
use Modules\User\Constants\UserGuard;
use Modules\User\Constants\UserStatus;
use Modules\User\Constants\UserSystemRole;
use Tests\TestCase;

final class SequenceTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cross_tenant_sequence_access_is_rejected(): void
    {
        $contextA = $this->createAuthContext(['code' => 'SQA', 'email' => 'admin.sqa@example.test']);
        $contextB = $this->createAuthContext(['code' => 'SQB', 'email' => 'admin.sqb@example.test']);

        // Tenant A creates a sequence
        $createResponse = $this->withAuth($contextA)->postJson('/api/v1/sequence/sequences', [
            'tenant_id' => $contextA['tenant_id'],
            'organization_unit_id' => $contextA['organization_unit_id'],
            'document_type' => 'invoice',
            'prefix' => 'INV-',
            'padding' => 6,
            'next_number' => 1,
            'period_type' => 'yearly',
        ])->assertCreated();

        $sequenceId = (int) $createResponse->json('data.id');

        // Tenant B cannot view Tenant A's sequence
        $this->withAuth($contextB)
            ->getJson("/api/v1/sequence/sequences/{$sequenceId}")
            ->assertNotFound();

        // Tenant B cannot update Tenant A's sequence
        $this->withAuth($contextB)
            ->matchJson(['put'], "/api/v1/sequence/sequences/{$sequenceId}", [
            'tenant_id' => $contextB['tenant_id'],
            'organization_unit_id' => $contextB['organization_unit_id'],
            'document_type' => 'invoice',
            'prefix' => 'HACKED-',
            'padding' => 6,
            'next_number' => 1,
            'period_type' => 'yearly',
        ])->assertNotFound();

        // Tenant B cannot delete Tenant A's sequence
        $this->withAuth($contextB)
            ->deleteJson("/api/v1/sequence/sequences/{$sequenceId}")
            ->assertNotFound();

        // Tenant A can view its own sequence
        $this->withAuth($contextA)
            ->getJson("/api/v1/sequence/sequences/{$sequenceId}")
            ->assertOk()
            ->assertJsonPath('data.prefix', 'INV-');
    }

    public function test_sequence_cannot_be_deleted_if_numbers_already_issued(): void
    {
        $context = $this->createAuthContext();

        $createResponse = $this->withAuth($context)->postJson('/api/v1/sequence/sequences', [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'document_type' => 'sale_receipt',
            'prefix' => 'RCP-',
            'padding' => 5,
            'next_number' => 10, // Already issued 9 numbers
            'period_type' => 'yearly',
        ])->assertCreated();

        $sequenceId = (int) $createResponse->json('data.id');

        // Deleting should fail with 409 Conflict
        $this->withAuth($context)
            ->deleteJson("/api/v1/sequence/sequences/{$sequenceId}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Sequences that have already generated document numbers cannot be deleted.');
    }

    public function test_sequence_can_be_deleted_if_unused(): void
    {
        $context = $this->createAuthContext();

        $createResponse = $this->withAuth($context)->postJson('/api/v1/sequence/sequences', [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'document_type' => 'quotation',
            'prefix' => 'QTN-',
            'padding' => 5,
            'next_number' => 1,
            'period_type' => 'yearly',
        ])->assertCreated();

        $sequenceId = (int) $createResponse->json('data.id');

        $this->withAuth($context)
            ->deleteJson("/api/v1/sequence/sequences/{$sequenceId}")
            ->assertNoContent();
    }

    public function test_sequence_update_verifies_optimistic_concurrency(): void
    {
        $context = $this->createAuthContext();

        $createResponse = $this->withAuth($context)->postJson('/api/v1/sequence/sequences', [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'document_type' => 'delivery_order',
            'prefix' => 'DO-',
            'padding' => 5,
            'next_number' => 1,
            'period_type' => 'yearly',
        ])->assertCreated();

        $sequenceId = (int) $createResponse->json('data.id');

        // Mismatched expected_version should fail with 409
        $this->withAuth($context)->matchJson(['put'], "/api/v1/sequence/sequences/{$sequenceId}", [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'document_type' => 'delivery_order',
            'prefix' => 'DO-UPDATED-',
            'padding' => 5,
            'next_number' => 1,
            'period_type' => 'yearly',
            'expected_version' => 999,
        ])->assertStatus(409);

        // Correct expected_version succeeds and bumps row_version
        $this->withAuth($context)->matchJson(['put'], "/api/v1/sequence/sequences/{$sequenceId}", [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'document_type' => 'delivery_order',
            'prefix' => 'DO-UPDATED-',
            'padding' => 5,
            'next_number' => 1,
            'period_type' => 'yearly',
            'expected_version' => 1,
        ])->assertOk()->assertJsonPath('data.prefix', 'DO-UPDATED-');
    }

    /** @param array<string,mixed> $overrides */
    private function createAuthContext(array $overrides = []): array
    {
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
        $organizationUnitId = (int) \Tests\Support\OrganizationUnitFixture::create([
            'tenant_id' => $tenantId, 'name' => 'Main Branch', 'code' => 'MB-'.$code,
            'path' => '/mb-'.strtolower($code), 'is_active' => true, 'row_version' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $email = (string) ($overrides['email'] ?? strtolower($code).'@example.test');
        $userId = \Tests\Support\TenantUserFixture::create([
            'tenant_id' => $tenantId, 'first_name' => 'Admin', 'email' => $email,
            'status' => UserStatus::ACTIVE, 'password' => 'secret-password',
        ]);
        $roleId = (int) DB::table('roles')->insertGetId([
            'tenant_id' => $tenantId, 'name' => 'Super Admin', 'active_name_key' => 'super admin',
            'guard_name' => UserGuard::TENANT_API, 'system_key' => UserSystemRole::SUPER_ADMIN,
            'is_system' => true, 'description' => 'Super admin role', 'row_version' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        foreach (SequencePermission::descriptions() as $permission => $desc) {
            $permId = (int) DB::table('permissions')->insertGetId([
                'tenant_id' => $tenantId, 'name' => $permission, 'guard_name' => UserGuard::TENANT_API,
                'module' => 'Sequence', 'description' => $desc, 'is_active' => true,
                'row_version' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('role_permissions')->insert([
                'tenant_id' => $tenantId, 'role_id' => $roleId, 'permission_id' => $permId,
                'row_version' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        DB::table('user_roles')->insert([
            'tenant_id' => $tenantId, 'user_id' => $userId, 'role_id' => $roleId,
            'row_version' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('user_organization_units')->insert([
            'tenant_id' => $tenantId, 'organization_unit_id' => $organizationUnitId, 'user_id' => $userId,
            'status' => 'active', 'is_default' => true, 'default_marker' => 'default',
            'row_version' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
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
        ];
    }

    /** @param array<string,mixed> $context */
    private function withAuth(array $context): self
    {
        return $this->withToken((string) $context['token'])->withHeaders([
            'X-Tenant-Id' => (string) $context['tenant_id'],
        ]);
    }

    /** @param list<string> $methods @param array<string,mixed> $data */
    private function matchJson(array $methods, string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->putJson($uri, $data);
    }
}
