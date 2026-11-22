<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Services\Credentials\PasswordCredentialService;
use Modules\Tenant\Constants\TenantStatus;
use Modules\User\Constants\PlatformOperatorStatus;
use Modules\User\Constants\UserOrganizationUnitStatus;
use Tests\Support\ActiveTenantSubscriptionFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantAuthenticationFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class RefreshTokenRotationConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Valid-Test-Password-2026!';

    public function test_tenant_refresh_token_rotation_allows_concurrent_requests_within_grace_period(): void
    {
        $tenantId = $this->createActiveTenant('AUTH-CONCURRENCY');
        ActiveTenantSubscriptionFixture::create($tenantId);
        $organizationUnitId = OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Main organization',
            'code' => 'MAIN',
        ]);
        $email = 'concurrency@example.test';
        $userId = TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'Concurrent',
            'last_name' => 'Tester',
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

        // 1. Initial Login
        $loginResponse = $this->withHeader('X-Tenant-Id', (string) $tenantId)
            ->postJson('/api/v1/auth/login', [
                'identifier' => $email,
                'password' => self::PASSWORD,
                'organization_unit_id' => $organizationUnitId,
                'device_name' => 'Concurrent Client 1',
            ])
            ->assertOk();

        $cookieName = (string) config('module-auth.cookies.tenant_refresh.name');
        $initialCookie = null;
        foreach ($loginResponse->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $cookieName) {
                $initialCookie = $cookie->getValue();
                break;
            }
        }
        self::assertNotNull($initialCookie, 'Tenant refresh cookie should be set after login.');

        // 2. Request 1: Refreshes token with initialCookie
        $refresh1 = $this->postRefresh('/api/v1/auth/refresh', $cookieName, $initialCookie, $tenantId)
            ->assertOk()
            ->assertJsonStructure(['token', 'user', 'tenant']);

        $token1 = (string) $refresh1->json('token');
        self::assertNotSame('', $token1);

        // 3. Request 2: Concurrent/race request presents the SAME initialCookie within grace period (0 seconds elapsed)
        $refresh2 = $this->postRefresh('/api/v1/auth/refresh', $cookieName, $initialCookie, $tenantId)
            ->assertOk()
            ->assertJsonStructure(['token', 'user', 'tenant']);

        $token2 = (string) $refresh2->json('token');
        self::assertNotSame('', $token2);

        // 4. Confirm session remains active in database
        $this->assertDatabaseHas('auth_sessions', [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'status' => 'active',
        ]);

        // 5. Verify both issued access tokens work on protected endpoints
        $this->withToken($token1)
            ->withHeader('X-Tenant-Id', (string) $tenantId)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', $email);

        $this->withToken($token2)
            ->withHeader('X-Tenant-Id', (string) $tenantId)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', $email);
    }

    public function test_tenant_refresh_token_rotation_detects_reuse_and_revokes_after_grace_period(): void
    {
        $tenantId = $this->createActiveTenant('AUTH-EXPIRED-GRACE');
        ActiveTenantSubscriptionFixture::create($tenantId);
        $organizationUnitId = OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Main organization',
            'code' => 'MAIN',
        ]);
        $email = 'expired-grace@example.test';
        $userId = TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'Grace',
            'last_name' => 'Tester',
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

        // 1. Initial Login
        $loginResponse = $this->withHeader('X-Tenant-Id', (string) $tenantId)
            ->postJson('/api/v1/auth/login', [
                'identifier' => $email,
                'password' => self::PASSWORD,
                'organization_unit_id' => $organizationUnitId,
                'device_name' => 'Test Client',
            ])
            ->assertOk();

        $cookieName = (string) config('module-auth.cookies.tenant_refresh.name');
        $initialCookie = null;
        foreach ($loginResponse->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $cookieName) {
                $initialCookie = $cookie->getValue();
                break;
            }
        }
        self::assertNotNull($initialCookie);

        // 2. First refresh rotates the token
        $this->postRefresh('/api/v1/auth/refresh', $cookieName, $initialCookie, $tenantId)
            ->assertOk();

        // 3. Fast-forward past rotation grace period (e.g. 45 seconds > 30 seconds default)
        Carbon::setTestNow(now()->addSeconds(45));

        try {
            // 4. Replaying the rotated token after grace period must trigger reuse detection (401 TOKEN_REVOKED)
            $response = $this->postRefresh('/api/v1/auth/refresh', $cookieName, $initialCookie, $tenantId)
                ->assertStatus(401);

            self::assertSame(AuthErrorCode::TOKEN_REVOKED, $response->json('error.code') ?? $response->json('code'));

            // 5. Confirm the session has been revoked due to reuse detection
            $this->assertDatabaseHas('auth_sessions', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'status' => 'revoked',
                'revocation_reason' => 'refresh_token_reuse_detected',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_platform_refresh_token_rotation_allows_concurrent_requests_within_grace_period(): void
    {
        $now = now();
        $email = 'plat-concurrent@example.test';
        $operatorId = (int) DB::table('platform_operators')->insertGetId([
            'row_version' => 1,
            'first_name' => 'Platform',
            'last_name' => 'Concurrent',
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

        $loginResponse = $this->postJson('/api/v1/platform/auth/login', [
            'email' => $email,
            'password' => self::PASSWORD,
            'device_name' => 'Platform Client',
        ])->assertOk();

        $cookieName = (string) config('module-auth.cookies.platform_refresh.name');
        $initialCookie = null;
        foreach ($loginResponse->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $cookieName) {
                $initialCookie = $cookie->getValue();
                break;
            }
        }
        self::assertNotNull($initialCookie);

        // First refresh
        $refresh1 = $this->postRefresh('/api/v1/platform/auth/refresh', $cookieName, $initialCookie)
            ->assertOk();
        $token1 = (string) $refresh1->json('token');

        // Second refresh with same cookie within grace period
        $refresh2 = $this->postRefresh('/api/v1/platform/auth/refresh', $cookieName, $initialCookie)
            ->assertOk();
        $token2 = (string) $refresh2->json('token');

        // Both tokens work on platform /me
        $this->withToken($token1)->getJson('/api/v1/platform/auth/me')->assertOk();
        $this->withToken($token2)->getJson('/api/v1/platform/auth/me')->assertOk();

        // Platform session is still active
        $this->assertDatabaseHas('auth_platform_sessions', [
            'platform_operator_id' => $operatorId,
            'status' => 'active',
        ]);
    }

    private function postRefresh(string $uri, string $cookieName, string $cookieValue, ?int $tenantId = null)
    {
        $server = ['HTTP_ACCEPT' => 'application/json'];
        if ($tenantId !== null) {
            $server['HTTP_X-Tenant-Id'] = (string) $tenantId;
        }

        return $this->call('POST', $uri, [], [$cookieName => $cookieValue], [], $server);
    }

    private function createActiveTenant(string $code): int
    {
        $now = now();

        return (int) DB::table('tenants')->insertGetId([
            'row_version' => 1,
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => 'Concurrency test tenant',
            'slug' => strtolower($code),
            'logo_object_key' => null,
            'logo_mime_type' => null,
            'logo_size_bytes' => null,
            'base_currency_id' => null,
            'status' => TenantStatus::ACTIVE,
            'status_reason' => 'Concurrency integration test.',
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
