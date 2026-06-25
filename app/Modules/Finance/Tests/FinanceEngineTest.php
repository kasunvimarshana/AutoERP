<?php

declare(strict_types=1);

namespace Modules\Finance\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Finance\DTOs\CreateAccountData;
use Modules\Finance\DTOs\CreateJournalEntryData;
use Modules\Finance\DTOs\JournalLineData;
use Modules\Finance\Enums\FiscalPeriodStatus;
use Modules\Finance\Enums\JournalStatus;
use Modules\Finance\Enums\JournalType;
use Modules\Finance\Enums\NormalBalance;
use Modules\Finance\Enums\StatementType;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceAccountType;
use Modules\Finance\Models\FinanceFiscalPeriod;
use Modules\Finance\Models\FinanceFiscalYear;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Services\ChartOfAccountsService;
use Modules\Finance\Services\JournalEntryCreationService;
use Modules\Finance\Services\JournalPostingService;
use Modules\Finance\Services\JournalReversalService;
use Modules\Finance\Services\TrialBalanceService;
use Tests\TestCase;

final class FinanceEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_accounts_and_parent_child_relationships(): void
    {
        $tenantId = $this->createTenant();
        $assetType = $this->createAccountType($tenantId, 'ASSET', NormalBalance::Debit);
        $accounts = app(ChartOfAccountsService::class);

        $parent = $accounts->createAccount(new CreateAccountData(
            tenantId: $tenantId,
            accountTypeId: (int) $assetType->getKey(),
            code: '1000',
            name: 'Cash and Bank',
            normalBalance: NormalBalance::Debit,
            isControlAccount: true,
            isPostingAccount: false,
        ));

        $child = $accounts->createAccount(new CreateAccountData(
            tenantId: $tenantId,
            accountTypeId: (int) $assetType->getKey(),
            code: '1010',
            name: 'Cash',
            normalBalance: NormalBalance::Debit,
            parentId: (int) $parent->getKey(),
            isCashAccount: true,
        ));

        $this->assertSame((int) $parent->getKey(), (int) $child->parent->getKey());
        $this->assertTrue($parent->children()->whereKey($child->getKey())->exists());
    }

    public function test_it_posts_balanced_journal_creates_ledger_entries_and_updates_balances(): void
    {
        [$tenantId, $cash, $capital, $period] = $this->chartWithOpenPeriod();

        $journal = $this->createCashCapitalJournal($tenantId, $cash, $capital, $period);
        $result = app(JournalPostingService::class)->post($journal, postedBy: 77);

        $this->assertSame(JournalStatus::Posted, $result->status);
        $this->assertSame('100000.000000', $result->totalDebit);
        $this->assertSame('100000.000000', $result->totalCredit);
        $this->assertSame(2, $result->ledgerEntryCount);

        $journal->refresh();
        $this->assertSame(JournalStatus::Posted, $journal->status);
        $this->assertSame(2, $journal->ledgerEntries()->count());
        $this->assertSame('100000.000000', (string) $cash->refresh()->current_balance);
        $this->assertSame('100000.000000', (string) $capital->refresh()->current_balance);

        $cashBalance = $cash->balances()->where('fiscal_period_id', $period->getKey())->firstOrFail();
        $capitalBalance = $capital->balances()->where('fiscal_period_id', $period->getKey())->firstOrFail();

        $this->assertSame('100000.000000', (string) $cashBalance->closing_debit);
        $this->assertSame('100000.000000', (string) $capitalBalance->closing_credit);

        $trialBalance = app(TrialBalanceService::class)->calculate($tenantId, null, (int) $period->getKey());

        $this->assertTrue($trialBalance->isBalanced);
        $this->assertSame('100000.000000', $trialBalance->totalDebit);
        $this->assertSame('100000.000000', $trialBalance->totalCredit);
    }

    public function test_it_rejects_unbalanced_journals(): void
    {
        [$tenantId, $cash, $capital, $period] = $this->chartWithOpenPeriod();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Journal must be balanced before it can be created.');

        app(JournalEntryCreationService::class)->create(new CreateJournalEntryData(
            tenantId: $tenantId,
            journalDate: '2026-06-06',
            journalNumber: 'JE-BAD',
            fiscalYearId: $period->fiscal_year_id,
            fiscalPeriodId: (int) $period->getKey(),
            lines: [
                new JournalLineData(accountId: (int) $cash->getKey(), lineNumber: 1, debit: '100.000000'),
                new JournalLineData(accountId: (int) $capital->getKey(), lineNumber: 2, credit: '90.000000'),
            ],
        ));
    }

    public function test_it_rejects_posting_into_closed_period(): void
    {
        [$tenantId, $cash, $capital, $period] = $this->chartWithOpenPeriod();
        $period->forceFill(['status' => FiscalPeriodStatus::Closed->value])->save();

        $journal = $this->createCashCapitalJournal($tenantId, $cash, $capital, $period, 'JE-CLOSED');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot post into a closed or locked fiscal period.');

        app(JournalPostingService::class)->post($journal);
    }

    public function test_it_reverses_posted_journal_with_immutable_opposite_entry(): void
    {
        [$tenantId, $cash, $capital, $period] = $this->chartWithOpenPeriod();
        $journal = $this->createCashCapitalJournal($tenantId, $cash, $capital, $period, 'JE-REV');
        app(JournalPostingService::class)->post($journal);

        $reversal = app(JournalReversalService::class)->reverse($journal->refresh(), '2026-06-07', reversedBy: 88);

        $this->assertSame(JournalType::Reversal, $reversal->journal_type);
        $this->assertSame(JournalStatus::Posted, $reversal->status);
        $this->assertSame(JournalStatus::Reversed, $journal->refresh()->status);
        $this->assertSame('0.000000', (string) $cash->refresh()->current_balance);
        $this->assertSame('0.000000', (string) $capital->refresh()->current_balance);
        $this->assertSame(2, $reversal->ledgerEntries()->count());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only posted journals can be reversed.');

        app(JournalReversalService::class)->reverse($journal->refresh(), '2026-06-08');
    }

    public function test_it_prevents_cross_organization_posting(): void
    {
        $tenantId = $this->createTenant();
        $orgOne = $this->createOrganizationUnit($tenantId, 'ORG-FIN-A');
        $orgTwo = $this->createOrganizationUnit($tenantId, 'ORG-FIN-B');
        $assetType = $this->createAccountType($tenantId, 'ASSET-ORG', NormalBalance::Debit);

        $cash = app(ChartOfAccountsService::class)->createAccount(new CreateAccountData(
            tenantId: $tenantId,
            organizationUnitId: $orgOne,
            accountTypeId: (int) $assetType->getKey(),
            code: '1010-ORG',
            name: 'Org Cash',
            normalBalance: NormalBalance::Debit,
        ));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Finance posting organization unit scope mismatch.');

        app(JournalEntryCreationService::class)->create(new CreateJournalEntryData(
            tenantId: $tenantId,
            organizationUnitId: $orgTwo,
            journalDate: '2026-06-06',
            journalNumber: 'JE-ORG',
            lines: [
                new JournalLineData(accountId: (int) $cash->getKey(), lineNumber: 1, debit: '100.000000'),
            ],
        ));
    }

    public function test_it_prevents_cross_tenant_posting(): void
    {
        [$tenantId, $cash] = $this->chartWithOpenPeriod();
        $otherTenantId = $this->createTenant('OTHER-FIN');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Finance posting tenant scope mismatch.');

        app(JournalEntryCreationService::class)->create(new CreateJournalEntryData(
            tenantId: $otherTenantId,
            journalDate: '2026-06-06',
            journalNumber: 'JE-TENANT',
            lines: [
                new JournalLineData(accountId: (int) $cash->getKey(), lineNumber: 1, debit: '100.000000'),
            ],
        ));
    }

    /**
     * @return array{0: int, 1: FinanceAccount, 2: FinanceAccount, 3: FinanceFiscalPeriod}
     */
    private function chartWithOpenPeriod(): array
    {
        $tenantId = $this->createTenant();
        $assetType = $this->createAccountType($tenantId, 'ASSET', NormalBalance::Debit);
        $equityType = $this->createAccountType($tenantId, 'EQUITY', NormalBalance::Credit);

        $cash = app(ChartOfAccountsService::class)->createAccount(new CreateAccountData(
            tenantId: $tenantId,
            accountTypeId: (int) $assetType->getKey(),
            code: '1010',
            name: 'Cash',
            normalBalance: NormalBalance::Debit,
            isCashAccount: true,
        ));

        $capital = app(ChartOfAccountsService::class)->createAccount(new CreateAccountData(
            tenantId: $tenantId,
            accountTypeId: (int) $equityType->getKey(),
            code: '3000',
            name: 'Capital',
            normalBalance: NormalBalance::Credit,
        ));

        $year = FinanceFiscalYear::query()->create([
            'tenant_id' => $tenantId,
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => FiscalPeriodStatus::Open->value,
        ]);

        $period = FinanceFiscalPeriod::query()->create([
            'tenant_id' => $tenantId,
            'fiscal_year_id' => $year->getKey(),
            'name' => 'June 2026',
            'period_number' => 6,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'status' => FiscalPeriodStatus::Open->value,
        ]);

        return [$tenantId, $cash, $capital, $period];
    }

    private function createCashCapitalJournal(
        int $tenantId,
        FinanceAccount $cash,
        FinanceAccount $capital,
        FinanceFiscalPeriod $period,
        string $journalNumber = 'JE-001',
    ): FinanceJournalEntry {
        return app(JournalEntryCreationService::class)->create(new CreateJournalEntryData(
            tenantId: $tenantId,
            journalDate: '2026-06-06',
            journalNumber: $journalNumber,
            fiscalYearId: $period->fiscal_year_id,
            fiscalPeriodId: (int) $period->getKey(),
            journalType: JournalType::Opening,
            description: 'Opening capital contribution',
            lines: [
                new JournalLineData(
                    accountId: (int) $cash->getKey(),
                    lineNumber: 1,
                    debit: '100000.000000',
                    description: 'Cash received',
                ),
                new JournalLineData(
                    accountId: (int) $capital->getKey(),
                    lineNumber: 2,
                    credit: '100000.000000',
                    description: 'Owner capital',
                ),
            ],
        ));
    }

    private function createAccountType(int $tenantId, string $code, NormalBalance $normalBalance): FinanceAccountType
    {
        return FinanceAccountType::query()->create([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => Str::headline($code),
            'normal_balance' => $normalBalance->value,
            'statement_type' => $normalBalance === NormalBalance::Debit
                ? StatementType::BalanceSheet->value
                : StatementType::BalanceSheet->value,
            'is_active' => true,
        ]);
    }

    private function createTenant(string $suffix = ''): int
    {
        $suffix = $suffix !== '' ? $suffix : Str::upper(Str::random(4));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-FIN-'.$suffix,
            'name' => 'Finance Tenant '.$suffix,
            'slug' => 'finance-tenant-'.Str::lower($suffix),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()]);
    }

    private function createOrganizationUnit(int $tenantId, string $code): int
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
