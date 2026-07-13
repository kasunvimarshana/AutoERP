<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Finance\DTOs\JournalPostingResult;
use Modules\Finance\Enums\JournalStatus;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Validators\FinanceValidationService;

final class JournalPostingService
{
    public function __construct(
        private readonly FinanceValidationService $validator,
        private readonly LedgerPostingService $ledger,
        private readonly AccountingPeriodService $periods,
    ) {}

    public function post(FinanceJournalEntry $journal, ?int $postedBy = null): JournalPostingResult
    {
        return DB::transaction(function () use ($journal, $postedBy): JournalPostingResult {
            $journal = FinanceJournalEntry::query()
                ->with(['lines.account'])
                ->lockForUpdate()
                ->findOrFail($journal->getKey());
            $status = $this->statusOf($journal);

            if (in_array($status, [JournalStatus::Posted, JournalStatus::Reversed], true)) {
                return $this->resultFromJournal($journal, $status);
            }
            if ($status !== JournalStatus::Draft) {
                throw new InvalidArgumentException('Only draft journals can be posted.');
            }

            $this->periods->assertPostingDateAllowed(
                (int) $journal->tenant_id,
                $journal->organization_unit_id === null ? null : (int) $journal->organization_unit_id,
                $journal->journal_date->toDateString(),
            );
            $this->validator->validateForPosting($journal);

            $ledgerCount = $this->ledger->post($journal);

            $journal->forceFill([
                'status' => JournalStatus::Posted->value,
                'posted_by' => $postedBy,
                'posted_at' => now(),
            ])->save();

            return new JournalPostingResult(
                journalEntryId: (int) $journal->getKey(),
                journalNumber: (string) $journal->journal_number,
                status: JournalStatus::Posted,
                totalDebit: (string) $journal->total_debit,
                totalCredit: (string) $journal->total_credit,
                ledgerEntryCount: $ledgerCount,
            );
        });
    }

    private function resultFromJournal(FinanceJournalEntry $journal, JournalStatus $status): JournalPostingResult
    {
        return new JournalPostingResult(
            journalEntryId: (int) $journal->getKey(),
            journalNumber: (string) $journal->journal_number,
            status: $status,
            totalDebit: (string) $journal->total_debit,
            totalCredit: (string) $journal->total_credit,
            ledgerEntryCount: $journal->ledgerEntries()->count(),
        );
    }

    private function statusOf(FinanceJournalEntry $journal): JournalStatus
    {
        return $journal->status instanceof JournalStatus
            ? $journal->status
            : JournalStatus::from((string) $journal->status);
    }
}
