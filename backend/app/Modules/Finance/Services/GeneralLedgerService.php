<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Repositories\AccountRepository;
use App\Modules\Finance\Repositories\JournalEntryRepository;
use App\Services\BaseService;

/**
 * General Ledger Service
 *
 * Handles general ledger operations including account balances and transactions.
 */
class GeneralLedgerService extends BaseService
{
    public function __construct(
        protected AccountRepository $accountRepository,
        protected JournalEntryRepository $journalEntryRepository,
        protected AccountService $accountService
    ) {}

    public function getAccountLedger(int $accountId, string $startDate, string $endDate): array
    {
        $this->logInfo('Getting account ledger', compact('accountId', 'startDate', 'endDate'));

        $account = $this->accountRepository->findOrFail($accountId);

        $journalEntries = $this->journalEntryRepository->getEntriesByAccount($accountId, $startDate, $endDate);

        $openingBalance = $this->accountService->getAccountBalance($accountId, $startDate);
        $runningBalance = $openingBalance;
        $transactions = [];

        foreach ($journalEntries as $entry) {
            foreach ($entry->lines as $line) {
                if ($line->account_id === $accountId) {
                    if ($account->type->isDebitNormal()) {
                        $runningBalance += $line->debit - $line->credit;
                    } else {
                        $runningBalance += $line->credit - $line->debit;
                    }

                    $transactions[] = [
                        'date' => $entry->entry_date->format('Y-m-d'),
                        'entry_number' => $entry->entry_number,
                        'description' => $line->description ?? $entry->description,
                        'debit' => $line->debit,
                        'credit' => $line->credit,
                        'balance' => $runningBalance,
                    ];
                }
            }
        }

        return [
            'account' => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type->label(),
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'opening_balance' => $openingBalance,
            'transactions' => $transactions,
            'closing_balance' => $runningBalance,
        ];
    }

    public function getGeneralLedger(string $startDate, string $endDate): array
    {
        $this->logInfo('Getting general ledger', compact('startDate', 'endDate'));

        $accounts = $this->accountRepository->getActiveAccounts();
        $ledgers = [];

        foreach ($accounts as $account) {
            $ledger = $this->getAccountLedger($account->id, $startDate, $endDate);

            // Only include accounts with transactions
            if (! empty($ledger['transactions'])) {
                $ledgers[] = $ledger;
            }
        }

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'ledgers' => $ledgers,
        ];
    }
}
