<?php

declare(strict_types=1);

namespace Modules\Expense\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Expense\DTOs\CreateExpenseData;
use Modules\Expense\Enums\ExpenseStatus;
use Modules\Expense\Models\ExpenseType;
use Modules\Expense\Services\ExpensePostingService;
use Modules\Expense\Services\ExpenseReversalService;
use Modules\Finance\Services\FinanceStatementService;
use Modules\Reporting\Services\ExpenseReportService;
use Tests\Support\FinancePostingFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class ExpensePostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_expense_posts_cash_out_reduces_profit_is_idempotent_and_reverses(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        FinancePostingFixture::seedExpensePaymentProfile($tenantId, $organizationUnitId);
        $paymentMethodId = $this->paymentMethod($tenantId);

        $expense = $this->withTenantExecutionContext($tenantId, function () use (
            $tenantId,
            $organizationUnitId,
            $paymentMethodId,
        ) {
            $type = ExpenseType::query()->create([
                'tenant_id' => $tenantId,
                'code' => 'RENT',
                'name' => 'Rent',
                'is_active' => true,
            ]);
            $data = new CreateExpenseData(
                tenantId: $tenantId,
                organizationUnitId: $organizationUnitId,
                expenseTypeId: (int) $type->getKey(),
                paymentMethodId: $paymentMethodId,
                expenseDate: '2026-09-22',
                amount: '125.500000',
                referenceNumber: 'RENT-SEP',
                idempotencyKey: 'expense-posting-test',
            );
            $service = app(ExpensePostingService::class);
            $created = $service->createAndPost($data);
            $replayed = $service->createAndPost($data);

            self::assertSame($created->getKey(), $replayed->getKey());

            return $created;
        });

        self::assertSame(ExpenseStatus::Posted, $expense->status);
        self::assertSame('125.500000', (string) $expense->amount);
        self::assertSame('Cash', $expense->payment_method_name_snapshot);
        self::assertSame(1, DB::table('expenses')->count());

        $journal = DB::table('finance_journal_entries')
            ->where('source_module', 'expense')
            ->where('source_type', 'expense')
            ->where('source_id', $expense->getKey())
            ->first();
        self::assertNotNull($journal);
        self::assertSame('posted', $journal->status);
        self::assertSame(125.5, (float) $journal->total_debit);
        self::assertSame(125.5, (float) $journal->total_credit);
        self::assertSame(2, DB::table('finance_ledger_entries')
            ->where('journal_entry_id', $journal->id)
            ->count());

        $profit = $this->withTenantExecutionContext(
            $tenantId,
            fn (): array => app(FinanceStatementService::class)->profitAndLoss(
                $tenantId,
                $organizationUnitId,
                '2026-09-01',
                '2026-09-30',
            ),
        );
        self::assertSame('125.500000', $profit['total_expenses']);
        self::assertSame('-125.500000', $profit['net_profit']);

        $expenseReport = app(ExpenseReportService::class)->overview(
            $tenantId,
            $organizationUnitId,
            '2026-09-01',
            '2026-09-30',
        );
        self::assertSame('125.500000', $expenseReport['summary']['posted_amount']);
        self::assertSame('125.500000', $expenseReport['summary']['net_amount']);
        self::assertSame('Rent', $expenseReport['by_expense_type'][0]['name']);

        $reversed = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(ExpenseReversalService::class)->reverse(
                $expense,
                (int) $expense->row_version,
                '2026-09-22',
                'Incorrect branch expense',
                null,
            ),
        );
        self::assertSame(ExpenseStatus::Reversed, $reversed->status);
        self::assertNotNull($reversed->finance_reversal_reference);
        self::assertSame(2, DB::table('finance_journal_entries')->count());

        $afterReversal = $this->withTenantExecutionContext(
            $tenantId,
            fn (): array => app(FinanceStatementService::class)->profitAndLoss(
                $tenantId,
                $organizationUnitId,
                '2026-09-01',
                '2026-09-30',
            ),
        );
        self::assertSame('0.000000', $afterReversal['total_expenses']);
        self::assertSame('0.000000', $afterReversal['net_profit']);

        $reversedExpenseReport = app(ExpenseReportService::class)->overview(
            $tenantId,
            $organizationUnitId,
            '2026-09-01',
            '2026-09-30',
        );
        self::assertSame(2, $reversedExpenseReport['summary']['event_count']);
        self::assertSame('125.500000', $reversedExpenseReport['summary']['posted_amount']);
        self::assertSame('125.500000', $reversedExpenseReport['summary']['reversed_amount']);
        self::assertSame('0.000000', $reversedExpenseReport['summary']['net_amount']);
    }

    /** @return array{int, int} */
    private function scope(): array
    {
        $suffix = Str::upper(Str::random(8));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-'.$suffix,
            'name' => 'Tenant '.$suffix,
            'slug' => 'tenant-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organizationUnitId = (int) OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Branch '.$suffix,
            'code' => 'BR-'.$suffix,
            'is_active' => true,
        ]);

        return [$tenantId, $organizationUnitId];
    }

    private function paymentMethod(int $tenantId): int
    {
        return (int) DB::table('payment_methods')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'scope_key' => 'tenant:'.$tenantId,
            'code' => 'CASH',
            'name' => 'Cash',
            'method_type' => 'cash',
            'direction_allowed' => 'outbound',
            'requires_reference' => false,
            'requires_instrument_details' => false,
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
