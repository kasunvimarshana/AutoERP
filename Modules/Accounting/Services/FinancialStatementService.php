<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Modules\Accounting\Enums\AccountType;
use Modules\Accounting\Models\Account;
use Modules\Core\Helpers\MathHelper;

/**
 * Financial Statement Service
 *
 * Generates financial statements including Balance Sheet, Income Statement, and Cash Flow
 */
class FinancialStatementService
{
    /**
     * Generate Balance Sheet
     */
    public function generateBalanceSheet(
        string $organizationId,
        string $asOfDate
    ): array {
        $scale = config('accounting.decimal_scale', 6);

        $assets = $this->getAccountBalancesByType($organizationId, AccountType::Asset, null, $asOfDate);
        $liabilities = $this->getAccountBalancesByType($organizationId, AccountType::Liability, null, $asOfDate);
        $equity = $this->getAccountBalancesByType($organizationId, AccountType::Equity, null, $asOfDate);

        $totalAssets = $this->sumBalances($assets, $scale);
        $totalLiabilities = $this->sumBalances($liabilities, $scale);
        $totalEquity = $this->sumBalances($equity, $scale);

        $totalLiabilitiesAndEquity = MathHelper::add($totalLiabilities, $totalEquity, $scale);

        return [
            'organization_id' => $organizationId,
            'as_of_date' => $asOfDate,
            'generated_at' => now()->toIso8601String(),
            'assets' => [
                'accounts' => $assets,
                'total' => $totalAssets,
            ],
            'liabilities' => [
                'accounts' => $liabilities,
                'total' => $totalLiabilities,
            ],
            'equity' => [
                'accounts' => $equity,
                'total' => $totalEquity,
            ],
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
            'is_balanced' => MathHelper::equals($totalAssets, $totalLiabilitiesAndEquity, $scale),
        ];
    }

    /**
     * Generate Income Statement
     */
    public function generateIncomeStatement(
        string $organizationId,
        string $startDate,
        string $endDate
    ): array {
        $scale = config('accounting.decimal_scale', 6);

        $revenue = $this->getAccountBalancesByType($organizationId, AccountType::Revenue, $startDate, $endDate);
        $expenses = $this->getAccountBalancesByType($organizationId, AccountType::Expense, $startDate, $endDate);

        $totalRevenue = $this->sumBalances($revenue, $scale);
        $totalExpenses = $this->sumBalances($expenses, $scale);

        $netIncome = MathHelper::subtract($totalRevenue, $totalExpenses, $scale);

        return [
            'organization_id' => $organizationId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'generated_at' => now()->toIso8601String(),
            'revenue' => [
                'accounts' => $revenue,
                'total' => $totalRevenue,
            ],
            'expenses' => [
                'accounts' => $expenses,
                'total' => $totalExpenses,
            ],
            'net_income' => $netIncome,
            'is_profit' => MathHelper::greaterThan($netIncome, '0', $scale),
        ];
    }

    /**
     * Generate Cash Flow Statement (Indirect Method)
     *
     * Note: This is a basic implementation that calculates net income.
     * The detailed cash flow activity methods are stubs for future implementation.
     * Full cash flow statement generation requires integration with AR, AP, and other modules.
     */
    public function generateCashFlowStatement(
        string $organizationId,
        string $startDate,
        string $endDate
    ): array {
        $scale = config('accounting.decimal_scale', 6);

        $netIncome = $this->getNetIncome($organizationId, $startDate, $endDate);

        $operatingActivities = $this->getOperatingCashFlow($organizationId, $startDate, $endDate);
        $investingActivities = $this->getInvestingCashFlow($organizationId, $startDate, $endDate);
        $financingActivities = $this->getFinancingCashFlow($organizationId, $startDate, $endDate);

        $totalOperating = $this->sumCashFlowActivities($operatingActivities, $scale);
        $totalInvesting = $this->sumCashFlowActivities($investingActivities, $scale);
        $totalFinancing = $this->sumCashFlowActivities($financingActivities, $scale);

        $netCashFlow = MathHelper::add(
            MathHelper::add($totalOperating, $totalInvesting, $scale),
            $totalFinancing,
            $scale
        );

        return [
            'organization_id' => $organizationId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'generated_at' => now()->toIso8601String(),
            'net_income' => $netIncome,
            'operating_activities' => [
                'items' => $operatingActivities,
                'total' => $totalOperating,
            ],
            'investing_activities' => [
                'items' => $investingActivities,
                'total' => $totalInvesting,
            ],
            'financing_activities' => [
                'items' => $financingActivities,
                'total' => $totalFinancing,
            ],
            'net_cash_flow' => $netCashFlow,
        ];
    }

    /**
     * Get account balances by type
     */
    protected function getAccountBalancesByType(
        string $organizationId,
        AccountType $type,
        ?string $startDate,
        ?string $endDate
    ): array {
        $scale = config('accounting.decimal_scale', 6);

        $accounts = Account::where('organization_id', $organizationId)
            ->where('type', $type)
            ->where('status', 'active')
            ->whereDoesntHave('children')
            ->orderBy('code')
            ->get();

        $balances = [];

        foreach ($accounts as $account) {
            $query = $account->journalLines()
                ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                    $q->where('status', 'posted');

                    if ($startDate) {
                        $q->where('entry_date', '>=', $startDate);
                    }

                    if ($endDate) {
                        $q->where('entry_date', '<=', $endDate);
                    }
                });

            $totalDebits = (string) $query->sum('debit');
            $totalCredits = (string) $query->sum('credit');

            if ($account->normal_balance === 'debit') {
                $balance = MathHelper::subtract($totalDebits, $totalCredits, $scale);
            } else {
                $balance = MathHelper::subtract($totalCredits, $totalDebits, $scale);
            }

            if (! MathHelper::equals($balance, '0', $scale) || config('accounting.include_zero_balances', false)) {
                $balances[] = [
                    'account_id' => $account->id,
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'balance' => $balance,
                ];
            }
        }

        return $balances;
    }

    /**
     * Sum account balances
     */
    protected function sumBalances(array $balances, int $scale): string
    {
        $total = '0';

        foreach ($balances as $balance) {
            $total = MathHelper::add($total, $balance['balance'], $scale);
        }

        return $total;
    }

    /**
     * Get net income
     */
    protected function getNetIncome(string $organizationId, string $startDate, string $endDate): string
    {
        $scale = config('accounting.decimal_scale', 6);

        $revenue = $this->getAccountBalancesByType($organizationId, AccountType::Revenue, $startDate, $endDate);
        $expenses = $this->getAccountBalancesByType($organizationId, AccountType::Expense, $startDate, $endDate);

        $totalRevenue = $this->sumBalances($revenue, $scale);
        $totalExpenses = $this->sumBalances($expenses, $scale);

        return MathHelper::subtract($totalRevenue, $totalExpenses, $scale);
    }

    /**
     * Get operating cash flow activities
     *
     * Stub for future implementation - requires integration with:
     * - Accounts Receivable changes
     * - Accounts Payable changes
     * - Inventory changes
     * - Prepaid expenses and accrued liabilities
     */
    protected function getOperatingCashFlow(string $organizationId, string $startDate, string $endDate): array
    {
        return [];
    }

    /**
     * Get investing cash flow activities
     *
     * Stub for future implementation - requires integration with:
     * - Fixed asset purchases and sales
     * - Investment acquisitions and disposals
     */
    protected function getInvestingCashFlow(string $organizationId, string $startDate, string $endDate): array
    {
        return [];
    }

    /**
     * Get financing cash flow activities
     *
     * Stub for future implementation - requires integration with:
     * - Debt issuance and repayment
     * - Equity transactions
     * - Dividend payments
     */
    protected function getFinancingCashFlow(string $organizationId, string $startDate, string $endDate): array
    {
        return [];
    }

    /**
     * Sum cash flow activities
     */
    protected function sumCashFlowActivities(array $activities, int $scale): string
    {
        $total = '0';

        foreach ($activities as $activity) {
            $total = MathHelper::add($total, $activity['amount'] ?? '0', $scale);
        }

        return $total;
    }
}
