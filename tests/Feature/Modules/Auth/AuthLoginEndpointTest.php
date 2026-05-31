<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AuthLoginEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function testSeededLocalAdminCanLoginAndResolveCurrentUser(): void
    {
        $tenantId = (int) DB::table('tenants')->where('code', 'AUTOERP')->value('id');
        $organizationUnitId = (int) DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->where('code', 'MAIN')
            ->value('id');

        $loginResponse = $this->postJson('/api/auth/login', [
            'login_identifier' => 'admin@example.com',
            'password' => 'password',
            'provider_key' => 'internal',
            'tenant_id' => $tenantId,
        ]);

        self::assertSame(200, $loginResponse->getStatusCode(), $loginResponse->getContent());

        $loginResponse
            ->assertJsonPath('data.user.email', 'admin@example.com')
            ->assertJsonPath('data.user.tenant_id', $tenantId)
            ->assertJsonPath('data.tokens.token_type', 'Bearer');

        $accessToken = (string) $loginResponse->json('data.tokens.access_token');

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'X-Organization-Unit-ID' => (string) $organizationUnitId,
            'X-Tenant-ID' => (string) $tenantId,
        ])
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.tenant_id', $tenantId)
            ->assertJsonPath('data.organization_unit_id', $organizationUnitId);
    }
}
