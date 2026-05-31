<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class FinanceModuleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            if (! Schema::hasTable('accounts')) {
                return;
            }

            $tenantId = $this->tenantId();
            if ($tenantId === null) {
                return;
            }

            $organizationUnitId = $this->organizationUnitId($tenantId);

            $this->seedCostCenter($tenantId, $organizationUnitId);
            $fiscalYearId = $this->seedFiscalYearAndPeriods($tenantId, $organizationUnitId);
            $this->seedBankAccount($tenantId, $organizationUnitId);
            $this->seedSampleJournal($tenantId, $organizationUnitId, $fiscalYearId);
            $this->seedBudget($tenantId, $organizationUnitId, $fiscalYearId);
        });
    }

    private function tenantId(): ?int
    {
        if (! Schema::hasTable('tenants')) {
            return null;
        }

        $id = (int) DB::table('tenants')->where('code', 'AUTOERP')->value('id');
        $id = $id > 0 ? $id : (int) DB::table('tenants')->value('id');

        return $id > 0 ? $id : null;
    }

    private function organizationUnitId(int $tenantId): ?int
    {
        if (! Schema::hasTable('organization_units')) {
            return null;
        }

        $id = (int) DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->where('code', 'MAIN')
            ->value('id');
        $id = $id > 0 ? $id : (int) DB::table('organization_units')->where('tenant_id', $tenantId)->value('id');

        return $id > 0 ? $id : null;
    }

    private function seedCostCenter(int $tenantId, ?int $organizationUnitId): void
    {
        if (! Schema::hasTable('cost_centers')) {
            return;
        }

        DB::table('cost_centers')->updateOrInsert(
            ['tenant_id' => $tenantId, 'code' => 'GEN'],
            [
                'description' => 'General operations cost center.',
                'is_active' => true,
                'metadata' => json_encode(['seed_source' => 'finance_module']),
                'name' => 'General Operations',
                'organization_unit_id' => $organizationUnitId,
                'parent_id' => null,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function seedFiscalYearAndPeriods(int $tenantId, ?int $organizationUnitId): ?int
    {
        if (! Schema::hasTable('fiscal_years')) {
            return null;
        }

        $year = CarbonImmutable::now()->year;
        $start = CarbonImmutable::create($year, 1, 1);
        $end = CarbonImmutable::create($year, 12, 31);

        DB::table('fiscal_years')->updateOrInsert(
            ['tenant_id' => $tenantId, 'name' => (string) $year],
            [
                'end_date' => $end->toDateString(),
                'is_current' => true,
                'metadata' => json_encode(['seed_source' => 'finance_module']),
                'organization_unit_id' => $organizationUnitId,
                'row_version' => 1,
                'start_date' => $start->toDateString(),
                'status' => 'OPEN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $fiscalYearId = (int) DB::table('fiscal_years')->where('tenant_id', $tenantId)->where('name', (string) $year)->value('id');

        if ($fiscalYearId > 0 && Schema::hasTable('fiscal_periods')) {
            for ($month = 1; $month <= 12; $month++) {
                $periodStart = CarbonImmutable::create($year, $month, 1);
                DB::table('fiscal_periods')->updateOrInsert(
                    [
                        'tenant_id' => $tenantId,
                        'fiscal_year_id' => $fiscalYearId,
                        'period_number' => $month,
                    ],
                    [
                        'end_date' => $periodStart->endOfMonth()->toDateString(),
                        'metadata' => json_encode(['seed_source' => 'finance_module']),
                        'name' => $periodStart->format('F Y'),
                        'organization_unit_id' => $organizationUnitId,
                        'period_type' => 'MONTH',
                        'row_version' => 1,
                        'start_date' => $periodStart->toDateString(),
                        'status' => 'OPEN',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }

        return $fiscalYearId > 0 ? $fiscalYearId : null;
    }

    private function seedBankAccount(int $tenantId, ?int $organizationUnitId): void
    {
        if (! Schema::hasTable('bank_accounts')) {
            return;
        }

        $accountId = $this->accountId($tenantId, '1010') ?? $this->accountId($tenantId, '1000');
        if ($accountId === null) {
            return;
        }

        DB::table('bank_accounts')->updateOrInsert(
            ['tenant_id' => $tenantId, 'account_number' => 'MAIN-OPERATING'],
            [
                'account_id' => $accountId,
                'bank_name' => 'Main Bank',
                'current_balance' => 0,
                'feed_credentials_enc' => null,
                'feed_provider' => null,
                'iban' => null,
                'is_active' => true,
                'last_reconciled_at' => null,
                'last_reconciled_balance' => null,
                'metadata' => json_encode(['seed_source' => 'finance_module']),
                'name' => 'Main Operating Account',
                'opening_balance' => 0,
                'organization_unit_id' => $organizationUnitId,
                'routing_number' => null,
                'row_version' => 1,
                'swift_bic' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function seedSampleJournal(int $tenantId, ?int $organizationUnitId, ?int $fiscalYearId): void
    {
        if (! Schema::hasTable('journal_entries') || ! Schema::hasTable('journal_entry_lines')) {
            return;
        }

        $cashAccountId = $this->accountId($tenantId, '1000');
        $incomeAccountId = $this->accountId($tenantId, '4000');
        if ($cashAccountId === null || $incomeAccountId === null) {
            return;
        }

        $periodId = $fiscalYearId !== null && Schema::hasTable('fiscal_periods')
            ? (int) DB::table('fiscal_periods')->where('tenant_id', $tenantId)->where('fiscal_year_id', $fiscalYearId)->where('period_number', (int) now()->format('n'))->value('id')
            : null;

        DB::table('journal_entries')->updateOrInsert(
            ['tenant_id' => $tenantId, 'entry_number' => 'JE-SEED-0001'],
            [
                'description' => 'Seeded balanced manual journal for Finance UI verification.',
                'entry_date' => now()->toDateString(),
                'entry_type' => 'MANUAL',
                'fiscal_period_id' => $periodId ?: null,
                'is_reversed' => false,
                'metadata' => json_encode(['seed_source' => 'finance_module']),
                'organization_unit_id' => $organizationUnitId,
                'posting_date' => now()->toDateString(),
                'posted_at' => null,
                'posted_by' => null,
                'reference_id' => null,
                'reference_type' => null,
                'reversal_entry_id' => null,
                'reversed_at' => null,
                'row_version' => 1,
                'source_context' => json_encode(['seed_source' => 'finance_module']),
                'source_id' => null,
                'source_module' => 'finance',
                'source_reference' => 'FIN-SEED',
                'source_type' => 'manual_seed',
                'status' => 'DRAFT',
                'total_credit' => 1000,
                'total_debit' => 1000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $journalId = (int) DB::table('journal_entries')->where('tenant_id', $tenantId)->where('entry_number', 'JE-SEED-0001')->value('id');
        if ($journalId < 1) {
            return;
        }

        foreach ([
            [1, $cashAccountId, 1000, 0, 'Cash receipt side'],
            [2, $incomeAccountId, 0, 1000, 'Income side'],
        ] as [$lineNumber, $accountId, $debit, $credit, $description]) {
            DB::table('journal_entry_lines')->updateOrInsert(
                [
                    'tenant_id' => $tenantId,
                    'journal_entry_id' => $journalId,
                    'line_number' => $lineNumber,
                ],
                [
                    'account_id' => $accountId,
                    'base_credit_amount' => $credit,
                    'base_debit_amount' => $debit,
                    'credit_amount' => $credit,
                    'currency_id' => null,
                    'debit_amount' => $debit,
                    'description' => $description,
                    'exchange_rate' => 1,
                    'metadata' => json_encode(['seed_source' => 'finance_module']),
                    'organization_unit_id' => $organizationUnitId,
                    'party_id' => null,
                    'party_type' => null,
                    'row_version' => 1,
                    'source_line_reference' => 'FIN-SEED-' . $lineNumber,
                    'tax_amount' => 0,
                    'tax_rate_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function seedBudget(int $tenantId, ?int $organizationUnitId, ?int $fiscalYearId): void
    {
        if ($fiscalYearId === null || ! Schema::hasTable('budgets') || ! Schema::hasTable('budget_lines')) {
            return;
        }

        $expenseAccountId = $this->accountId($tenantId, '5000');
        if ($expenseAccountId === null) {
            return;
        }

        DB::table('budgets')->updateOrInsert(
            ['tenant_id' => $tenantId, 'name' => 'Operating Expense Budget'],
            [
                'approved_at' => null,
                'approved_by' => null,
                'budget_type' => 'expense',
                'end_date' => now()->endOfYear()->toDateString(),
                'fiscal_year_id' => $fiscalYearId,
                'metadata' => json_encode(['seed_source' => 'finance_module']),
                'notes' => 'Seeded budget for Finance UI verification.',
                'organization_unit_id' => $organizationUnitId,
                'row_version' => 1,
                'start_date' => now()->startOfYear()->toDateString(),
                'status' => 'DRAFT',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $budgetId = (int) DB::table('budgets')->where('tenant_id', $tenantId)->where('name', 'Operating Expense Budget')->value('id');
        if ($budgetId < 1) {
            return;
        }

        DB::table('budget_lines')->updateOrInsert(
            ['tenant_id' => $tenantId, 'budget_id' => $budgetId, 'account_id' => $expenseAccountId, 'cost_center_id' => null],
            [
                'metadata' => json_encode(['seed_source' => 'finance_module']),
                'notes' => 'Seeded annual expense budget line.',
                'organization_unit_id' => $organizationUnitId,
                'period_1_amount' => 1000,
                'period_2_amount' => 1000,
                'period_3_amount' => 1000,
                'period_4_amount' => 1000,
                'period_5_amount' => 1000,
                'period_6_amount' => 1000,
                'period_7_amount' => 1000,
                'period_8_amount' => 1000,
                'period_9_amount' => 1000,
                'period_10_amount' => 1000,
                'period_11_amount' => 1000,
                'period_12_amount' => 1000,
                'row_version' => 1,
                'total_amount' => 12000,
                'used_amount' => 0,
                'variance_amount' => 12000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function accountId(int $tenantId, string $code): ?int
    {
        $id = (int) DB::table('accounts')->where('tenant_id', $tenantId)->where('code', $code)->value('id');

        return $id > 0 ? $id : null;
    }
}
