<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Models\FinanceLedgerEntry;

final class LedgerPostingService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly AccountBalanceService $balances,
        private readonly LedgerBalanceProjectionService $ledgerBalances,
    ) {}

    public function post(FinanceJournalEntry $journal): int
    {
        if ($journal->ledgerEntries()->exists()) {
            throw new InvalidArgumentException('Journal has already been posted to ledger.');
        }

        $accountIds = $journal->lines
            ->pluck('account_id')
            ->map(static fn (mixed $accountId): int => (int) $accountId)
            ->unique()
            ->sort()
            ->values();

        $accounts = FinanceAccount::query()
            ->where('tenant_id', (int) $journal->tenant_id)
            ->whereIn('id', $accountIds->all())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($accounts->count() !== $accountIds->count()) {
            throw new InvalidArgumentException('Journal contains an unavailable Finance account.');
        }

        $count = 0;

        foreach ($journal->lines as $line) {
            $account = $accounts->get((int) $line->account_id);
            if (! $account instanceof FinanceAccount) {
                throw new InvalidArgumentException('Journal contains an unavailable Finance account.');
            }

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
                'balance_after' => '0.000000',
                'source_module' => $journal->source_module,
                'source_type' => $journal->source_type,
                'source_id' => $journal->source_id,
                'source_number' => $journal->source_number,
                'source_date' => $journal->source_date,
                'source_line_type' => $line->source_line_type,
                'source_line_id' => $line->source_line_id,
            ]);

            $line->setRelation('account', $account);
            $this->balances->applyJournalLine($journal, $line);
            $count++;
        }

        $this->ledgerBalances->rebuildForAccounts($accountIds->all());

        return $count;
    }
}
