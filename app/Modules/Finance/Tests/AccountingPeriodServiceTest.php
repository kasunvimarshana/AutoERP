<?php

declare(strict_types=1);

namespace Modules\Finance\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Finance\DTOs\AccountingPeriodData;
use Modules\Finance\DTOs\CreateAccountData;
use Modules\Finance\DTOs\CreateJournalEntryData;
use Modules\Finance\DTOs\JournalLineData;
use Modules\Finance\Enums\JournalStatus;
use Modules\Finance\Enums\NormalBalance;
use Modules\Finance\Enums\StatementType;
use Modules\Finance\Models\FinanceAccountType;
use Modules\Finance\Services\AccountingPeriodService;
use Modules\Finance\Services\ChartOfAccountsService;
use Modules\Finance\Services\JournalEntryCreationService;
use Modules\Finance\Services\JournalPostingService;
use Tests\TestCase;

final class AccountingPeriodServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_unconfigured_scope_is_bootstrap_open_but_configured_gaps_fail_closed(): void
    {
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $service = app(AccountingPeriodService::class);
            $service->assertPostingDateAllowed($tenantId, null, '2026-01-15');
            $service->create(new AccountingPeriodData(
                tenantId: $tenantId,
                organizationUnitId: null,
                code: 'FY26-P01',
                name: 'January 2026',
                startDate: '2026-01-01',
                endDate: '2026-01-31',
            ));

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('outside the configured accounting periods');
            $service->assertPostingDateAllowed($tenantId, null, '2026-02-01');
        });
    }

    public function test_close_blocks_journal_posting_and_reopen_restores_it(): void
    {
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $periods = app(AccountingPeriodService::class);
            $period = $periods->create(new AccountingPeriodData(
                tenantId: $tenantId,
                organizationUnitId: null,
                code: 'FY26-P01',
                name: 'January 2026',
                startDate: '2026-01-01',
                endDate: '2026-01-31',
            ));
            $period = $periods->close($period, (int) $period->row_version, 'Month-end close.');
            $journal = $this->journal($tenantId, '2026-01-15');

            try {
                app(JournalPostingService::class)->post($journal);
                $this->fail('Expected closed accounting period to block journal posting.');
            } catch (InvalidArgumentException $exception) {
                $this->assertStringContainsString('closed accounting period', $exception->getMessage());
            }
            $this->assertSame(JournalStatus::Draft, $journal->refresh()->status);
            $this->assertSame(0, DB::table('finance_ledger_entries')->count());

            $period = $periods->reopen(
                $period,
                (int) $period->row_version,
                'Approved correction window.',
            );
            $posted = app(JournalPostingService::class)->post($journal->refresh());

            $this->assertSame(JournalStatus::Posted, $posted->status);
            $this->assertSame(2, DB::table('finance_ledger_entries')->count());
            $this->assertSame(['created', 'closed', 'reopened'], $period->events->pluck('event_type.value')->all());
        });
    }

    public function test_periods_cannot_overlap_and_lifecycle_requires_current_version(): void
    {
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $service = app(AccountingPeriodService::class);
            $period = $service->create(new AccountingPeriodData(
                tenantId: $tenantId,
                organizationUnitId: null,
                code: 'FY26-P01',
                name: 'January 2026',
                startDate: '2026-01-01',
                endDate: '2026-01-31',
            ));

            try {
                $service->create(new AccountingPeriodData(
                    tenantId: $tenantId,
                    organizationUnitId: null,
                    code: 'FY26-OVERLAP',
                    name: 'Overlap',
                    startDate: '2026-01-15',
                    endDate: '2026-02-15',
                ));
                $this->fail('Expected overlapping accounting period to fail.');
            } catch (InvalidArgumentException $exception) {
                $this->assertStringContainsString('overlap', $exception->getMessage());
            }

            $service->close($period, (int) $period->row_version, 'Close.');
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('changed by another request');
            $service->reopen($period, (int) $period->row_version, 'Stale reopen.');
        });
    }

    private function journal(int $tenantId, string $postingDate)
    {
        $type = FinanceAccountType::query()->create([
            'tenant_id' => $tenantId,
            'code' => 'ASSET',
            'name' => 'Asset',
            'normal_balance' => NormalBalance::Debit->value,
            'statement_type' => StatementType::BalanceSheet->value,
            'is_active' => true,
        ]);
        $accounts = app(ChartOfAccountsService::class);
        $cash = $accounts->createAccount(new CreateAccountData(
            tenantId: $tenantId,
            accountTypeId: (int) $type->getKey(),
            code: '1010',
            name: 'Cash',
            normalBalance: NormalBalance::Debit,
        ));
        $clearing = $accounts->createAccount(new CreateAccountData(
            tenantId: $tenantId,
            accountTypeId: (int) $type->getKey(),
            code: '1090',
            name: 'Clearing',
            normalBalance: NormalBalance::Debit,
        ));

        return app(JournalEntryCreationService::class)->create(new CreateJournalEntryData(
            tenantId: $tenantId,
            journalDate: $postingDate,
            description: 'Accounting period enforcement test.',
            lines: [
                new JournalLineData(
                    accountId: (int) $cash->getKey(),
                    lineNumber: 1,
                    debit: '10.000000',
                ),
                new JournalLineData(
                    accountId: (int) $clearing->getKey(),
                    lineNumber: 2,
                    credit: '10.000000',
                ),
            ],
        ));
    }

    private function tenant(): int
    {
        $suffix = Str::upper(Str::random(6));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'FIN-'.$suffix,
            'name' => 'Finance '.$suffix,
            'slug' => 'finance-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
