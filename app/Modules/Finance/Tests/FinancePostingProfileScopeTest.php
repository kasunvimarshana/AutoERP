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

    public function test_organization_posting_uses_tenant_default_profile_and_global_accounts(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $organizationUnitId): void {
            $assetTypeId = (int) DB::table('finance_account_types')->insertGetId([
                'tenant_id' => $tenantId,
                'code' => 'ASSET-FALLBACK',
                'name' => 'Fallback Asset',
                'normal_balance' => 'debit',
                'statement_type' => 'balance_sheet',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $equityTypeId = (int) DB::table('finance_account_types')->insertGetId([
                'tenant_id' => $tenantId,
                'code' => 'EQUITY-FALLBACK',
                'name' => 'Fallback Equity',
                'normal_balance' => 'credit',
                'statement_type' => 'balance_sheet',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $cashId = (int) DB::table('finance_accounts')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'account_type_id' => $assetTypeId,
                'code' => 'FALLBACK-CASH',
                'name' => 'Fallback Cash',
                'normal_balance' => 'debit',
                'is_posting_account' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $capitalId = (int) DB::table('finance_accounts')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'account_type_id' => $equityTypeId,
                'code' => 'FALLBACK-CAPITAL',
                'name' => 'Fallback Capital',
                'normal_balance' => 'credit',
                'is_posting_account' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $cashRoleId = (int) DB::table('finance_account_roles')->insertGetId([
                'tenant_id' => $tenantId,
                'code' => 'fallback_cash',
                'name' => 'Fallback Cash',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $capitalRoleId = (int) DB::table('finance_account_roles')->insertGetId([
                'tenant_id' => $tenantId,
                'code' => 'fallback_capital',
                'name' => 'Fallback Capital',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            foreach ([[$cashRoleId, $cashId], [$capitalRoleId, $capitalId]] as [$roleId, $accountId]) {
                DB::table('finance_account_assignments')->insert([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => null,
                    'account_role_id' => $roleId,
                    'account_id' => $accountId,
                    'effective_from' => '1900-01-01',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $profileId = $this->profile($tenantId, null, 'tenant_fallback_posting', 'Tenant Fallback Posting');
            foreach ([['fallback_cash', $cashRoleId], ['fallback_capital', $capitalRoleId]] as [$lineKey, $roleId]) {
                DB::table('finance_posting_profile_rules')->insert([
                    'tenant_id' => $tenantId,
                    'posting_profile_id' => $profileId,
                    'line_key' => $lineKey,
                    'account_role_id' => $roleId,
                    'effective_from' => '1900-01-01',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $result = app(FinancePostingInterface::class)->post(new PostingContext(
                source: new PostingSourceData(
                    sourceType: 'tenant_fallback_test',
                    sourceId: 1,
                    tenantId: $tenantId,
                    organizationUnitId: $organizationUnitId,
                    sourceModule: 'finance',
                ),
                postingDate: '2026-10-06',
                lines: [
                    new PostingLine(debit: '100.000000', profileKey: 'fallback_cash'),
                    new PostingLine(credit: '100.000000', profileKey: 'fallback_capital'),
                ],
                postingProfileCode: 'tenant_fallback_posting',
            ));

            self::assertSame('posted', $result->status);
            $this->assertDatabaseHas('finance_journal_entries', [
                'id' => $result->journalId,
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'posting_profile_id' => $profileId,
            ]);
        });
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
