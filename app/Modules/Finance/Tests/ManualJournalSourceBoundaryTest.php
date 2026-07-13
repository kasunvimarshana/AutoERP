<?php

declare(strict_types=1);

namespace Modules\Finance\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ManualJournalSourceBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
    }

    public function test_manual_journal_api_rejects_business_source_identity(): void
    {
        [$tenantId, $organizationUnitId, $cashId, $capitalId] = $this->context();
        $scope = [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
        ];
        $payload = $this->journalPayload($cashId, $capitalId);
        $payload['source_module'] = 'invoice';
        $payload['source_type'] = 'invoice';
        $payload['source_id'] = 99;
        $payload['source_number'] = 'INV-99';
        $payload['source_date'] = '2026-07-13';
        $payload['lines'][0]['source_line_type'] = 'invoice_line';
        $payload['lines'][0]['source_line_id'] = 1;

        $this->tenantPostJson($tenantId, '/api/v1/finance/journals', $scope + $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'source_module',
                'source_type',
                'source_id',
                'source_number',
                'source_date',
                'lines.0.source_line_type',
                'lines.0.source_line_id',
            ]);

        $this->assertDatabaseCount('finance_journal_entries', 0);
    }

    public function test_manual_journal_is_persisted_without_business_source_identity(): void
    {
        [$tenantId, $organizationUnitId, $cashId, $capitalId] = $this->context();
        $scope = [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
        ];

        $journalId = (int) $this->tenantPostJson(
            $tenantId,
            '/api/v1/finance/journals',
            $scope + $this->journalPayload($cashId, $capitalId),
        )->assertSuccessful()->json('data.id');

        $this->assertDatabaseHas('finance_journal_entries', [
            'id' => $journalId,
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'source_module' => null,
            'source_type' => null,
            'source_id' => null,
            'source_number' => null,
            'source_date' => null,
            'source_key' => null,
            'posting_fingerprint' => null,
        ]);
    }

    /** @return array{0: int, 1: int, 2: int, 3: int} */
    private function context(): array
    {
        $suffix = Str::upper(Str::random(6));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-MJS-'.$suffix,
            'name' => 'Manual Journal Source '.$suffix,
            'slug' => 'manual-journal-source-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organizationUnitId = (int) \Tests\Support\OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Manual Journal Organization '.$suffix,
            'code' => 'ORG-MJS-'.$suffix,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $assetTypeId = $this->accountType($tenantId, 'ASSET', 'debit');
        $equityTypeId = $this->accountType($tenantId, 'EQUITY', 'credit');

        return [
            $tenantId,
            $organizationUnitId,
            $this->account($tenantId, $organizationUnitId, $assetTypeId, '1010', 'Cash', 'debit'),
            $this->account($tenantId, $organizationUnitId, $equityTypeId, '3000', 'Capital', 'credit'),
        ];
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
        int $accountTypeId,
        string $code,
        string $name,
        string $normalBalance,
    ): int {
        return (int) DB::table('finance_accounts')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'account_type_id' => $accountTypeId,
            'code' => $code,
            'name' => $name,
            'normal_balance' => $normalBalance,
            'is_posting_account' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function journalPayload(int $cashId, int $capitalId): array
    {
        return [
            'journal_date' => '2026-07-13',
            'journal_type' => 'general',
            'description' => 'Manual journal source boundary test.',
            'exchange_rate' => '1.000000',
            'lines' => [
                [
                    'account_id' => $cashId,
                    'line_number' => 1,
                    'debit' => '50.000000',
                    'credit' => '0.000000',
                ],
                [
                    'account_id' => $capitalId,
                    'line_number' => 2,
                    'debit' => '0.000000',
                    'credit' => '50.000000',
                ],
            ],
        ];
    }
}
