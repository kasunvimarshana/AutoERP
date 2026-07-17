<?php

declare(strict_types=1);

namespace Modules\Finance\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\User\Models\UserModel;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class FinancePostingProfileScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
    }

    public function test_organization_context_lists_exact_and_tenant_fallback_profiles_with_scope(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $this->actingAsTenantUser($tenantId);
        $tenantProfileId = $this->profile($tenantId, null, 'sales_invoice', 'Tenant Sales');
        $organizationProfileId = $this->profile($tenantId, $organizationUnitId, 'sales_invoice', 'Organization Sales');
        $query = http_build_query([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'per_page' => 100,
        ]);

        $profiles = $this->tenantGetJson($tenantId, '/api/v1/finance/posting-profiles?'.$query)
            ->assertSuccessful()
            ->json('data');

        $this->assertSame([$organizationProfileId, $tenantProfileId], array_column($profiles, 'id'));
        $this->assertSame('organization', $profiles[0]['scope']);
        $this->assertSame($organizationUnitId, $profiles[0]['organization_unit_id']);
        $this->assertSame('tenant_default', $profiles[1]['scope']);
        $this->assertNull($profiles[1]['organization_unit_id']);

        $lookups = $this->tenantGetJson($tenantId, '/api/v1/finance/lookups?'.$query)
            ->assertSuccessful()
            ->json('data.profiles');

        $this->assertSame([$organizationProfileId, $tenantProfileId], array_column($lookups, 'id'));
    }

    public function test_tenant_context_lists_only_tenant_default_profiles(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $this->actingAsTenantUser($tenantId);
        $tenantProfileId = $this->profile($tenantId, null, 'sales_invoice', 'Tenant Sales');
        $this->profile($tenantId, $organizationUnitId, 'sales_invoice', 'Organization Sales');
        $query = http_build_query([
            'tenant_id' => $tenantId,
            'per_page' => 100,
        ]);

        $profiles = $this->tenantGetJson($tenantId, '/api/v1/finance/posting-profiles?'.$query)
            ->assertSuccessful()
            ->json('data');

        $this->assertSame([$tenantProfileId], array_column($profiles, 'id'));
        $this->assertSame('tenant_default', $profiles[0]['scope']);
    }

    /** @return array{0: int, 1: int} */
    private function scope(): array
    {
        $suffix = Str::upper(Str::random(6));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-FPS-'.$suffix,
            'name' => 'Finance Profile Scope '.$suffix,
            'slug' => 'finance-profile-scope-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organizationUnitId = (int) OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Finance Profile Organization '.$suffix,
            'code' => 'ORG-FPS-'.$suffix,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tenantId, $organizationUnitId];
    }

    private function profile(
        int $tenantId,
        ?int $organizationUnitId,
        string $code,
        string $name,
    ): int {
        return (int) DB::table('finance_posting_profiles')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => $code,
            'name' => $name,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function actingAsTenantUser(int $tenantId): void
    {
        $userId = TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'email' => 'finance-profile-scope-'.Str::lower(Str::random(8)).'@example.test',
        ]);
        $user = $this->withTenantExecutionContext(
            $tenantId,
            fn (): UserModel => UserModel::query()->findOrFail($userId),
        );

        $this->actingAs($user, (string) config('module-auth.protected_route_guard', 'auth-api'));
    }
}
