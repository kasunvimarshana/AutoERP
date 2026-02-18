<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Repositories\AccountRepository;
use App\Modules\Finance\Repositories\JournalEntryRepository;
use App\Services\BaseService;

/**
 * Financial Report Service
 *
 * Generates financial reports including P&L, Balance Sheet, and Trial Balance.
 */
class FinancialReportService extends BaseService
{
    private const HISTORICAL_START_DATE = '1900-01-01';

    public function __construct(
        protected AccountRepository $accountRepository,
        protected JournalEntryRepository $journalEntryRepository
    ) {}

    public function generateTrialBalance(string $startDate, string $endDate): array
    {
        $this->logInfo('Generating trial balance', compact('startDate', 'endDate'));

        $accounts = $this->accountRepository->getActiveAccounts();
        $report = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            $openingBalance = $account->opening_balance;

            $transactions = $account->journalEntryLines()
                ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                    $q->posted()
                        ->whereBetween('entry_date', [$startDate, $endDate]);
                })
                ->get();

            $debit = $transactions->sum('debit');
            $credit = $transactions->sum('credit');

            // Calculate closing balance
            if ($account->type->isDebitNormal()) {
                $closingBalance = $openingBalance + $debit - $credit;
            } else {
                $closingBalance = $openingBalance + $credit - $debit;
            }

            $report[] = [
                'account_code' => $account->code,
                'account_name' => $account->name,
                'account_type' => $account->type->label(),
                'opening_balance' => $openingBalance,
                'debit' => $debit,
                'credit' => $credit,
                'closing_balance' => $closingBalance,
            ];

            if ($closingBalance > 0) {
                if ($account->type->isDebitNormal()) {
                    $totalDebit += $closingBalance;
                } else {
                    $totalCredit += $closingBalance;
                }
            } elseif ($closingBalance < 0) {
                if ($account->type->isDebitNormal()) {
                    $totalCredit += abs($closingBalance);
                } else {
                    $totalDebit += abs($closingBalance);
                }
            }
        }

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'accounts' => $report,
            'totals' => [
                'debit' => $totalDebit,
                'credit' => $totalCredit,
            ],
            'is_balanced' => bccomp($totalDebit, $totalCredit, 2) === 0,
        ];
    }

    public function generateProfitAndLoss(string $startDate, string $endDate): array
    {
        $this->logInfo('Generating profit and loss statement', compact('startDate', 'endDate'));

        $revenueAccounts = $this->accountRepository->getAccountsForFinancialStatements(
            AccountType::REVENUE,
            $startDate,
            $endDate
        );

        $expenseAccounts = $this->accountRepository->getAccountsForFinancialStatements(
            AccountType::EXPENSE,
            $startDate,
            $endDate
        );

        $revenues = [];
        $totalRevenue = 0;

        foreach ($revenueAccounts as $account) {
            $credit = $account->journalEntryLines->sum('credit');
            $debit = $account->journalEntryLines->sum('debit');
            $amount = $credit - $debit;

            $revenues[] = [
                'account_code' => $account->code,
                'account_name' => $account->name,
                'amount' => $amount,
            ];

            $totalRevenue += $amount;
        }

        $expenses = [];
        $totalExpense = 0;

        foreach ($expenseAccounts as $account) {
            $debit = $account->journalEntryLines->sum('debit');
            $credit = $account->journalEntryLines->sum('credit');
            $amount = $debit - $credit;

            $expenses[] = [
                'account_code' => $account->code,
                'account_name' => $account->name,
                'amount' => $amount,
            ];

            $totalExpense += $amount;
        }

        $netIncome = $totalRevenue - $totalExpense;

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'revenues' => $revenues,
            'total_revenue' => $totalRevenue,
            'expenses' => $expenses,
            'total_expense' => $totalExpense,
            'net_income' => $netIncome,
        ];
    }

    public function generateBalanceSheet(string $asOfDate): array
    {
        $this->logInfo('Generating balance sheet', ['as_of_date' => $asOfDate]);

        // Get accounts by type
        $assetAccounts = $this->accountRepository->getAccountsForFinancialStatements(
            AccountType::ASSET,
            self::HISTORICAL_START_DATE,
            $asOfDate
        );

        $liabilityAccounts = $this->accountRepository->getAccountsForFinancialStatements(
            AccountType::LIABILITY,
            self::HISTORICAL_START_DATE,
            $asOfDate
        );

        $equityAccounts = $this->accountRepository->getAccountsForFinancialStatements(
            AccountType::EQUITY,
            self::HISTORICAL_START_DATE,
            $asOfDate
        );

        $assets = [];
        $totalAssets = 0;

        foreach ($assetAccounts as $account) {
            $debit = $account->journalEntryLines->sum('debit');
            $credit = $account->journalEntryLines->sum('credit');
            $amount = $account->opening_balance + $debit - $credit;

            $assets[] = [
                'account_code' => $account->code,
                'account_name' => $account->name,
                'amount' => $amount,
            ];

            $totalAssets += $amount;
        }

        $liabilities = [];
        $totalLiabilities = 0;

        foreach ($liabilityAccounts as $account) {
            $credit = $account->journalEntryLines->sum('credit');
            $debit = $account->journalEntryLines->sum('debit');
            $amount = $account->opening_balance + $credit - $debit;

            $liabilities[] = [
                'account_code' => $account->code,
                'account_name' => $account->name,
                'amount' => $amount,
            ];

            $totalLiabilities += $amount;
        }

        $equity = [];
        $totalEquity = 0;

        foreach ($equityAccounts as $account) {
            $credit = $account->journalEntryLines->sum('credit');
            $debit = $account->journalEntryLines->sum('debit');
            $amount = $account->opening_balance + $credit - $debit;

            $equity[] = [
                'account_code' => $account->code,
                'account_name' => $account->name,
                'amount' => $amount,
            ];

            $totalEquity += $amount;
        }

        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;

        return [
            'as_of_date' => $asOfDate,
            'assets' => $assets,
            'total_assets' => $totalAssets,
            'liabilities' => $liabilities,
            'total_liabilities' => $totalLiabilities,
            'equity' => $equity,
            'total_equity' => $totalEquity,
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
            'is_balanced' => bccomp($totalAssets, $totalLiabilitiesAndEquity, 2) === 0,
        ];
    }
}
