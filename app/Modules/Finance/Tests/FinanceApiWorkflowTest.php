<?php

declare(strict_types=1);

namespace Modules\Finance\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\User\Models\UserModel;
use Tests\TestCase;

final class FinanceApiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
    }

    public function test_account_journal_and_profile_workflows_are_exposed_with_draft_guards(): void
    {
        [$tenantId, $organizationUnitId, $cashId, $capitalId, $cashRoleId, $capitalRoleId] = $this->context();
        $scope = ['tenant_id' => $tenantId, 'organization_unit_id' => $organizationUnitId];
        $this->actingAsFinanceUser($tenantId);

        $accountPayload = [
            'account_type_id' => $this->accountTypeId($tenantId, 'ASSET'),
            'code' => '1010',
            'name' => 'Main Cash',
            'normal_balance' => 'debit',
            'is_posting_account' => true,
            'is_active' => true,
        ];

        $this->tenantPatchJson($tenantId, '/api/v1/finance/accounts/'.$cashId, $scope + $accountPayload)
            ->assertSuccessful()
            ->assertJsonPath('data.name', 'Main Cash')
            ->assertJsonMissingPath('data.opening_balance')
            ->assertJsonMissingPath('data.current_balance');

        $this->tenantPatchJson($tenantId, '/api/v1/finance/accounts/'.$cashId, $scope + $accountPayload + [
            'opening_balance' => '10.000000',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['opening_balance']);

        $profile = $this->tenantPostJson($tenantId, '/api/v1/finance/posting-profiles', $scope + [
            'code' => 'api_profile',
            'name' => 'API Profile',
            'is_active' => true,
            'rules' => [
                ['line_key' => 'cash', 'account_role_id' => $cashRoleId],
                ['line_key' => 'capital', 'account_role_id' => $capitalRoleId],
            ],
        ])->assertSuccessful()->assertJsonPath('data.code', 'api_profile')->json('data');

        $this->tenantPatchJson($tenantId, '/api/v1/finance/posting-profiles/'.$profile['id'], $scope + [
            'code' => 'api_profile',
            'name' => 'Updated API Profile',
            'is_active' => true,
            'rules' => [
                ['line_key' => 'cash', 'account_role_id' => $cashRoleId],
                ['line_key' => 'capital', 'account_role_id' => $capitalRoleId],
            ],
        ])->assertSuccessful()->assertJsonPath('data.name', 'Updated API Profile');

        $journal = $this->tenantPostJson($tenantId, '/api/v1/finance/journals', $scope + $this->journalPayload($cashId, $capitalId))
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'draft')
            ->json('data');

        $this->tenantGetJson($tenantId, '/api/v1/finance/journals?'.http_build_query($scope))
            ->assertSuccessful()
            ->assertJsonPath('data.0.journal_number', $journal['journal_number']);
        $this->tenantGetJson($tenantId, '/api/v1/finance/journals/'.$journal['id'].'?'.http_build_query($scope))
            ->assertSuccessful()
            ->assertJsonPath('data.can_edit', true)
            ->assertJsonCount(2, 'data.lines');

        $this->tenantPatchJson($tenantId, '/api/v1/finance/journals/'.$journal['id'], $scope + $this->journalPayload(
            $cashId,
            $capitalId,
            'Updated draft',
        ))->assertSuccessful()->assertJsonPath('data.description', 'Updated draft');

        $this->tenantPostJson($tenantId, '/api/v1/finance/journals/'.$journal['id'].'/post', $scope)->assertSuccessful();
        $this->tenantPatchJson($tenantId, '/api/v1/finance/journals/'.$journal['id'], $scope + $this->journalPayload(
            $cashId,
            $capitalId,
            'Illegal edit',
        ))->assertStatus(422);
        $this->tenantPostJson($tenantId, '/api/v1/finance/journals/'.$journal['id'].'/reverse', $scope + [
            'reversal_date' => '2026-06-11',
            'reversal_reason' => 'API correction',
        ])->assertSuccessful()
            ->assertJsonPath('data.reversal_reason', 'API correction');

        $draft = $this->tenantPostJson($tenantId, '/api/v1/finance/journals', $scope + $this->journalPayload(
            $cashId,
            $capitalId,
            'Cancel me',
        ))->assertSuccessful()->json('data');
        $this->tenantPostJson($tenantId, '/api/v1/finance/journals/'.$draft['id'].'/cancel', $scope)
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'cancelled');
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: int, 4: int, 5: int}
     */
    private function context(): array
    {
        $suffix = Str::upper(Str::random(6));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-FAW-'.$suffix,
            'name' => 'Finance API '.$suffix,
            'slug' => 'finance-api-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now()]);
        $organizationUnitId = (int) \Tests\Support\OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Finance API Org',
            'code' => 'ORG-'.$suffix,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $assetId = $this->accountType($tenantId, 'ASSET', 'debit');
        $equityId = $this->accountType($tenantId, 'EQUITY', 'credit');
        $cashId = $this->account($tenantId, $organizationUnitId, $assetId, '1010', 'Cash', 'debit');
        $capitalId = $this->account($tenantId, $organizationUnitId, $equityId, '3000', 'Capital', 'credit');
        $cashRoleId = $this->accountRole($tenantId, 'cash', 'Cash');
        $capitalRoleId = $this->accountRole($tenantId, 'capital', 'Capital');

        return [$tenantId, $organizationUnitId, $cashId, $capitalId, $cashRoleId, $capitalRoleId];
    }

    private function accountType(int $tenantId, string $code, string $normalBalance): int
    {
        return (int) DB::table('finance_account_types')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => $code,
            'normal_balance' => $normalBalance,
            'statement_type' => 'balance_sheet',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function account(
        int $tenantId,
        int $organizationUnitId,
        int $typeId,
        string $code,
        string $name,
        string $normalBalance,
    ): int {
        return (int) DB::table('finance_accounts')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'account_type_id' => $typeId,
            'code' => $code,
            'name' => $name,
            'normal_balance' => $normalBalance,
            'is_posting_account' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function accountRole(int $tenantId, string $code, string $name): int
    {
        return (int) DB::table('finance_account_roles')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => $name,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function accountTypeId(int $tenantId, string $code): int
    {
        return (int) DB::table('finance_account_types')
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->value('id');
    }

    private function actingAsFinanceUser(int $tenantId): void
    {
        $userId = \Tests\Support\TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'email' => 'finance-api-'.Str::lower(Str::random(8)).'@example.test',
        ]);

        $user = $this->withTenantExecutionContext(
            $tenantId,
            fn (): UserModel => UserModel::query()->findOrFail($userId),
        );

        $this->actingAs($user, (string) config('module-auth.protected_route_guard', 'auth-api'));
    }

    /**
     * @return array<string, mixed>
     */
    private function journalPayload(
        int $cashId,
        int $capitalId,
        string $description = 'API journal',
    ): array {
        return [
            'journal_date' => '2026-06-10',
            'journal_type' => 'general',
            'description' => $description,
            'exchange_rate' => '1.000000',
            'lines' => [
                ['account_id' => $cashId, 'line_number' => 1, 'debit' => '50.000000', 'credit' => '0.000000'],
                ['account_id' => $capitalId, 'line_number' => 2, 'debit' => '0.000000', 'credit' => '50.000000'],
            ],
        ];
    }
}
