<?php

declare(strict_types=1);

namespace Modules\Finance\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
use Modules\Finance\Services\AgingReportService;
use Modules\Finance\Services\BankReconciliationService;
use Modules\Finance\Services\BudgetService;
use Modules\Finance\Services\ChartOfAccountsService;
use Modules\Finance\Services\CurrencyRevaluationService;
use Tests\TestCase;

final class FinanceEnterpriseCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_ar_and_ap_aging_use_invoice_balances_as_source(): void
    {
        [$tenantId] = $this->context();

        $this->invoice($tenantId, 'AR-CURRENT', 'outbound', '2026-06-20', '2026-07-05', '100.000000');
        $this->invoice($tenantId, 'AR-30', 'outbound', '2026-06-01', '2026-06-15', '200.000000');
        $this->invoice($tenantId, 'AP-60', 'inbound', '2026-05-01', '2026-05-15', '300.000000');

        $service = app(AgingReportService::class);
        $ar = $service->receivables($tenantId, null, '2026-06-30');
        $ap = $service->payables($tenantId, null, '2026-06-30');

        $this->assertSame('100.000000', $ar['buckets']['current']);
        $this->assertSame('200.000000', $ar['buckets']['1_30']);
        $this->assertSame('300.000000', $ar['total']);
        $this->assertSame('300.000000', $ap['buckets']['31_60']);
        $this->assertSame('300.000000', $ap['total']);
    }

    public function test_currency_revaluation_posts_through_profile_mappings(): void
    {
        [$tenantId, $bank, , , $gain, $loss] = $this->context();
        $profile = $this->profile($tenantId, 'revaluation', [
            'ar_exposure' => $bank,
            'unrealized_gain' => $gain,
            'unrealized_loss' => $loss,
        ]);

        $result = app(CurrencyRevaluationService::class)->revalue(
            tenantId: $tenantId,
            organizationUnitId: null,
            exposureType: 'ar',
            sourceId: 77,
            postingDate: '2026-06-20',
            postingProfileCode: (string) $profile->code,
            exposures: [[
                'exposure_key' => 'ar_exposure',
                'carrying_amount' => '100.000000',
                'revalued_amount' => '125.000000',
                'description' => 'AR USD revaluation',
            ]],
        );

        $this->assertSame('posted', $result->status);
        $this->assertDatabaseHas('finance_journal_entries', [
            'id' => $result->journalId,
            'source_module' => 'finance',
            'source_type' => 'currency_revaluation_ar',
            'source_id' => 77,
            'posting_profile_id' => $profile->getKey(),
        ]);
        $this->assertDatabaseHas('finance_ledger_entries', [
            'journal_entry_id' => $result->journalId,
            'account_id' => $gain->getKey(),
            'credit' => '25.000000',
        ]);
    }

    public function test_bank_reconciliation_matches_statement_lines_to_existing_ledger_only(): void
    {
        [$tenantId, $bank, $capital] = $this->context();
        $posted = app(FinancePostingInterface::class)->post(new FinancePostingRequest(
            source: new PostingSourceData('bank_deposit', 10, $tenantId, sourceModule: 'payment', sourceNumber: 'PAY-10', sourceDate: '2026-06-18'),
            postingDate: '2026-06-18',
            lines: [
                new FinancePostingLine((string) $bank->code, (string) $bank->name, debit: '100.000000'),
                new FinancePostingLine((string) $capital->code, (string) $capital->name, credit: '100.000000'),
            ],
        ));
        $ledger = FinanceLedgerEntry::query()
            ->where('journal_entry_id', $posted->journalId)
            ->where('account_id', $bank->getKey())
            ->firstOrFail();

        $service = app(BankReconciliationService::class);
        $reconciliation = $service->create(
            tenantId: $tenantId,
            organizationUnitId: null,
            bankAccountId: (int) $bank->getKey(),
            statementReference: 'STM-001',
            statementDate: '2026-06-30',
            openingBalance: '0.000000',
            closingBalance: '100.000000',
            statementLines: [[
                'statement_date' => '2026-06-18',
                'reference' => 'PAY-10',
                'debit' => '100.000000',
                'credit' => '0.000000',
            ]],
        );

        $line = $reconciliation->statementLines()->firstOrFail();
        $matched = $service->matchLine($line, (int) $ledger->getKey());
        $completed = $service->complete($reconciliation->refresh());

        $this->assertSame('matched', (string) $matched->status);
        $this->assertSame('completed', (string) $completed->status);
        $this->assertSame(2, FinanceLedgerEntry::query()->where('journal_entry_id', $posted->journalId)->count());
    }

    public function test_budget_actuals_are_calculated_from_ledger(): void
    {
        [$tenantId, $bank, $capital] = $this->context();
        app(FinancePostingInterface::class)->post(new FinancePostingRequest(
            source: new PostingSourceData('budget_actual', 1, $tenantId, sourceModule: 'finance'),
            postingDate: '2026-06-10',
            lines: [
                new FinancePostingLine((string) $bank->code, (string) $bank->name, debit: '100.000000'),
                new FinancePostingLine((string) $capital->code, (string) $capital->name, credit: '100.000000'),
            ],
        ));

        $budget = app(BudgetService::class)->save(
            tenantId: $tenantId,
            organizationUnitId: null,
            budgetYear: 2026,
            name: 'FY 2026',
            lines: [[
                'account_id' => $bank->getKey(),
                'budget_month' => 6,
                'amount' => '120.000000',
            ]],
        );
        $actuals = app(BudgetService::class)->actualVsBudget($budget);

        $this->assertSame('120.000000', $actuals['total_budget']);
        $this->assertSame('100.000000', $actuals['total_actual']);
        $this->assertSame('-20.000000', $actuals['variance']);
    }

    /**
     * @return array{0: int, 1: FinanceAccount, 2: FinanceAccount, 3: FinanceAccount, 4: FinanceAccount, 5: FinanceAccount, 6: FinanceAccount}
     */
    private function context(): array
    {
        $tenantId = $this->tenant();
        $asset = $this->accountType($tenantId, 'ASSET', NormalBalance::Debit, StatementType::BalanceSheet);
        $equity = $this->accountType($tenantId, 'EQUITY', NormalBalance::Credit, StatementType::BalanceSheet);
        $revenue = $this->accountType($tenantId, 'REVENUE', NormalBalance::Credit, StatementType::IncomeStatement);
        $expense = $this->accountType($tenantId, 'EXPENSE', NormalBalance::Debit, StatementType::IncomeStatement);
        $accounts = app(ChartOfAccountsService::class);

        $bank = $accounts->createAccount(new CreateAccountData(
            tenantId: $tenantId,
            accountTypeId: (int) $asset->getKey(),
            code: '1010',
            name: 'Bank',
            normalBalance: NormalBalance::Debit,
            isBankAccount: true,
        ));
        $capital = $accounts->createAccount(new CreateAccountData(
            tenantId: $tenantId,
            accountTypeId: (int) $equity->getKey(),
            code: '3000',
            name: 'Capital',
            normalBalance: NormalBalance::Credit,
        ));
        $receivable = $accounts->createAccount(new CreateAccountData(
            tenantId: $tenantId,
            accountTypeId: (int) $asset->getKey(),
            code: '1100',
            name: 'Accounts Receivable',
            normalBalance: NormalBalance::Debit,
        ));
        $payable = $accounts->createAccount(new CreateAccountData(
            tenantId: $tenantId,
            accountTypeId: (int) $equity->getKey(),
            code: '2100',
            name: 'Accounts Payable',
            normalBalance: NormalBalance::Credit,
        ));
        $gain = $accounts->createAccount(new CreateAccountData(
            tenantId: $tenantId,
            accountTypeId: (int) $revenue->getKey(),
            code: '7100',
            name: 'Unrealized FX Gain',
            normalBalance: NormalBalance::Credit,
        ));
        $loss = $accounts->createAccount(new CreateAccountData(
            tenantId: $tenantId,
            accountTypeId: (int) $expense->getKey(),
            code: '8100',
            name: 'Unrealized FX Loss',
            normalBalance: NormalBalance::Debit,
        ));

        $year = FinanceFiscalYear::query()->create([
            'tenant_id' => $tenantId,
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);
        FinanceFiscalPeriod::query()->create([
            'tenant_id' => $tenantId,
            'fiscal_year_id' => $year->getKey(),
            'name' => 'June 2026',
            'period_number' => 6,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'status' => 'open',
        ]);

        return [$tenantId, $bank, $capital, $receivable, $payable, $gain, $loss];
    }

    /**
     * @param  array<string, FinanceAccount>  $rules
     */
    private function profile(int $tenantId, string $code, array $rules): FinancePostingProfile
    {
        $profile = FinancePostingProfile::query()->create([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => Str::headline($code),
            'is_active' => true,
        ]);
        foreach ($rules as $key => $account) {
            FinancePostingProfileRule::query()->create([
                'posting_profile_id' => $profile->getKey(),
                'line_key' => $key,
                'account_id' => $account->getKey(),
            ]);
        }

        return $profile->refresh();
    }

    private function invoice(
        int $tenantId,
        string $number,
        string $direction,
        string $invoiceDate,
        string $dueDate,
        string $remaining,
    ): void {
        $invoiceId = (int) DB::table('invoices')->insertGetId([
            'tenant_id' => $tenantId,
            'invoice_number' => $number,
            'invoice_type' => $direction === 'outbound' ? 'sales' : 'purchase',
            'direction' => $direction,
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'status' => 'posted',
            'grand_total' => $remaining,
            'balance_due' => $remaining,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('invoice_balances')->insert([
            'tenant_id' => $tenantId,
            'invoice_id' => $invoiceId,
            'invoice_total' => $remaining,
            'paid_amount' => '0.000000',
            'credit_allocated_amount' => '0.000000',
            'debit_allocated_amount' => '0.000000',
            'refunded_amount' => '0.000000',
            'remaining_amount' => $remaining,
            'status' => 'unpaid',
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

    private function tenant(): int
    {
        $suffix = Str::upper(Str::random(6));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-FEC-'.$suffix,
            'name' => 'Finance Enterprise Core '.$suffix,
            'slug' => 'finance-enterprise-core-'.Str::lower($suffix),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()]);
    }
}
