<?php

declare(strict_types=1);

namespace Modules\Financial\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Financial\Application\Contracts\FinancialReportingServiceInterface;
use Modules\Financial\Domain\Contracts\Repositories\AccountRepositoryInterface;
use Modules\Financial\Domain\Contracts\Repositories\JournalEntryLineRepositoryInterface;

/**
 * Financial Reporting Service.
 *
 * Generates Balance Sheet and Profit & Loss reports by aggregating
 * posted journal entry lines against the chart of accounts.
 */
class FinancialReportingService implements FinancialReportingServiceInterface
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly JournalEntryLineRepositoryInterface $lineRepository,
    ) {}

    /**
     * Generate a Balance Sheet as-of the given date.
     *
     * Aggregates all posted journal entry lines up to and including the as-of date,
     * grouped by account type (asset, liability, equity).
     *
     * @return array{
     *   as_of_date: string,
     *   assets: array{accounts: array<int, array{id: string, code: string, name: string, balance: float}>, total: float},
     *   liabilities: array{accounts: array<int, array{id: string, code: string, name: string, balance: float}>, total: float},
     *   equity: array{accounts: array<int, array{id: string, code: string, name: string, balance: float}>, total: float},
     *   is_balanced: bool,
     * }
     */
    public function balanceSheet(int $tenantId, string $asOfDate, string $currencyCode = 'USD'): array
    {
        $balances = $this->getAccountBalancesUpTo($tenantId, $asOfDate);

        $assets      = $this->extractTypeBalances($balances, ['asset']);
        $liabilities = $this->extractTypeBalances($balances, ['liability']);
        $equity      = $this->extractTypeBalances($balances, ['equity']);

        $totalAssets      = array_sum(array_column($assets, 'balance'));
        $totalLiabilities = array_sum(array_column($liabilities, 'balance'));
        $totalEquity      = array_sum(array_column($equity, 'balance'));

        return [
            'as_of_date'  => $asOfDate,
            'currency'    => $currencyCode,
            'assets'      => [
                'accounts' => $assets,
                'total'    => round($totalAssets, 4),
            ],
            'liabilities' => [
                'accounts' => $liabilities,
                'total'    => round($totalLiabilities, 4),
            ],
            'equity'      => [
                'accounts' => $equity,
                'total'    => round($totalEquity, 4),
            ],
            'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
        ];
    }

    /**
     * Generate a Profit & Loss (Income Statement) for the given date range.
     *
     * Aggregates all posted revenue and expense lines within the date range.
     *
     * @return array{
     *   from_date: string,
     *   to_date: string,
     *   revenue: array{accounts: array<int, array{id: string, code: string, name: string, balance: float}>, total: float},
     *   expenses: array{accounts: array<int, array{id: string, code: string, name: string, balance: float}>, total: float},
     *   net_income: float,
     * }
     */
    public function profitAndLoss(int $tenantId, string $fromDate, string $toDate, string $currencyCode = 'USD'): array
    {
        $balances = $this->getAccountBalancesInRange($tenantId, $fromDate, $toDate);

        $revenue  = $this->extractTypeBalances($balances, ['revenue']);
        $expenses = $this->extractTypeBalances($balances, ['expense']);

        $totalRevenue  = array_sum(array_column($revenue, 'balance'));
        $totalExpenses = array_sum(array_column($expenses, 'balance'));
        $netIncome     = $totalRevenue - $totalExpenses;

        return [
            'from_date'  => $fromDate,
            'to_date'    => $toDate,
            'currency'   => $currencyCode,
            'revenue'    => [
                'accounts' => $revenue,
                'total'    => round($totalRevenue, 4),
            ],
            'expenses'   => [
                'accounts' => $expenses,
                'total'    => round($totalExpenses, 4),
            ],
            'net_income' => round($netIncome, 4),
        ];
    }

    /**
     * Fetch aggregated account balances for all posted journal entries up to the given date.
     *
     * @return array<string, array{id: string, code: string, name: string, type: string, normal_balance: string, net: float}>
     */
    private function getAccountBalancesUpTo(int $tenantId, string $asOfDate): array
    {
        return $this->aggregateLines($tenantId, null, $asOfDate);
    }

    /**
     * Fetch aggregated account balances for posted journal entries within a date range.
     *
     * @return array<string, array{id: string, code: string, name: string, type: string, normal_balance: string, net: float}>
     */
    private function getAccountBalancesInRange(int $tenantId, string $fromDate, string $toDate): array
    {
        return $this->aggregateLines($tenantId, $fromDate, $toDate);
    }

    /**
     * Perform the DB aggregation query against journal_entry_lines joined to accounts and entries.
     *
     * @return array<string, array{id: string, code: string, name: string, type: string, normal_balance: string, net: float}>
     */
    private function aggregateLines(int $tenantId, ?string $fromDate, string $toDate): array
    {
        $query = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jel.account_id')
            ->where('je.tenant_id', $tenantId)
            ->where('je.status', 'posted')
            ->whereNull('je.deleted_at')
            ->whereDate('je.posting_date', '<=', $toDate)
            ->select([
                'a.id',
                'a.code',
                'a.name',
                'a.type',
                'a.normal_balance',
                DB::raw('SUM(jel.debit)  AS total_debit'),
                DB::raw('SUM(jel.credit) AS total_credit'),
            ])
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type', 'a.normal_balance');

        if ($fromDate !== null) {
            $query->whereDate('je.posting_date', '>=', $fromDate);
        }

        $rows    = $query->get();
        $indexed = [];

        foreach ($rows as $row) {
            $debit  = (float) $row->total_debit;
            $credit = (float) $row->total_credit;

            // Net balance follows the normal balance convention:
            // debit-normal accounts (asset, expense): balance = debits - credits
            // credit-normal accounts (liability, equity, revenue): balance = credits - debits
            $net = $row->normal_balance === 'debit'
                ? $debit - $credit
                : $credit - $debit;

            $indexed[$row->id] = [
                'id'             => $row->id,
                'code'           => $row->code,
                'name'           => $row->name,
                'type'           => $row->type,
                'normal_balance' => $row->normal_balance,
                'net'            => round($net, 4),
            ];
        }

        return $indexed;
    }

    /**
     * Filter aggregated balances by account types and return an array of balance items.
     *
     * @param  array<string, array{id: string, code: string, name: string, type: string, net: float}>  $balances
     * @param  string[]  $types
     * @return array<int, array{id: string, code: string, name: string, balance: float}>
     */
    private function extractTypeBalances(array $balances, array $types): array
    {
        $result = [];
        foreach ($balances as $item) {
            if (in_array($item['type'], $types, true)) {
                $result[] = [
                    'id'      => $item['id'],
                    'code'    => $item['code'],
                    'name'    => $item['name'],
                    'balance' => $item['net'],
                ];
            }
        }

        usort($result, fn ($a, $b) => strcmp($a['code'], $b['code']));

        return $result;
    }
}
