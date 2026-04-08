<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Services;
use Modules\Accounting\Application\Contracts\GenerateFinancialReportServiceInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\JournalEntryLineRepositoryInterface;
class GenerateFinancialReportService implements GenerateFinancialReportServiceInterface
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly JournalEntryRepositoryInterface $journalEntryRepository,
        private readonly JournalEntryLineRepositoryInterface $journalEntryLineRepository,
    ) {}
    public function generateBalanceSheet(string $tenantId, string $asOfDate): array
    {
        $accounts = $this->accountRepository->findAll($tenantId);
        $entries  = $this->journalEntryRepository->findAll($tenantId, [
            'status'  => 'posted',
            'to_date' => $asOfDate,
        ]);
        $balances    = $this->computeBalances($tenantId, $entries);
        $assets      = [];
        $liabilities = [];
        $equity      = [];
        foreach ($accounts as $account) {
            $balance = $balances[$account->id] ?? 0.0;
            $row = ['account' => $account->code . ' ' . $account->name, 'balance' => $balance];
            match ($account->type) {
                'asset'     => $assets[]      = $row,
                'liability' => $liabilities[] = $row,
                'equity'    => $equity[]       = $row,
                default     => null,
            };
        }
        return [
            'as_of'       => $asOfDate,
            'assets'      => $assets,
            'liabilities' => $liabilities,
            'equity'      => $equity,
        ];
    }
    public function generateProfitAndLoss(string $tenantId, string $fromDate, string $toDate): array
    {
        $accounts = $this->accountRepository->findAll($tenantId);
        $entries  = $this->journalEntryRepository->findAll($tenantId, [
            'status'    => 'posted',
            'from_date' => $fromDate,
            'to_date'   => $toDate,
        ]);
        $balances = $this->computeBalances($tenantId, $entries);
        $income   = [];
        $expenses = [];
        foreach ($accounts as $account) {
            $balance = $balances[$account->id] ?? 0.0;
            if (abs($balance) < PHP_FLOAT_EPSILON) {
                continue;
            }
            $row = ['account' => $account->code . ' ' . $account->name, 'balance' => $balance];
            match ($account->type) {
                'income'  => $income[]   = $row,
                'expense' => $expenses[] = $row,
                default   => null,
            };
        }
        $totalIncome  = array_sum(array_column($income, 'balance'));
        $totalExpense = array_sum(array_column($expenses, 'balance'));
        return [
            'from'       => $fromDate,
            'to'         => $toDate,
            'income'     => $income,
            'expenses'   => $expenses,
            'net_income' => $totalIncome - $totalExpense,
        ];
    }
    public function generateCashFlow(string $tenantId, string $fromDate, string $toDate): array
    {
        $entries      = $this->journalEntryRepository->findAll($tenantId, [
            'status'    => 'posted',
            'from_date' => $fromDate,
            'to_date'   => $toDate,
        ]);
        $totalDebits  = 0.0;
        $totalCredits = 0.0;
        foreach ($entries as $entry) {
            $lines = $this->journalEntryLineRepository->findByJournalEntry($tenantId, $entry->id);
            foreach ($lines as $line) {
                if ($line->isDebit()) {
                    $totalDebits += $line->amount;
                } else {
                    $totalCredits += $line->amount;
                }
            }
        }
        return [
            'from'          => $fromDate,
            'to'            => $toDate,
            'total_debits'  => $totalDebits,
            'total_credits' => $totalCredits,
            'net_cash_flow' => $totalDebits - $totalCredits,
        ];
    }
    /** @param \Modules\Accounting\Domain\Entities\JournalEntry[] $entries */
    private function computeBalances(string $tenantId, array $entries): array
    {
        $balances = [];
        foreach ($entries as $entry) {
            $lines = $this->journalEntryLineRepository->findByJournalEntry($tenantId, $entry->id);
            foreach ($lines as $line) {
                $balances[$line->accountId] ??= 0.0;
                if ($line->isDebit()) {
                    $balances[$line->accountId] += $line->amount;
                } else {
                    $balances[$line->accountId] -= $line->amount;
                }
            }
        }
        return $balances;
    }
}
