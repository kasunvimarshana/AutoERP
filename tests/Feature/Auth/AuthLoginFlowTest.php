<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Auth\Services\Credentials\PasswordCredentialService;
use Modules\Tenant\Constants\TenantStatus;
use Modules\User\Constants\PlatformOperatorStatus;
use Modules\User\Constants\UserOrganizationUnitStatus;
use Tests\Support\ActiveTenantSubscriptionFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantAuthenticationFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class AuthLoginFlowTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Valid-Test-Password-2026!';

    public function test_tenant_login_returns_complete_session_and_supports_me_and_logout(): void
    {
        $tenantId = $this->createActiveTenant('AUTH-TENANT');
        ActiveTenantSubscriptionFixture::create($tenantId);
        $organizationUnitId = OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Main organization',
            'code' => 'MAIN',
        ]);
        $email = 'tenant-auth@example.test';
        $userId = TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'Tenant',
            'last_name' => 'Administrator',
            'email' => $email,
            'password' => self::PASSWORD,
            'status' => 'active',
        ]);
        $now = now();
        DB::table('user_organization_units')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
            'status' => UserOrganizationUnitStatus::ACTIVE,
            'is_default' => true,
            'default_marker' => UserOrganizationUnitStatus::DEFAULT_MARKER,
            'row_version' => 1,
            'created_by_user_id' => null,
            'updated_by_user_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        TenantAuthenticationFixture::provision($tenantId, $userId, $email);

        $login = $this->withHeader('X-Tenant-Id', (string) $tenantId)
            ->postJson('/api/v1/auth/login', [
                'identifier' => $email,
                'password' => self::PASSWORD,
                'organization_unit_id' => $organizationUnitId,
                'device_name' => 'PHPUnit tenant client',
            ])
            ->assertOk()
            ->assertJsonPath('user.email', $email)
            ->assertJsonPath('tenant.id', $tenantId)
            ->assertJsonPath('organization_unit.id', $organizationUnitId)
            ->assertJsonPath('is_platform_operator', false);

        $token = trim((string) $login->json('token'));
        self::assertNotSame('', $token);
        $this->assertDatabaseHas('auth_sessions', [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'organization_unit_id' => $organizationUnitId,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('auth_login_attempts', [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'was_successful' => true,
        ]);

        $this->withToken($token)
            ->withHeader('X-Tenant-Id', (string) $tenantId)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', $email);

        $this->withToken($token)
            ->withHeader('X-Tenant-Id', (string) $tenantId)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseHas('auth_sessions', [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'status' => 'revoked',
        ]);
    }

    public function test_platform_login_returns_complete_session_and_supports_me_and_logout(): void
    {
        $now = now();
        $email = 'platform-auth@example.test';
        $operatorId = (int) DB::table('platform_operators')->insertGetId([
            'row_version' => 1,
            'first_name' => 'Platform',
            'last_name' => 'Administrator',
            'email' => $email,
            'status' => PlatformOperatorStatus::ACTIVE,
            'credentials_ready_at' => $now,
            'activated_at' => $now,
            'deactivated_at' => null,
            'created_by_operator_id' => null,
            'updated_by_operator_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->app->make(PasswordCredentialService::class)->provision($operatorId, self::PASSWORD);

        $login = $this->postJson('/api/v1/platform/auth/login', [
            'email' => $email,
            'password' => self::PASSWORD,
            'device_name' => 'PHPUnit platform client',
        ])
            ->assertOk()
            ->assertJsonPath('user.email', $email)
            ->assertJsonPath('tenant', null)
            ->assertJsonPath('organization_unit', null)
            ->assertJsonPath('is_platform_operator', true);

        $token = trim((string) $login->json('token'));
        self::assertNotSame('', $token);
        $this->assertDatabaseHas('auth_platform_sessions', [
            'platform_operator_id' => $operatorId,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('auth_platform_login_attempts', [
            'platform_operator_id' => $operatorId,
            'was_successful' => true,
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/platform/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', $email)
            ->assertJsonPath('is_platform_operator', true);

        $this->withToken($token)
            ->postJson('/api/v1/platform/auth/logout')
            ->assertOk();

        $this->assertDatabaseHas('auth_platform_sessions', [
            'platform_operator_id' => $operatorId,
            'status' => 'revoked',
        ]);
    }

    private function createActiveTenant(string $code): int
    {
        $now = now();

        return (int) DB::table('tenants')->insertGetId([
            'row_version' => 1,
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => 'Authentication test tenant',
            'slug' => strtolower($code),
            'logo_object_key' => null,
            'logo_mime_type' => null,
            'logo_size_bytes' => null,
            'base_currency_id' => null,
            'status' => TenantStatus::ACTIVE,
            'status_reason' => 'Authentication integration test.',
            'status_changed_at' => $now,
            'activated_at' => $now,
            'suspended_at' => null,
            'archived_at' => null,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
