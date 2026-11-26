<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Finance\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\DTOs\CreateAccountData;
use Modules\Finance\DTOs\CreateJournalEntryData;
use Modules\Finance\DTOs\FinancePostingLine;
use Modules\Finance\DTOs\FinancePostingRequest;
use Modules\Finance\DTOs\JournalLineData;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Finance\Enums\JournalStatus;
use Modules\Finance\Enums\JournalType;
use Modules\Finance\Enums\NormalBalance;
use Modules\Finance\Enums\StatementType;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceAccountType;
use Modules\Finance\Models\FinanceBankReconciliation;
use Modules\Finance\Models\FinanceBankStatementLine;
use Modules\Finance\Models\FinanceDimension;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Models\FinanceLedgerEntry;
use Modules\Finance\Models\FinancePostingProfile;
use Modules\Finance\Models\FinancePostingProfileRule;
use Modules\Finance\Services\BudgetService;
use Modules\Finance\Services\CashFlowReportService;
use Modules\Finance\Services\ChartOfAccountsService;
use Modules\Finance\Services\FinanceStatementService;
use Modules\Finance\Services\GeneralLedgerReportService;
use Modules\Finance\Services\JournalEntryCreationService;
use Modules\Finance\Services\JournalPostingService;
use Modules\Finance\Services\JournalReversalService;
use Modules\Finance\Services\PostingProfileService;
use Modules\Finance\Services\TrialBalanceService;
use Tests\Support\OrganizationUnitFixture;
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

    public function test_ledger_entries_are_immutable_and_reversal_uses_new_date_and_reason(): void
    {
        [$tenantId] = $this->context();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $posting = app(FinancePostingInterface::class);
            $posted = $posting->post($this->directRequest($tenantId, '2026-06-30'));
            $reversal = $posting->reverseJournal($posted->journalId, '2026-07-01', reason: 'Correct source date');

            $this->assertDatabaseHas('finance_journal_entries', [
                'id' => $reversal->journalId,
                'reversal_of_id' => $posted->journalId,
                'reversal_reason' => 'Correct source date',
            ]);
            $this->assertStringStartsWith(
                '2026-07-01',
                (string) DB::table('finance_journal_entries')
                    ->where('id', $reversal->journalId)
                    ->value('journal_date'),
            );

            $ledger = FinanceLedgerEntry::query()->where('journal_entry_id', $posted->journalId)->firstOrFail();
            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('Ledger entries are immutable.');
            $ledger->forceFill(['debit' => '999.000000'])->save();
        });
    }

    public function test_posted_replay_is_idempotent_but_reversed_finance_journal_cannot_post_again(): void
    {
        [$tenantId] = $this->context();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $posting = app(FinancePostingInterface::class);
            $request = $this->directRequest($tenantId, '2026-06-30');
            $posted = $posting->post($request);

            $same = $posting->post($request);
            $this->assertSame($posted->journalId, $same->journalId);
            $this->assertSame('posted', $same->status);
            $this->assertDatabaseCount('finance_ledger_entries', 2);

            $posting->reverseJournal($posted->journalId, '2026-07-01', reason: 'Correct source document');
            $this->assertDatabaseCount('finance_ledger_entries', 4);

            foreach ([
                [fn () => $posting->postJournal($posted->journalId),
                    'Only draft journals can be posted. Reversed journals cannot be reposted.'],
                [fn () => $posting->createDraftJournal($request),
                    'Reversed Finance posting sources cannot be replayed.'],
                [fn () => $posting->post($request),
                    'Reversed Finance posting sources cannot be replayed.'],
                [fn () => app(JournalPostingService::class)->post(
                    FinanceJournalEntry::query()->findOrFail($posted->journalId),
                ), 'Only draft journals can be posted. Reversed journals cannot be reposted.'],
            ] as [$repost, $expectedMessage]) {
                try {
                    $repost();
                    $this->fail('Reversed source was reported as a successful posting replay.');
                } catch (InvalidArgumentException $exception) {
                    $this->assertSame($expectedMessage, $exception->getMessage());
                }
            }

            $this->assertDatabaseHas('finance_journal_entries', [
                'id' => $posted->journalId,
                'status' => 'reversed',
            ]);
            $this->assertDatabaseCount('finance_ledger_entries', 4);
        });
    }

    public function test_unbalanced_source_lines_cannot_hide_behind_balanced_journal_header(): void
    {
        [$tenantId] = $this->context();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $posting = app(FinancePostingInterface::class);
            $draft = $posting->createDraftJournal($this->directRequest($tenantId, '2026-06-30'));

            // Simulate a stale imported header: both header sides still balance
            // each other, but neither matches the underlying journal lines.
            FinanceJournalEntry::query()->findOrFail($draft->journalId)
                ->forceFill(['total_debit' => '90.000000', 'total_credit' => '90.000000'])
                ->save();

            try {
                $posting->postJournal($draft->journalId);
                $this->fail('A journal with inconsistent header and line totals was posted.');
            } catch (InvalidArgumentException $exception) {
                $this->assertSame('Journal header totals do not match its lines.', $exception->getMessage());
            }

            $this->assertDatabaseHas('finance_journal_entries', [
                'id' => $draft->journalId,
                'status' => 'draft',
            ]);
            $this->assertDatabaseCount('finance_ledger_entries', 0);
        });
    }

    public function test_draft_journal_without_lines_cannot_post_zero_entry_ledger(): void
    {
        [$tenantId] = $this->context();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $posting = app(FinancePostingInterface::class);
            $draft = $posting->createDraftJournal($this->directRequest($tenantId, '2026-06-30'));

            // An incomplete historical import must never become a posted
            // journal merely because the stored header still balances.
            DB::table('finance_journal_lines')
                ->where('journal_entry_id', $draft->journalId)
                ->delete();

            try {
                $posting->postJournal($draft->journalId);
                $this->fail('A journal with no lines was posted.');
            } catch (InvalidArgumentException $exception) {
                $this->assertSame('Journal cannot be posted without lines.', $exception->getMessage());
            }

            $this->assertDatabaseHas('finance_journal_entries', [
                'id' => $draft->journalId,
                'status' => 'draft',
            ]);
            $this->assertDatabaseCount('finance_ledger_entries', 0);
        });
    }

    public function test_posted_journal_cannot_be_replayed_or_reversed_with_missing_ledger(): void
    {
        [$tenantId] = $this->context();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $posting = app(FinancePostingInterface::class);
            $request = $this->directRequest($tenantId, '2026-06-30');
            $posted = $posting->post($request);

            $ledgerId = DB::table('finance_ledger_entries')
                ->where('journal_entry_id', $posted->journalId)
                ->orderBy('id')
                ->value('id');
            $this->assertNotNull($ledgerId);
            // Deliberate untrusted historical import corruption in disposable
            // test DB: bypass the immutable Eloquent ledger guard.
            DB::table('finance_ledger_entries')->where('id', $ledgerId)->delete();

            foreach ([
                fn () => $posting->postJournal($posted->journalId),
                fn () => $posting->createDraftJournal($request),
                fn () => $posting->post($request),
                fn () => $posting->reverseJournal(
                    $posted->journalId, '2026-07-01', reason: 'Correct document',
                ),
            ] as $operation) {
                try {
                    $operation();
                    $this->fail('Corrupt posted Finance journal was accepted.');
                } catch (InvalidArgumentException $exception) {
                    $this->assertSame(
                        'Posted Finance journal ledger does not match its journal lines.',
                        $exception->getMessage(),
                    );
                }
            }

            $this->assertDatabaseHas('finance_journal_entries', [
                'id' => $posted->journalId,
                'status' => 'posted',
            ]);
            $this->assertDatabaseCount('finance_ledger_entries', 1);
            $this->assertDatabaseCount('finance_journal_entries', 1);
        });
    }

    public function test_posted_journal_cannot_be_replayed_with_modified_ledger_amount(): void
    {
        [$tenantId] = $this->context();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $posting = app(FinancePostingInterface::class);
            $posted = $posting->post($this->directRequest($tenantId, '2026-06-30'));
            $ledgerId = DB::table('finance_ledger_entries')
                ->where('journal_entry_id', $posted->journalId)
                ->where('debit', '>', 0)
                ->value('id');
            $this->assertNotNull($ledgerId);

            DB::table('finance_ledger_entries')->where('id', $ledgerId)
                ->update(['debit' => '99.000000']);

            try {
                $posting->postJournal($posted->journalId);
                $this->fail('Finance accepted a posted ledger with altered monetary evidence.');
            } catch (InvalidArgumentException $exception) {
                $this->assertSame(
                    'Posted Finance journal ledger does not match its journal lines.',
                    $exception->getMessage(),
                );
            }

            $this->assertDatabaseCount('finance_ledger_entries', 2);
            $this->assertDatabaseCount('finance_journal_entries', 1);
        });
    }

    public function test_source_owned_journal_cannot_be_reversed_directly_from_finance(): void
    {
        [$tenantId] = $this->context();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $posting = app(FinancePostingInterface::class);
            $posted = $posting->post(new FinancePostingRequest(
                source: new PostingSourceData('invoice', 77, $tenantId, sourceModule: 'invoice'),
                postingDate: '2026-06-30',
                lines: [
                    new FinancePostingLine(null, 'Cash', debit: '100.000000', profileKey: 'cash'),
                    new FinancePostingLine(null, 'Capital', credit: '100.000000', profileKey: 'capital'),
                ],
                postingProfileCode: 'direct_test',
            ));
            $journal = FinanceJournalEntry::query()->findOrFail($posted->journalId);

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Source-owned journals must be reversed through the module that owns the source document.');

            app(JournalReversalService::class)->reverse($journal, '2026-07-01');
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

    public function test_inactive_posting_profile_rejected_on_journal_creation(): void
    {
        [$tenantId, $cash, $capital] = $this->context();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $cash, $capital): void {
            $profile = $this->profile($tenantId, 'inactive_test', [
                'cash' => $cash,
                'capital' => $capital,
            ]);
            $profile->forceFill(['is_active' => false])->save();

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Posting profile is inactive.');

            app(JournalEntryCreationService::class)->create(new CreateJournalEntryData(
                tenantId: $tenantId,
                journalDate: '2026-06-15',
                journalType: JournalType::General,
                organizationUnitId: null,
                source: null,
                description: 'Test inactive profile',
                lines: [
                    new JournalLineData((int) $cash->getKey(), 1, debit: '100.000000', credit: '0.000000'),
                    new JournalLineData((int) $capital->getKey(), 2, debit: '0.000000', credit: '100.000000'),
                ],
                postingProfileId: (int) $profile->getKey(),
            ));
        });
    }

    public function test_reversal_succeeds_even_when_original_posting_profile_is_deactivated(): void
    {
        [$tenantId, $cash, $capital] = $this->context();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $cash, $capital): void {
            $profile = $this->profile($tenantId, 'temp_sales_profile', [
                'cash' => $cash,
                'capital' => $capital,
            ]);

            $journal = app(JournalEntryCreationService::class)->create(new CreateJournalEntryData(
                tenantId: $tenantId,
                journalNumber: 'JE-TEMP-01',
                journalDate: '2026-06-15',
                journalType: JournalType::General,
                organizationUnitId: null,
                source: null,
                description: 'Original Sales Journal',
                lines: [
                    new JournalLineData((int) $cash->getKey(), 1, debit: '300.000000', credit: '0.000000'),
                    new JournalLineData((int) $capital->getKey(), 2, debit: '0.000000', credit: '300.000000'),
                ],
                postingProfileId: $profile->getKey(),
            ));
            app(JournalPostingService::class)->post($journal);

            // Now deactivate the profile
            $profile->forceFill(['is_active' => false])->save();
            $this->assertFalse((bool) $profile->refresh()->is_active);

            // Reversing the historical journal must succeed despite the deactivated profile
            $reversal = app(JournalReversalService::class)->reverse(
                $journal->refresh(),
                reversalDate: '2026-06-25',
                reason: 'Customer cancelled transaction',
            );

            $this->assertSame(JournalType::Reversal, $reversal->journal_type);
            $this->assertSame(JournalStatus::Posted, $reversal->status);
            $this->assertSame('300.000000', (string) $reversal->total_debit);
            $this->assertSame(JournalStatus::Reversed, $journal->refresh()->status);
        });
    }

    public function test_reversal_journal_cannot_be_reversed(): void
    {
        [$tenantId, $cash, $capital] = $this->context();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $cash, $capital): void {
            $journal = app(JournalEntryCreationService::class)->create(new CreateJournalEntryData(
                tenantId: $tenantId,
                journalDate: '2026-06-15',
                journalType: JournalType::General,
                organizationUnitId: null,
                source: null,
                description: 'Original journal',
                lines: [
                    new JournalLineData((int) $cash->getKey(), 1, debit: '100.000000', credit: '0.000000'),
                    new JournalLineData((int) $capital->getKey(), 2, debit: '0.000000', credit: '100.000000'),
                ],
            ));
            $posted = app(JournalPostingService::class)->post($journal);
            $reversal = app(JournalReversalService::class)->reverse($journal->refresh(), '2026-06-16', reason: 'Correction');

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Reversal journals cannot be reversed.');

            app(JournalReversalService::class)->reverse($reversal, '2026-06-17', reason: 'Attempt reverse reversal');
        });
    }

    public function test_cancelled_journal_lifecycle_immutability(): void
    {
        [$tenantId, $cash, $capital] = $this->context();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $cash, $capital): void {
            $journal = app(JournalEntryCreationService::class)->create(new CreateJournalEntryData(
                tenantId: $tenantId,
                journalDate: '2026-06-15',
                journalType: JournalType::General,
                organizationUnitId: null,
                source: null,
                description: 'Draft journal',
                lines: [
                    new JournalLineData((int) $cash->getKey(), 1, debit: '50.000000', credit: '0.000000'),
                    new JournalLineData((int) $capital->getKey(), 2, debit: '0.000000', credit: '50.000000'),
                ],
            ));

            $journal->status = JournalStatus::Cancelled;
            $journal->save();

            try {
                $journal->description = 'Updated description';
                $journal->save();
                $this->fail('Expected LogicException on updating cancelled journal');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Cancelled journals are immutable', $e->getMessage());
            }

            try {
                $journal->delete();
                $this->fail('Expected LogicException on deleting cancelled journal');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Only draft journals can be deleted', $e->getMessage());
            }
        });
    }

    public function test_completed_bank_reconciliation_cannot_be_deleted(): void
    {
        [$tenantId, $cash] = $this->context();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $cash): void {
            $cash->forceFill(['is_bank_account' => true])->save();

            $recon = FinanceBankReconciliation::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'bank_account_id' => $cash->getKey(),
                'statement_reference' => 'STMT-999',
                'statement_date' => '2026-06-30',
                'status' => 'completed',
                'opening_balance' => '0.000000',
                'closing_balance' => '0.000000',
            ]);

            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('Completed bank reconciliations cannot be deleted.');

            $recon->delete();
        });
    }

    public function test_completed_bank_reconciliation_and_statement_lines_are_immutable(): void
    {
        [$tenantId, $cash] = $this->context();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $cash): void {
            $cash->forceFill(['is_bank_account' => true])->save();

            $recon = FinanceBankReconciliation::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'bank_account_id' => $cash->getKey(),
                'statement_reference' => 'STMT-IMMUTABLE-01',
                'statement_date' => '2026-06-30',
                'status' => 'draft',
                'opening_balance' => '100.000000',
                'closing_balance' => '100.000000',
            ]);

            $line = FinanceBankStatementLine::query()->create([
                'reconciliation_id' => $recon->getKey(),
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'bank_account_id' => $cash->getKey(),
                'statement_date' => '2026-06-30',
                'reference' => 'REF-01',
                'description' => 'Test deposit',
                'debit' => '50.000000',
                'credit' => '0.000000',
                'status' => 'unmatched',
            ]);

            $recon->forceFill(['status' => 'completed'])->save();

            try {
                $recon->opening_balance = '999.000000';
                $recon->save();
                $this->fail('Expected LogicException on updating completed reconciliation');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Completed bank reconciliations are immutable', $e->getMessage());
            }

            try {
                $line->description = 'Tampered description';
                $line->save();
                $this->fail('Expected LogicException on updating statement line in completed reconciliation');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Statement lines belonging to completed bank reconciliations cannot be updated', $e->getMessage());
            }

            try {
                $line->delete();
                $this->fail('Expected LogicException on deleting statement line in completed reconciliation');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Statement lines belonging to completed bank reconciliations cannot be deleted', $e->getMessage());
            }
        });
    }

    public function test_organization_unit_trial_balance_and_fallback_accounts(): void
    {
        [$tenantId, $cash, $capital] = $this->context();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $cash, $capital): void {
            $orgUnitId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'code' => 'OU_TEST_01',
                'name' => 'Test Branch',
            ]);

            $journal = app(JournalEntryCreationService::class)->create(new CreateJournalEntryData(
                tenantId: $tenantId,
                journalNumber: 'JE-BRANCH-01',
                journalDate: '2026-06-20',
                journalType: JournalType::General,
                organizationUnitId: $orgUnitId,
                source: null,
                description: 'Branch Journal',
                lines: [
                    new JournalLineData((int) $cash->getKey(), 1, debit: '200.000000', credit: '0.000000'),
                    new JournalLineData((int) $capital->getKey(), 2, debit: '0.000000', credit: '200.000000'),
                ],
            ));
            app(JournalPostingService::class)->post($journal);

            $rootJournal = app(JournalEntryCreationService::class)->create(new CreateJournalEntryData(
                tenantId: $tenantId,
                journalNumber: 'JE-ROOT-01',
                journalDate: '2026-06-21',
                journalType: JournalType::General,
                organizationUnitId: null,
                source: null,
                description: 'Root Journal',
                lines: [
                    new JournalLineData((int) $cash->getKey(), 1, debit: '500.000000', credit: '0.000000'),
                    new JournalLineData((int) $capital->getKey(), 2, debit: '0.000000', credit: '500.000000'),
                ],
            ));
            app(JournalPostingService::class)->post($rootJournal);

            $tbService = app(TrialBalanceService::class);
            $branchTb = $tbService->calculate($tenantId, $orgUnitId);

            $this->assertTrue($branchTb->isBalanced);
            $this->assertSame('200.000000', $branchTb->totalDebit);
            $this->assertSame('200.000000', $branchTb->totalCredit);

            $statementService = app(FinanceStatementService::class);
            $branchBs = $statementService->balanceSheet($tenantId, $orgUnitId, '2026-06-30');
            $this->assertSame('200.000000', $branchBs['total_assets']);
        });
    }

    public function test_tenant_wide_reports_include_branch_accounts_and_entries(): void
    {
        [$tenantId, $cash] = $this->context();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $cash): void {
            $branchId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'code' => 'OU_BRANCH_X',
                'name' => 'Branch X',
            ]);

            $cash->forceFill(['is_cash_account' => true])->save();

            $assetType = FinanceAccountType::query()->where('tenant_id', $tenantId)->where('code', 'ASSET')->firstOrFail();
            $branchPettyCash = app(ChartOfAccountsService::class)->createAccount(new CreateAccountData(
                tenantId: $tenantId,
                accountTypeId: (int) $assetType->getKey(),
                code: '1015-BX',
                name: 'Branch X Petty Cash',
                normalBalance: NormalBalance::Debit,
                organizationUnitId: $branchId,
                isCashAccount: true,
            ));

            // Branch posts transfer from tenant cash to branch petty cash
            $journal = app(JournalEntryCreationService::class)->create(new CreateJournalEntryData(
                tenantId: $tenantId,
                journalNumber: 'JE-TRANSFER-01',
                journalDate: '2026-06-10',
                journalType: JournalType::General,
                organizationUnitId: $branchId,
                source: null,
                description: 'Cash transfer to branch',
                lines: [
                    new JournalLineData((int) $branchPettyCash->getKey(), 1, debit: '150.000000', credit: '0.000000'),
                    new JournalLineData((int) $cash->getKey(), 2, debit: '0.000000', credit: '150.000000'),
                ],
            ));
            app(JournalPostingService::class)->post($journal);

            // 1. Tenant-wide Trial Balance (organizationUnitId = null) must include branch account and be balanced
            $tbService = app(TrialBalanceService::class);
            $tenantTb = $tbService->calculate($tenantId, null);
            $this->assertTrue($tenantTb->isBalanced, 'Tenant-wide trial balance must be balanced');
            $this->assertSame('150.000000', $tenantTb->totalDebit);
            $this->assertSame('150.000000', $tenantTb->totalCredit);
            $accountIdsInTb = collect($tenantTb->accountBalances)->pluck('accountId')->all();
            $this->assertContains((int) $branchPettyCash->getKey(), $accountIdsInTb);
            $this->assertContains((int) $cash->getKey(), $accountIdsInTb);

            // 2. Tenant-wide Cash Flow Report must include branch movements
            $cashFlowService = app(CashFlowReportService::class);
            $cashFlow = $cashFlowService->calculate($tenantId, null);
            $this->assertSame('150.000000', $cashFlow['total_inflow']);
            $this->assertSame('150.000000', $cashFlow['total_outflow']);

            // 3. Tenant-wide General Ledger Report must include branch entries
            $glService = app(GeneralLedgerReportService::class);
            $gl = $glService->paginate($tenantId, null);
            $this->assertSame(2, $gl->total());
        });
    }

    public function test_budget_allows_tenant_fallback_accounts_and_validates_dimension_scope(): void
    {
        [$tenantId, $cash] = $this->context();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $cash): void {
            $orgUnitId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'code' => 'OU_BUDGET_01',
                'name' => 'Budget Branch',
            ]);

            $dimension = FinanceDimension::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'code' => 'CC-01',
                'name' => 'Cost Center 1',
                'dimension_type' => 'cost_center',
                'is_active' => true,
            ]);

            $service = app(BudgetService::class);
            $budget = $service->save(
                tenantId: $tenantId,
                organizationUnitId: $orgUnitId,
                budgetYear: 2026,
                name: '2026 Branch Budget',
                lines: [
                    [
                        'account_id' => $cash->getKey(),
                        'dimension_id' => $dimension->getKey(),
                        'budget_month' => 1,
                        'amount' => '1000.000000',
                    ],
                ],
            );

            $this->assertSame('2026 Branch Budget', $budget->name);
            $this->assertCount(1, $budget->lines);

            $otherOrgUnitId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'code' => 'OU_OTHER_01',
                'name' => 'Other Branch',
            ]);
            $otherDimension = FinanceDimension::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $otherOrgUnitId,
                'code' => 'CC-OTHER',
                'name' => 'Other Branch Cost Center',
                'dimension_type' => 'cost_center',
                'is_active' => true,
            ]);

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Budget dimension scope mismatch.');

            $service->save(
                tenantId: $tenantId,
                organizationUnitId: $orgUnitId,
                budgetYear: 2026,
                name: 'Invalid Dimension Budget',
                lines: [
                    [
                        'account_id' => $cash->getKey(),
                        'dimension_id' => $otherDimension->getKey(),
                        'budget_month' => 1,
                        'amount' => '500.000000',
                    ],
                ],
            );
        });
    }

    public function test_budget_lifecycle_immutability(): void
    {
        [$tenantId, $cash] = $this->context();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $cash): void {
            $service = app(BudgetService::class);
            $budget = $service->save(
                tenantId: $tenantId,
                organizationUnitId: null,
                budgetYear: 2026,
                name: 'Annual Operations Budget',
                lines: [
                    [
                        'account_id' => $cash->getKey(),
                        'budget_month' => 1,
                        'amount' => '5000.000000',
                    ],
                ],
                status: 'draft',
            );

            // Mark budget as approved
            $budget->forceFill(['status' => 'approved'])->save();

            try {
                $service->save(
                    tenantId: $tenantId,
                    organizationUnitId: null,
                    budgetYear: 2026,
                    name: 'Modified Budget',
                    lines: [
                        [
                            'account_id' => $cash->getKey(),
                            'budget_month' => 1,
                            'amount' => '2000.000000',
                        ],
                    ],
                    budget: $budget,
                );
                $this->fail('Expected InvalidArgumentException on modifying approved budget');
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Only draft budgets can be modified', $e->getMessage());
            }

            try {
                $budget->delete();
                $this->fail('Expected LogicException on deleting approved budget');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Only draft budgets can be deleted', $e->getMessage());
            }
        });
    }

    public function test_posted_and_system_account_deletion_is_blocked(): void
    {
        [$tenantId, $cash] = $this->context();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $cash): void {
            // Mark cash as system account
            $cash->forceFill(['is_system' => true])->save();

            try {
                $cash->delete();
                $this->fail('Expected LogicException on deleting system account');
            } catch (LogicException $e) {
                $this->assertStringContainsString('System accounts cannot be deleted', $e->getMessage());
            }

            $cash->forceFill(['is_system' => false])->save();

            // Post a journal to cash
            $journal = $this->directRequest($tenantId, '2026-06-10');
            app(FinancePostingInterface::class)->post($journal);

            $this->assertTrue($cash->ledgerEntries()->exists());

            try {
                $cash->delete();
                $this->fail('Expected LogicException on deleting posted account');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Posted accounts cannot be deleted', $e->getMessage());
            }
        });
    }

    public function test_account_scope_and_normal_balance_mutation_is_blocked(): void
    {
        [$tenantId, $cash] = $this->context();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $cash): void {
            try {
                $cash->organization_unit_id = 99;
                $cash->save();
                $this->fail('Expected LogicException on changing organization_unit_id');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Finance account scope cannot be changed', $e->getMessage());
            }

            $cash->refresh();

            // Post journal to cash
            $journal = $this->directRequest($tenantId, '2026-06-10');
            app(FinancePostingInterface::class)->post($journal);

            try {
                $cash->normal_balance = NormalBalance::Credit;
                $cash->save();
                $this->fail('Expected LogicException on changing normal_balance of posted account');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Posted accounts cannot change normal balance', $e->getMessage());
            }
        });
    }

    public function test_journal_creation_rejects_inactive_or_non_posting_accounts(): void
    {
        [$tenantId, $cash, $capital] = $this->context();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $cash, $capital): void {
            $capital->forceFill(['is_posting_account' => false])->save();

            try {
                app(JournalEntryCreationService::class)->create(new CreateJournalEntryData(
                    tenantId: $tenantId,
                    journalNumber: 'JE-NONPOST-01',
                    journalDate: '2026-06-10',
                    journalType: JournalType::General,
                    organizationUnitId: null,
                    source: null,
                    description: 'Invalid non-posting line',
                    lines: [
                        new JournalLineData((int) $cash->getKey(), 1, debit: '100.000000'),
                        new JournalLineData((int) $capital->getKey(), 2, credit: '100.000000'),
                    ],
                ));
                $this->fail('Expected InvalidArgumentException on non-posting account in journal');
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Journal line references a non-posting account', $e->getMessage());
            }

            $capital->forceFill(['is_posting_account' => true, 'is_active' => false])->save();

            try {
                app(JournalEntryCreationService::class)->create(new CreateJournalEntryData(
                    tenantId: $tenantId,
                    journalNumber: 'JE-INACTIVE-01',
                    journalDate: '2026-06-10',
                    journalType: JournalType::General,
                    organizationUnitId: null,
                    source: null,
                    description: 'Invalid inactive account in line',
                    lines: [
                        new JournalLineData((int) $cash->getKey(), 1, debit: '100.000000'),
                        new JournalLineData((int) $capital->getKey(), 2, credit: '100.000000'),
                    ],
                ));
                $this->fail('Expected InvalidArgumentException on inactive account in journal');
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Journal line references an inactive account', $e->getMessage());
            }
        });
    }

    public function test_posting_profile_rule_rejects_inactive_account_role(): void
    {
        [$tenantId] = $this->context();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $roleId = $this->accountRole($tenantId, 'inactive_role_code', 'Inactive Role');
            DB::table('finance_account_roles')->where('id', $roleId)->update(['is_active' => false]);

            $service = app(PostingProfileService::class);

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Posting profile rule cannot reference an inactive account role.');

            $service->save(
                tenantId: $tenantId,
                organizationUnitId: null,
                code: 'inactive_role_profile',
                name: 'Inactive Role Profile',
                description: null,
                isActive: true,
                rules: [
                    [
                        'line_key' => 'debit_line',
                        'account_role_id' => $roleId,
                    ],
                ],
            );
        });
    }

    /**
     * @return array{0: int, 1: FinanceAccount, 2: FinanceAccount}
     */
    private function context(bool $withIncomeAccounts = false): array
    {
        $tenantId = $this->createTenant();

        return $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $withIncomeAccounts): array {
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
            source: new PostingSourceData('test_source', 1, $tenantId, sourceModule: FinanceJournalEntry::SOURCE_MODULE),
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
}
