<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Models\FinanceLedgerEntry;

final class LedgerPostingService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly AccountBalanceService $balances,
    ) {}

    public function post(FinanceJournalEntry $journal): int
    {
        if ($journal->ledgerEntries()->exists()) {
            throw new \InvalidArgumentException('Journal has already been posted to ledger.');
        }

        $count = 0;

        foreach ($journal->lines as $line) {
            $account = $line->account()->lockForUpdate()->firstOrFail();
            $balanceAfter = $this->balances->accountBalanceAfter($account, (string) $line->debit, (string) $line->credit);

            FinanceLedgerEntry::query()->create([
                'tenant_id' => $journal->tenant_id,
                'organization_unit_id' => $journal->organization_unit_id,
                'journal_entry_id' => $journal->getKey(),
                'journal_line_id' => $line->getKey(),
                'account_id' => $account->getKey(),
                'dimension_id' => $line->dimension_id,
                'entry_date' => $journal->journal_date,
                'debit' => $this->math->normalize((string) $line->debit),
                'credit' => $this->math->normalize((string) $line->credit),
                'balance_after' => $balanceAfter,
                'source_module' => $journal->source_module,
                'source_type' => $journal->source_type,
                'source_id' => $journal->source_id,
                'source_number' => $journal->source_number,
                'source_date' => $journal->source_date,
                'source_line_type' => $line->source_line_type,
                'source_line_id' => $line->source_line_id,
            ]);

            $account->forceFill([
                'current_balance' => $balanceAfter,
            ])->save();

            $line->setRelation('account', $account->refresh());
            $this->balances->applyJournalLine($journal, $line);
            $count++;
        }

        return $count;
    }
}
