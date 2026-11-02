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

final class FinancePostingProfileSimulationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
    }

    public function test_simulate_posting_profile_resolves_effective_accounts_and_detects_unassigned_roles(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $this->actingAsTenantUser($tenantId);

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $organizationUnitId): void {
            $assetTypeId = (int) DB::table('finance_account_types')->insertGetId([
                'tenant_id' => $tenantId,
                'code' => 'ASSET-SIM',
                'name' => 'Simulation Asset',
                'normal_balance' => 'debit',
                'statement_type' => 'balance_sheet',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $cashId = (int) DB::table('finance_accounts')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'account_type_id' => $assetTypeId,
                'code' => '1010-SIM',
                'name' => 'Sim Cash Account',
                'normal_balance' => 'debit',
                'is_posting_account' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $cashRoleId = (int) DB::table('finance_account_roles')->insertGetId([
                'tenant_id' => $tenantId,
                'code' => 'sim_cash',
                'name' => 'Sim Cash Role',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $unassignedRoleId = (int) DB::table('finance_account_roles')->insertGetId([
                'tenant_id' => $tenantId,
                'code' => 'sim_unassigned',
                'name' => 'Sim Unassigned Role',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('finance_account_assignments')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'account_role_id' => $cashRoleId,
                'account_id' => $cashId,
                'effective_from' => '1900-01-01',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $profileId = (int) DB::table('finance_posting_profiles')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'code' => 'sim_test_profile',
                'name' => 'Simulation Test Profile',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('finance_posting_profile_rules')->insert([
                [
                    'tenant_id' => $tenantId,
                    'posting_profile_id' => $profileId,
                    'line_key' => 'cash_line',
                    'account_role_id' => $cashRoleId,
                    'effective_from' => '1900-01-01',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'tenant_id' => $tenantId,
                    'posting_profile_id' => $profileId,
                    'line_key' => 'unassigned_line',
                    'account_role_id' => $unassignedRoleId,
                    'effective_from' => '1900-01-01',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            $response = $this->tenantPostJson(
                $tenantId,
                "/api/v1/finance/posting-profiles/{$profileId}/simulate",
                [
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'posting_date' => '2026-11-02',
                ],
            )->assertSuccessful();

            $data = $response->json('data');
            $this->assertSame($profileId, $data['profile_id']);
            $this->assertSame('sim_test_profile', $data['profile_code']);
            $this->assertFalse($data['is_ready']); // Has an unassigned role
            $this->assertCount(2, $data['lines']);

            $cashLine = collect($data['lines'])->firstWhere('line_key', 'cash_line');
            $this->assertSame('resolved', $cashLine['status']);
            $this->assertSame('1010-SIM', $cashLine['resolved_account']['code']);
            $this->assertSame('tenant_default', $cashLine['resolved_account']['scope']);

            $unassignedLine = collect($data['lines'])->firstWhere('line_key', 'unassigned_line');
            $this->assertSame('error', $unassignedLine['status']);
            $this->assertNull($unassignedLine['resolved_account']);
            $this->assertStringContainsString('no effective assignment', $unassignedLine['message']);
        });
    }

    private function scope(): array
    {
        $suffix = Str::upper(Str::random(6));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-SIM-'.$suffix,
            'name' => 'Finance Simulation Tenant '.$suffix,
            'slug' => 'finance-simulation-tenant-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organizationUnitId = (int) OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Finance Simulation Organization '.$suffix,
            'code' => 'ORG-SIM-'.$suffix,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tenantId, $organizationUnitId];
    }

    private function actingAsTenantUser(int $tenantId): void
    {
        $userId = TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'email' => 'finance-sim-'.Str::lower(Str::random(8)).'@example.test',
        ]);
        $user = $this->withTenantExecutionContext(
            $tenantId,
            fn (): UserModel => UserModel::query()->findOrFail($userId),
        );

        $this->actingAs($user, (string) config('module-auth.protected_route_guard', 'auth-api'));
    }
}
