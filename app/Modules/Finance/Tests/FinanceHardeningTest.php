<?php

declare(strict_types=1);

namespace Modules\Finance\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\DTOs\CreateAccountData;
use Modules\Finance\DTOs\FinancePostingLine;
use Modules\Finance\DTOs\FinancePostingRequest;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Finance\Enums\NormalBalance;
use Modules\Finance\Enums\StatementType;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceAccountType;
use Modules\Finance\Models\FinanceFiscalPeriod;
use Modules\Finance\Models\FinanceFiscalYear;
use Modules\Finance\Models\FinanceLedgerEntry;
use Modules\Finance\Models\FinancePostingProfile;
use Modules\Finance\Models\FinancePostingProfileRule;
use Modules\Finance\Services\ChartOfAccountsService;
use Modules\Finance\Services\FinanceStatementService;
use Modules\Finance\Services\TrialBalanceService;
use Tests\TestCase;

final class FinanceHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_account_code_is_rejected_per_tenant(): void
    {
        [$tenantId, $cash] = $this->context();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Finance account code already exists for this tenant.');

        $this->withTenantExecutionContext($tenantId, fn () => app(ChartOfAccountsService::class)->createAccount(new CreateAccountData(
            tenantId: $tenantId,
            accountTypeId: (int) $cash->account_type_id,
            code: '1010',
            name: 'Duplicate Cash',
            normalBalance: NormalBalance::Debit,
        )));
    }

    public function test_posting_profile_resolves_accounts_and_persists_source_traceability(): void
    {
        [$tenantId, $cash, $capital] = $this->context();
        $profile = $this->profile($tenantId, 'opening_test', [
            'cash' => $cash,
            'capital' => $capital,
        ]);

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $profile): void {
            $result = app(FinancePostingInterface::class)->post(new FinancePostingRequest(
                source: new PostingSourceData(
                    sourceType: 'opening_balance',
                    sourceId: 99,
                    tenantId: $tenantId,
                    sourceModule: 'finance',
                    sourceNumber: 'OPEN-99',
                    sourceDate: '2026-06-10',
                ),
                postingDate: '2026-06-10',
                lines: [
                    new FinancePostingLine(null, 'Cash', debit: '250.000000', profileKey: 'cash'),
                    new FinancePostingLine(null, 'Capital', credit: '250.000000', profileKey: 'capital'),
                ],
                postingProfileCode: 'opening_test',
            ));

            $this->assertSame('posted', $result->status);
            $this->assertDatabaseHas('finance_journal_entries', [
                'id' => $result->journalId,
                'posting_profile_id' => $profile->getKey(),
                'source_module' => 'finance',
                'source_type' => 'opening_balance',
                'source_id' => 99,
                'source_number' => 'OPEN-99',
            ]);
            $this->assertStringStartsWith(
                '2026-06-10',
                (string) DB::table('finance_journal_entries')
                    ->where('id', $result->journalId)
                    ->value('source_date'),
            );
            $this->assertDatabaseCount('finance_ledger_entries', 2);
            $this->assertDatabaseHas('finance_ledger_entries', [
                'journal_entry_id' => $result->journalId,
                'source_module' => 'finance',
                'source_number' => 'OPEN-99',
            ]);
            $this->assertStringStartsWith(
                '2026-06-10',
                (string) DB::table('finance_ledger_entries')
                    ->where('journal_entry_id', $result->journalId)
                    ->value('source_date'),
            );
        });
    }

    public function test_missing_posting_profile_mapping_fails_clearly(): void
    {
        [$tenantId] = $this->context();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing account role mapping [cash]');

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            FinancePostingProfile::query()->create([
                'tenant_id' => $tenantId,
                'code' => 'missing_rule',
                'name' => 'Missing Rule',
                'is_active' => true,
            ]);

            app(FinancePostingInterface::class)->validatePosting(new FinancePostingRequest(
                source: new PostingSourceData('test', 1, $tenantId, sourceModule: 'finance'),
                postingDate: '2026-06-10',
                lines: [
                    new FinancePostingLine(null, 'Cash', debit: '10.000000', profileKey: 'cash'),
                    new FinancePostingLine(null, 'Capital', credit: '10.000000', profileKey: 'capital'),
                ],
                postingProfileCode: 'missing_rule',
            ));
        });
    }

    public function test_ledger_entries_are_immutable_and_reversal_uses_new_period_and_reason(): void
    {
        [$tenantId] = $this->context(includeJuly: true);
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $posting = app(FinancePostingInterface::class);
            $posted = $posting->post($this->directRequest($tenantId, '2026-06-30'));
            $reversal = $posting->reverseJournal($posted->journalId, '2026-07-01', reason: 'Correct source date');

            $this->assertDatabaseHas('finance_journal_entries', [
                'id' => $reversal->journalId,
                'reversal_of_id' => $posted->journalId,
                'reversal_reason' => 'Correct source date',
            ]);
            $this->assertSame(7, (int) DB::table('finance_fiscal_periods')
                ->where('id', DB::table('finance_journal_entries')->where('id', $reversal->journalId)->value('fiscal_period_id'))
                ->value('period_number'));

            $ledger = FinanceLedgerEntry::query()->where('journal_entry_id', $posted->journalId)->firstOrFail();
            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('Ledger entries are immutable.');
            $ledger->forceFill(['debit' => '999.000000'])->save();
        });
    }

    public function test_trial_balance_and_financial_statements_are_ledger_balanced(): void
    {
        [$tenantId, $cash] = $this->context(withIncomeAccounts: true);
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $cash): void {
            app(FinancePostingInterface::class)->post($this->directRequest($tenantId, '2026-06-10'));
            $revenue = FinanceAccount::query()->where('code', '4100')->firstOrFail();
            $salesProfile = $this->profile($tenantId, 'sales_test', [
                'cash' => $cash,
                'revenue' => $revenue,
            ]);
            app(FinancePostingInterface::class)->post(new FinancePostingRequest(
                source: new PostingSourceData('sales_invoice', 2, $tenantId, sourceModule: 'invoice'),
                postingDate: '2026-06-11',
                lines: [
                    new FinancePostingLine(null, 'Cash', debit: '40.000000', profileKey: 'cash'),
                    new FinancePostingLine(null, 'Sales Revenue', credit: '40.000000', profileKey: 'revenue'),
                ],
                postingProfileCode: (string) $salesProfile->code,
            ));

            $trial = app(TrialBalanceService::class)->calculate(
                $tenantId,
                dateFrom: '2026-06-01',
                dateTo: '2026-06-30',
            );
            $this->assertTrue($trial->isBalanced);
            $this->assertSame('140.000000', $trial->totalDebit);
            $this->assertSame('140.000000', $trial->totalCredit);

            $statements = app(FinanceStatementService::class);
            $profitAndLoss = $statements->profitAndLoss($tenantId, null, '2026-06-01', '2026-06-30');
            $balanceSheet = $statements->balanceSheet($tenantId, null, '2026-06-30');
            $this->assertSame('40.000000', $profitAndLoss['net_profit']);
            $this->assertSame('40.000000', $balanceSheet['current_earnings']);
            $this->assertSame('0.000000', $balanceSheet['difference']);
        });
    }

    public function test_trial_balance_fiscal_period_respects_organization_scope(): void
    {
        $tenantId = $this->createTenant();
        $orgOne = $this->organizationUnit($tenantId, 'ORG-TB-A');
        $orgTwo = $this->organizationUnit($tenantId, 'ORG-TB-B');
        $yearTwo = (int) DB::table('finance_fiscal_years')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $orgTwo,
            'name' => 'FY 2026 Org B',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $periodTwo = (int) DB::table('finance_fiscal_periods')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $orgTwo,
            'fiscal_year_id' => $yearTwo,
            'name' => 'June 2026 Org B',
            'period_number' => 6,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->withTenantExecutionContext($tenantId, fn () => app(TrialBalanceService::class)->calculate($tenantId, $orgOne, $periodTwo));
    }

    /**
     * @return array{0: int, 1: FinanceAccount, 2: FinanceAccount}
     */
    private function context(bool $includeJuly = false, bool $withIncomeAccounts = false): array
    {
        $tenantId = $this->createTenant();

        return $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $includeJuly, $withIncomeAccounts): array {
            $asset = $this->accountType($tenantId, 'ASSET', NormalBalance::Debit, StatementType::BalanceSheet);
            $equity = $this->accountType($tenantId, 'EQUITY', NormalBalance::Credit, StatementType::BalanceSheet);
            if ($withIncomeAccounts) {
                $revenue = $this->accountType($tenantId, 'REVENUE', NormalBalance::Credit, StatementType::IncomeStatement);
                $expense = $this->accountType($tenantId, 'EXPENSE', NormalBalance::Debit, StatementType::IncomeStatement);
                app(ChartOfAccountsService::class)->createAccount(new CreateAccountData(
                    tenantId: $tenantId,
                    accountTypeId: (int) $revenue->getKey(),
                    code: '4100',
                    name: 'Sales Revenue',
                    normalBalance: NormalBalance::Credit,
                ));
                app(ChartOfAccountsService::class)->createAccount(new CreateAccountData(
                    tenantId: $tenantId,
                    accountTypeId: (int) $expense->getKey(),
                    code: '5100',
                    name: 'Purchase Expense',
                    normalBalance: NormalBalance::Debit,
                ));
            }

            $cash = app(ChartOfAccountsService::class)->createAccount(new CreateAccountData(
                tenantId: $tenantId,
                accountTypeId: (int) $asset->getKey(),
                code: '1010',
                name: 'Cash',
                normalBalance: NormalBalance::Debit,
            ));
            $capital = app(ChartOfAccountsService::class)->createAccount(new CreateAccountData(
                tenantId: $tenantId,
                accountTypeId: (int) $equity->getKey(),
                code: '3000',
                name: 'Capital',
                normalBalance: NormalBalance::Credit,
            ));

            $year = FinanceFiscalYear::query()->create([
                'tenant_id' => $tenantId,
                'name' => 'FY 2026',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'status' => 'open',
            ]);
            $this->period($tenantId, (int) $year->getKey(), 6, '2026-06-01', '2026-06-30');
            if ($includeJuly) {
                $this->period($tenantId, (int) $year->getKey(), 7, '2026-07-01', '2026-07-31');
            }

            $this->profile($tenantId, 'direct_test', [
                'cash' => $cash,
                'capital' => $capital,
            ]);

            return [$tenantId, $cash, $capital];
        });
    }

    private function directRequest(int $tenantId, string $date): FinancePostingRequest
    {
        return new FinancePostingRequest(
            source: new PostingSourceData('test_source', 1, $tenantId, sourceModule: 'test'),
            postingDate: $date,
            lines: [
                new FinancePostingLine(null, 'Cash', debit: '100.000000', profileKey: 'cash'),
                new FinancePostingLine(null, 'Capital', credit: '100.000000', profileKey: 'capital'),
            ],
            postingProfileCode: 'direct_test',
        );
    }

    /**
     * @param  array<string, FinanceAccount>  $rules
     */
    private function profile(int $tenantId, string $code, array $rules): FinancePostingProfile
    {
        return $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $code, $rules): FinancePostingProfile {
            $profile = FinancePostingProfile::query()->create([
                'tenant_id' => $tenantId,
                'code' => $code,
                'name' => Str::headline($code),
                'is_active' => true,
            ]);

            foreach ($rules as $lineKey => $account) {
                $roleId = $this->accountRole($tenantId, $code.'_'.$lineKey, Str::headline((string) $lineKey));
                DB::table('finance_account_assignments')->insert([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => null,
                    'account_role_id' => $roleId,
                    'account_id' => (int) $account->getKey(),
                    'effective_from' => '2026-01-01',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                FinancePostingProfileRule::query()->create([
                    'tenant_id' => (int) $profile->tenant_id,
                    'posting_profile_id' => $profile->getKey(),
                    'line_key' => $lineKey,
                    'account_role_id' => $roleId,
                ]);
            }

            return $profile->refresh();
        });
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

    private function accountType(
        int $tenantId,
        string $code,
        NormalBalance $normalBalance,
        StatementType $statementType,
    ): FinanceAccountType {
        return FinanceAccountType::query()->create([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => Str::headline($code),
            'normal_balance' => $normalBalance->value,
            'statement_type' => $statementType->value,
            'is_active' => true,
        ]);
    }

    private function period(int $tenantId, int $yearId, int $number, string $start, string $end): void
    {
        FinanceFiscalPeriod::query()->create([
            'tenant_id' => $tenantId,
            'fiscal_year_id' => $yearId,
            'name' => 'Period '.$number,
            'period_number' => $number,
            'start_date' => $start,
            'end_date' => $end,
            'status' => 'open',
        ]);
    }

    private function createTenant(): int
    {
        $suffix = Str::upper(Str::random(6));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-FH-'.$suffix,
            'name' => 'Finance Hardening '.$suffix,
            'slug' => 'finance-hardening-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now()]);
    }

    private function organizationUnit(int $tenantId, string $code): int
    {
        return (int) \Tests\Support\OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Organization '.$code,
            'code' => $code,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
