<?php

declare(strict_types=1);

namespace Modules\Finance\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class FinanceApiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_account_journal_and_profile_workflows_are_exposed_with_draft_guards(): void
    {
        [$tenantId, $organizationUnitId, $cashId, $capitalId] = $this->context();
        $scope = ['tenant_id' => $tenantId, 'organization_unit_id' => $organizationUnitId];

        $this->patchJson('/api/v1/finance/accounts/'.$cashId, $scope + [
            'account_type_id' => $this->accountTypeId($tenantId, 'ASSET'),
            'code' => '1010',
            'name' => 'Main Cash',
            'normal_balance' => 'debit',
            'opening_balance' => '0.000000',
            'is_posting_account' => true,
            'is_active' => true,
        ])->assertSuccessful()->assertJsonPath('data.name', 'Main Cash');

        $profile = $this->postJson('/api/v1/finance/posting-profiles', $scope + [
            'code' => 'api_profile',
            'name' => 'API Profile',
            'rules' => [
                ['line_key' => 'cash', 'account_id' => $cashId],
                ['line_key' => 'capital', 'account_id' => $capitalId],
            ],
        ])->assertSuccessful()->assertJsonPath('data.code', 'api_profile')->json('data');

        $this->patchJson('/api/v1/finance/posting-profiles/'.$profile['id'], $scope + [
            'code' => 'api_profile',
            'name' => 'Updated API Profile',
            'rules' => [
                ['line_key' => 'cash', 'account_id' => $cashId],
                ['line_key' => 'capital', 'account_id' => $capitalId],
            ],
        ])->assertSuccessful()->assertJsonPath('data.name', 'Updated API Profile');

        $journal = $this->postJson('/api/v1/finance/journals', $scope + $this->journalPayload($cashId, $capitalId))
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'draft')
            ->json('data');

        $this->getJson('/api/v1/finance/journals?'.http_build_query($scope))
            ->assertSuccessful()
            ->assertJsonPath('data.0.journal_number', $journal['journal_number']);
        $this->getJson('/api/v1/finance/journals/'.$journal['id'].'?'.http_build_query($scope))
            ->assertSuccessful()
            ->assertJsonPath('data.can_edit', true)
            ->assertJsonCount(2, 'data.lines');

        $this->patchJson('/api/v1/finance/journals/'.$journal['id'], $scope + $this->journalPayload(
            $cashId,
            $capitalId,
            'Updated draft',
        ))->assertSuccessful()->assertJsonPath('data.description', 'Updated draft');

        $this->postJson('/api/v1/finance/journals/'.$journal['id'].'/post', $scope)->assertSuccessful();
        $this->patchJson('/api/v1/finance/journals/'.$journal['id'], $scope + $this->journalPayload(
            $cashId,
            $capitalId,
            'Illegal edit',
        ))->assertStatus(422);
        $this->postJson('/api/v1/finance/journals/'.$journal['id'].'/reverse', $scope + [
            'reversal_date' => '2026-06-11',
            'reversal_reason' => 'API correction',
        ])->assertSuccessful()
            ->assertJsonPath('data.reversal_reason', 'API correction');

        $draft = $this->postJson('/api/v1/finance/journals', $scope + $this->journalPayload(
            $cashId,
            $capitalId,
            'Cancel me',
        ))->assertSuccessful()->json('data');
        $this->postJson('/api/v1/finance/journals/'.$draft['id'].'/cancel', $scope)
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'cancelled');
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: int}
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
        $yearId = (int) DB::table('finance_fiscal_years')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('finance_fiscal_periods')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'fiscal_year_id' => $yearId,
            'name' => 'June 2026',
            'period_number' => 6,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tenantId, $organizationUnitId, $cashId, $capitalId];
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
            'opening_balance' => '0.000000',
            'current_balance' => '0.000000',
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
