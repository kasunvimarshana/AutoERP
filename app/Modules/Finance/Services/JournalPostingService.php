<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\DTOs\JournalPostingResult;
use Modules\Finance\Enums\JournalStatus;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Validators\FinanceValidationService;

final class JournalPostingService
{
    public function __construct(
        private readonly FinanceValidationService $validator,
        private readonly FiscalPeriodService $periods,
        private readonly LedgerPostingService $ledger,
    ) {}

    public function post(FinanceJournalEntry $journal, ?int $postedBy = null): JournalPostingResult
    {
        return DB::transaction(function () use ($journal, $postedBy): JournalPostingResult {
            $journal = FinanceJournalEntry::query()
                ->with(['lines.account', 'fiscalPeriod'])
                ->lockForUpdate()
                ->findOrFail($journal->getKey());

            $this->periods->assertOpen($journal->fiscalPeriod);
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
}
