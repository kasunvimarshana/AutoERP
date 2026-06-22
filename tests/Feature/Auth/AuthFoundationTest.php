<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Contracts\CurrentTenantContextResolverInterface;
use Modules\Core\Contracts\PasswordHasherInterface;
use Tests\TestCase;

final class AuthFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_returns_token_and_readable_context(): void
    {
        $context = $this->createAuthContext();

        $response = $this->postJson('/api/v1/auth/login', [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'login_identifier' => 'admin@example.test',
            'password' => 'secret-password',
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.name', 'Ada Lovelace')
            ->assertJsonPath('user.email', 'admin@example.test')
            ->assertJsonPath('tenant.name', 'Acme ERP')
            ->assertJsonPath('organization_unit.name', 'Head Office')
            ->assertJsonStructure([
                'token',
                'refresh_token',
                'user' => ['id', 'name', 'email'],
                'tenant' => ['id', 'name'],
                'organization_unit' => ['id', 'name'],
                'session_id',
            ]);
    }

    public function test_seeded_local_admin_can_login_without_tenant_id_from_domain(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->withHeaders(['Host' => 'localhost:5173'])
            ->postJson('/api/v1/auth/login', [
                'login_identifier' => 'admin@example.com',
                'password' => 'password',
            ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'admin@example.com')
            ->assertJsonPath('user.username', 'admin')
            ->assertJsonPath('tenant.code', 'AUTOERP')
            ->assertJsonPath('organization_unit.code', 'HQ')
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email'],
                'tenant' => ['id', 'code', 'name'],
                'organization_unit' => ['id', 'code', 'name'],
            ]);
    }

    public function test_default_tenant_domains_are_seeded_for_local_development(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tenantId = (int) DB::table('tenants')->where('code', 'AUTOERP')->value('id');

        foreach (['localhost', '127.0.0.1', 'autoerp.local', 'autoerp.test'] as $domain) {
            $this->assertDatabaseHas('tenant_domains', [
                'tenant_id' => $tenantId,
                'domain' => $domain,
                'status' => 'active',
            ]);
        }
    }

    public function test_tenant_resolver_strips_port_and_resolves_localhost_domain(): void
    {
        $this->seed(DatabaseSeeder::class);

        $request = Request::create(
            '/api/v1/auth/login',
            'POST',
            [],
            [],
            [],
            ['HTTP_HOST' => 'localhost:5173'],
        );

        $context = app(CurrentTenantContextResolverInterface::class)->resolve($request);

        $this->assertNotNull($context);
        $this->assertSame('AUTOERP', $context->tenantCode());
        $this->assertSame('localhost', $context->domain());
    }

    public function test_invalid_password_without_tenant_id_fails_after_domain_resolution(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->withHeaders(['Host' => 'localhost'])
            ->postJson('/api/v1/auth/login', [
                'login_identifier' => 'admin@example.com',
                'password' => 'wrong-password',
            ]);

        $response->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Credentials are invalid.');
    }

    public function test_unknown_domain_returns_tenant_resolution_error_before_login_lookup(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->call(
            'POST',
            '/api/v1/auth/login',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_HOST' => 'unknown.example',
                'SERVER_NAME' => 'unknown.example',
            ],
            (string) json_encode([
                'tenant_domain' => 'unknown.example',
                'login_identifier' => 'admin@example.com',
                'password' => 'password',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertBadRequest()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Tenant could not be resolved for this domain.');
    }

    public function test_invalid_credentials_return_unauthorized_error(): void
    {
        $context = $this->createAuthContext();

        $response = $this->postJson('/api/v1/auth/login', [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'login_identifier' => 'admin@example.test',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'AUTH_INVALID_CREDENTIALS');
    }

    public function test_seeded_local_admin_can_login_with_username(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->withHeaders(['Host' => 'localhost'])
            ->postJson('/api/v1/auth/login', [
                'login_identifier' => 'admin',
                'password' => 'password',
            ])
            ->assertOk()
            ->assertJsonPath('user.username', 'admin')
            ->assertJsonPath('user.email', 'admin@example.com');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $context = $this->createAuthContext(['status' => 'inactive']);

        $response = $this->postJson('/api/v1/auth/login', [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'login_identifier' => 'admin@example.test',
            'password' => 'secret-password',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'AUTH_USER_INACTIVE');
    }

    public function test_me_endpoint_returns_user_tenant_and_organization_context(): void
    {
        $context = $this->createAuthContext();
        $session = $this->login($context);

        $response = $this->withAuthHeaders($session['token'], $context)
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('user.name', 'Ada Lovelace')
            ->assertJsonPath('tenant.name', 'Acme ERP')
            ->assertJsonPath('organization_unit.name', 'Head Office');
    }

    public function test_logout_invalidates_current_token(): void
    {
        $context = $this->createAuthContext();
        $session = $this->login($context);

        $this->withAuthHeaders($session['token'], $context)
            ->postJson('/api/v1/auth/logout', [
                'access_token' => $session['token'],
                'session_id' => $session['session_id'],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->withAuthHeaders($session['token'], $context)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_protected_route_requires_authentication(): void
    {
        $context = $this->createAuthContext();

        $this->withHeaders([
            'X-Tenant-Id' => (string) $context['tenant_id'],
            'X-Organization-Unit-Id' => (string) $context['organization_unit_id'],
        ])
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    /**
     * @param  array<string,mixed>  $userOverrides
     * @return array{tenant_id:int,organization_unit_id:int,user_id:int}
     */
    private function createAuthContext(array $userOverrides = []): array
    {
        $now = now();
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'ACME',
            'name' => 'Acme ERP',
            'slug' => 'acme-erp',
            'status' => 'active',
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now]);
        $organizationUnitId = (int) DB::table('organization_units')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Head Office',
            'code' => 'HO',
            'path' => '/ho',
            'is_active' => true,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $userId = (int) DB::table('users')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'admin@example.test',
            'password' => app(PasswordHasherInterface::class)->hash('secret-password'),
            'status' => $userOverrides['status'] ?? 'active',
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('user_tenants')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
            'is_default' => true,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
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

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
        ];
    }

    /**
     * @param  array{tenant_id:int,organization_unit_id:int,user_id:int}  $context
     * @return array{token:string,session_id:int}
     */
    private function login(array $context): array
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'login_identifier' => 'admin@example.test',
            'password' => 'secret-password',
        ]);

        $response->assertOk();

        return [
            'token' => (string) $response->json('token'),
            'session_id' => (int) $response->json('session_id'),
        ];
    }

    /**
     * @param  array{tenant_id:int,organization_unit_id:int,user_id:int}  $context
     */
    private function withAuthHeaders(string $token, array $context): self
    {
        return $this->withToken($token)->withHeaders([
            'X-Tenant-Id' => (string) $context['tenant_id'],
            'X-Organization-Unit-Id' => (string) $context['organization_unit_id'],
        ]);
    }
}
