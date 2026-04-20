<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Application\Contracts\FinancialReportServiceInterface;

class FinancialReportService implements FinancialReportServiceInterface
{
    /**
     * General Ledger: paginated list of posted journal entry lines per account.
     *
     * @param  array<string, mixed>  $filters  Supported: account_id, fiscal_period_id, date_from, date_to, cost_center_id
     * @return array<string, mixed>
     */
    public function generalLedger(int $tenantId, array $filters = []): array
    {
        $query = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'jel.journal_entry_id', '=', 'je.id')
            ->join('accounts as a', 'jel.account_id', '=', 'a.id')
            ->leftJoin('fiscal_periods as fp', 'je.fiscal_period_id', '=', 'fp.id')
            ->leftJoin('cost_centers as cc', 'jel.cost_center_id', '=', 'cc.id')
            ->where('je.tenant_id', $tenantId)
            ->where('je.status', 'posted')
            ->select([
                'jel.id',
                'je.id as journal_entry_id',
                'je.entry_number',
                'je.entry_date',
                'je.posting_date',
                'je.description as entry_description',
                'jel.account_id',
                'a.code as account_code',
                'a.name as account_name',
                'a.type as account_type',
                'jel.description as line_description',
                'jel.debit_amount',
                'jel.credit_amount',
                'jel.currency_id',
                'jel.exchange_rate',
                'jel.base_debit_amount',
                'jel.base_credit_amount',
                'jel.cost_center_id',
                'cc.name as cost_center_name',
                'fp.name as fiscal_period_name',
            ])
            ->orderBy('je.posting_date')
            ->orderBy('je.entry_number')
            ->orderBy('jel.id');

        if (! empty($filters['account_id'])) {
            $query->where('jel.account_id', (int) $filters['account_id']);
        }

        if (! empty($filters['fiscal_period_id'])) {
            $query->where('je.fiscal_period_id', (int) $filters['fiscal_period_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('je.posting_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('je.posting_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['cost_center_id'])) {
            $query->where('jel.cost_center_id', (int) $filters['cost_center_id']);
        }

        $perPage = (int) ($filters['per_page'] ?? 50);
        $page = (int) ($filters['page'] ?? 1);

        $total = $query->count();
        $lines = $query->forPage($page, $perPage)->get()->map(fn (object $r): array => (array) $r)->all();

        return [
            'data' => $lines,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / max(1, $perPage)),
            ],
        ];
    }

    /**
     * Trial Balance: debit/credit totals per account for a given period.
     *
     * @param  array<string, mixed>  $filters  Supported: fiscal_period_id, date_from, date_to
     * @return array<string, mixed>
     */
    public function trialBalance(int $tenantId, array $filters = []): array
    {
        $query = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'jel.journal_entry_id', '=', 'je.id')
            ->join('accounts as a', 'jel.account_id', '=', 'a.id')
            ->where('je.tenant_id', $tenantId)
            ->where('je.status', 'posted')
            ->groupBy('jel.account_id', 'a.code', 'a.name', 'a.type', 'a.normal_balance')
            ->select([
                'jel.account_id',
                'a.code as account_code',
                'a.name as account_name',
                'a.type as account_type',
                'a.normal_balance',
                DB::raw('SUM(jel.base_debit_amount) as total_debit'),
                DB::raw('SUM(jel.base_credit_amount) as total_credit'),
                DB::raw('SUM(jel.base_debit_amount) - SUM(jel.base_credit_amount) as net_balance'),
            ])
            ->orderBy('a.code');

        if (! empty($filters['fiscal_period_id'])) {
            $query->where('je.fiscal_period_id', (int) $filters['fiscal_period_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('je.posting_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('je.posting_date', '<=', $filters['date_to']);
        }

        $rows = $query->get()->map(fn (object $r): array => (array) $r)->all();

        $totalDebit = array_sum(array_column($rows, 'total_debit'));
        $totalCredit = array_sum(array_column($rows, 'total_credit'));

        return [
            'data' => $rows,
            'summary' => [
                'total_debit' => round((float) $totalDebit, 6),
                'total_credit' => round((float) $totalCredit, 6),
                'is_balanced' => abs((float) $totalDebit - (float) $totalCredit) < PHP_FLOAT_EPSILON,
            ],
        ];
    }

    /**
     * Balance Sheet: asset, liability, equity account balances as of a date.
     *
     * @param  array<string, mixed>  $filters  Supported: as_of_date (YYYY-MM-DD)
     * @return array<string, mixed>
     */
    public function balanceSheet(int $tenantId, array $filters = []): array
    {
        $query = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'jel.journal_entry_id', '=', 'je.id')
            ->join('accounts as a', 'jel.account_id', '=', 'a.id')
            ->where('je.tenant_id', $tenantId)
            ->where('je.status', 'posted')
            ->whereIn('a.type', ['asset', 'liability', 'equity'])
            ->groupBy('jel.account_id', 'a.code', 'a.name', 'a.type', 'a.sub_type', 'a.normal_balance')
            ->select([
                'jel.account_id',
                'a.code as account_code',
                'a.name as account_name',
                'a.type as account_type',
                'a.sub_type as account_sub_type',
                'a.normal_balance',
                DB::raw('SUM(jel.base_debit_amount) - SUM(jel.base_credit_amount) as balance'),
            ])
            ->orderBy('a.type')
            ->orderBy('a.code');

        if (! empty($filters['as_of_date'])) {
            $query->where('je.posting_date', '<=', $filters['as_of_date']);
        }

        $rows = $query->get()->map(fn (object $r): array => (array) $r)->all();

        $assets = array_filter($rows, static fn (array $r): bool => $r['account_type'] === 'asset');
        $liabilities = array_filter($rows, static fn (array $r): bool => $r['account_type'] === 'liability');
        $equity = array_filter($rows, static fn (array $r): bool => $r['account_type'] === 'equity');

        $totalAssets = array_sum(array_column(array_values($assets), 'balance'));
        $totalLiabilities = array_sum(array_column(array_values($liabilities), 'balance'));
        $totalEquity = array_sum(array_column(array_values($equity), 'balance'));

        return [
            'assets' => array_values($assets),
            'liabilities' => array_values($liabilities),
            'equity' => array_values($equity),
            'summary' => [
                'total_assets' => round((float) $totalAssets, 6),
                'total_liabilities' => round((float) $totalLiabilities, 6),
                'total_equity' => round((float) $totalEquity, 6),
                'total_liabilities_and_equity' => round((float) ($totalLiabilities + $totalEquity), 6),
                'is_balanced' => abs((float) $totalAssets - (float) ($totalLiabilities + $totalEquity)) < PHP_FLOAT_EPSILON,
            ],
        ];
    }

    /**
     * Profit & Loss: revenue and expense account balances for a period.
     *
     * @param  array<string, mixed>  $filters  Supported: date_from, date_to, fiscal_period_id
     * @return array<string, mixed>
     */
    public function profitLoss(int $tenantId, array $filters = []): array
    {
        $query = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'jel.journal_entry_id', '=', 'je.id')
            ->join('accounts as a', 'jel.account_id', '=', 'a.id')
            ->where('je.tenant_id', $tenantId)
            ->where('je.status', 'posted')
            ->whereIn('a.type', ['revenue', 'expense'])
            ->groupBy('jel.account_id', 'a.code', 'a.name', 'a.type', 'a.sub_type', 'a.normal_balance')
            ->select([
                'jel.account_id',
                'a.code as account_code',
                'a.name as account_name',
                'a.type as account_type',
                'a.sub_type as account_sub_type',
                'a.normal_balance',
                DB::raw('SUM(jel.base_credit_amount) - SUM(jel.base_debit_amount) as balance'),
            ])
            ->orderBy('a.type')
            ->orderBy('a.code');

        if (! empty($filters['fiscal_period_id'])) {
            $query->where('je.fiscal_period_id', (int) $filters['fiscal_period_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('je.posting_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('je.posting_date', '<=', $filters['date_to']);
        }

        $rows = $query->get()->map(fn (object $r): array => (array) $r)->all();

        $revenues = array_filter($rows, static fn (array $r): bool => $r['account_type'] === 'revenue');
        $expenses = array_filter($rows, static fn (array $r): bool => $r['account_type'] === 'expense');

        $totalRevenue = array_sum(array_column(array_values($revenues), 'balance'));
        $totalExpenses = array_sum(array_column(array_values($expenses), 'balance'));
        $netIncome = (float) $totalRevenue - (float) $totalExpenses;

        return [
            'revenues' => array_values($revenues),
            'expenses' => array_values($expenses),
            'summary' => [
                'total_revenue' => round((float) $totalRevenue, 6),
                'total_expenses' => round((float) $totalExpenses, 6),
                'net_income' => round($netIncome, 6),
            ],
        ];
    }
}
