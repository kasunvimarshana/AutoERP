<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Application\Contracts\FinancialReportServiceInterface;
use Modules\Finance\Domain\ValueObjects\AccountType;

final class FinancialReportService implements FinancialReportServiceInterface
{
    /**
     * {@inheritdoc}
     *
     * Balance Sheet = Assets | Liabilities + Equity as of a date.
     * We compute account balances from posted journal entry lines up to asOfDate.
     */
    public function getBalanceSheet(int $tenantId, string $asOfDate): array
    {
        $balances = $this->getAccountBalances($tenantId, '1900-01-01', $asOfDate, [
            AccountType::ASSET,
            AccountType::LIABILITY,
            AccountType::EQUITY,
        ]);

        $assets      = $balances->where('type', AccountType::ASSET)->values();
        $liabilities = $balances->where('type', AccountType::LIABILITY)->values();
        $equity      = $balances->where('type', AccountType::EQUITY)->values();

        $totalAssets      = (float) $assets->sum('balance');
        $totalLiabilities = (float) $liabilities->sum('balance');
        $totalEquity      = (float) $equity->sum('balance');

        return [
            'as_of_date'  => $asOfDate,
            'assets'      => $assets->toArray(),
            'liabilities' => $liabilities->toArray(),
            'equity'      => $equity->toArray(),
            'totals'      => [
                'total_assets'                    => $totalAssets,
                'total_liabilities'               => $totalLiabilities,
                'total_equity'                    => $totalEquity,
                'total_liabilities_and_equity'    => $totalLiabilities + $totalEquity,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * P&L = Revenue – Expenses for the period.
     */
    public function getProfitAndLoss(int $tenantId, string $fromDate, string $toDate): array
    {
        $balances = $this->getAccountBalances($tenantId, $fromDate, $toDate, [
            AccountType::REVENUE,
            AccountType::EXPENSE,
        ]);

        $revenue  = $balances->where('type', AccountType::REVENUE)->values();
        $expenses = $balances->where('type', AccountType::EXPENSE)->values();

        $totalRevenue  = (float) $revenue->sum('balance');
        $totalExpenses = (float) $expenses->sum('balance');
        $netIncome     = $totalRevenue - $totalExpenses;

        return [
            'from_date'  => $fromDate,
            'to_date'    => $toDate,
            'revenue'    => $revenue->toArray(),
            'expenses'   => $expenses->toArray(),
            'net_income' => $netIncome,
            'totals'     => [
                'total_revenue'  => $totalRevenue,
                'total_expenses' => $totalExpenses,
                'net_income'     => $netIncome,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Trial Balance: all accounts with their debit/credit totals from posted entries.
     */
    public function getTrialBalance(int $tenantId, string $asOfDate): array
    {
        $rows = DB::table('accounts as a')
            ->leftJoin('journal_entry_lines as jel', 'jel.account_id', '=', 'a.id')
            ->leftJoin('journal_entries as je', static function ($join) use ($asOfDate) {
                $join->on('je.id', '=', 'jel.journal_entry_id')
                     ->where('je.status', '=', 'posted')
                     ->whereDate('je.entry_date', '<=', $asOfDate);
            })
            ->where('a.tenant_id', $tenantId)
            ->whereNull('a.deleted_at')
            ->where('a.is_active', true)
            ->select([
                'a.id',
                'a.code',
                'a.name',
                'a.type',
                'a.nature',
                DB::raw('COALESCE(SUM(jel.debit_amount), 0) as total_debit'),
                DB::raw('COALESCE(SUM(jel.credit_amount), 0) as total_credit'),
            ])
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type', 'a.nature')
            ->orderBy('a.code')
            ->get();

        $totalDebit  = (float) $rows->sum('total_debit');
        $totalCredit = (float) $rows->sum('total_credit');

        return [
            'as_of_date'   => $asOfDate,
            'accounts'     => $rows->toArray(),
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced'  => abs($totalDebit - $totalCredit) < 0.000001,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Cash Flow: movements on bank/cash accounts in the period, grouped by direction.
     */
    public function getCashFlowStatement(int $tenantId, string $fromDate, string $toDate): array
    {
        $rows = DB::table('transactions as t')
            ->where('t.tenant_id', $tenantId)
            ->whereNull('t.deleted_at')
            ->whereIn('t.status', ['completed'])
            ->whereBetween('t.transaction_date', [$fromDate, $toDate])
            ->select([
                't.type',
                DB::raw('SUM(t.amount * t.exchange_rate) as total'),
            ])
            ->groupBy('t.type')
            ->get()
            ->keyBy('type');

        $income   = (float) ($rows->get('income')?->total   ?? 0);
        $expense  = (float) ($rows->get('expense')?->total  ?? 0);
        $transfer = (float) ($rows->get('transfer')?->total ?? 0);
        $payment  = (float) ($rows->get('payment')?->total  ?? 0);
        $refund   = (float) ($rows->get('refund')?->total   ?? 0);

        $operating = $income - $expense;
        $financing = $payment - $refund;
        $investing = 0.0;

        return [
            'from_date'  => $fromDate,
            'to_date'    => $toDate,
            'operating'  => [
                'income'     => $income,
                'expenses'   => $expense,
                'net'        => $operating,
            ],
            'investing'  => [
                'net' => $investing,
            ],
            'financing'  => [
                'payments' => $payment,
                'refunds'  => $refund,
                'net'      => $financing,
            ],
            'net_change' => $operating + $investing + $financing,
        ];
    }

    /**
     * Compute net account balances from posted journal entry lines for a tenant,
     * filtered by account types and date range.
     */
    private function getAccountBalances(
        int $tenantId,
        string $fromDate,
        string $toDate,
        array $accountTypes,
    ): \Illuminate\Support\Collection {
        return DB::table('accounts as a')
            ->leftJoin('journal_entry_lines as jel', 'jel.account_id', '=', 'a.id')
            ->leftJoin('journal_entries as je', static function ($join) use ($fromDate, $toDate) {
                $join->on('je.id', '=', 'jel.journal_entry_id')
                     ->where('je.status', '=', 'posted')
                     ->whereDate('je.entry_date', '>=', $fromDate)
                     ->whereDate('je.entry_date', '<=', $toDate);
            })
            ->where('a.tenant_id', $tenantId)
            ->whereNull('a.deleted_at')
            ->whereIn('a.type', $accountTypes)
            ->where('a.is_active', true)
            ->select([
                'a.id',
                'a.code',
                'a.name',
                'a.type',
                'a.nature',
                'a.classification',
                'a.opening_balance',
                DB::raw('COALESCE(SUM(jel.debit_amount), 0) as period_debit'),
                DB::raw('COALESCE(SUM(jel.credit_amount), 0) as period_credit'),
                DB::raw('
                    a.opening_balance + CASE
                        WHEN a.nature = \'debit\'
                        THEN COALESCE(SUM(jel.debit_amount), 0) - COALESCE(SUM(jel.credit_amount), 0)
                        ELSE COALESCE(SUM(jel.credit_amount), 0) - COALESCE(SUM(jel.debit_amount), 0)
                    END AS balance
                '),
            ])
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type', 'a.nature', 'a.classification', 'a.opening_balance')
            ->orderBy('a.code')
            ->get();
    }
}
