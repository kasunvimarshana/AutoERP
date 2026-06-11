<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\DTOs\CreateJournalEntryData;
use Modules\Finance\DTOs\JournalLineData;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Finance\Enums\JournalStatus;
use Modules\Finance\Enums\JournalType;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Validators\FinanceValidationService;

final class JournalReversalService
{
    public function __construct(
        private readonly FinanceValidationService $validator,
        private readonly JournalEntryCreationService $journals,
        private readonly JournalPostingService $posting,
    ) {}

    public function reverse(
        FinanceJournalEntry $journal,
        string $reversalDate,
        ?int $reversedBy = null,
        ?string $reason = null,
    ): FinanceJournalEntry {
        return DB::transaction(function () use ($journal, $reversalDate, $reversedBy, $reason): FinanceJournalEntry {
            $journal = FinanceJournalEntry::query()
                ->with(['lines', 'reversals'])
                ->lockForUpdate()
                ->findOrFail($journal->getKey());

            $this->validator->assertReversible($journal);
            if ($reversalDate < $journal->journal_date->toDateString()) {
                throw new \InvalidArgumentException('Reversal date cannot be before the original journal date.');
            }

            $reason = trim((string) $reason);
            if ($reason === '') {
                $reason = 'Accounting reversal';
            }

            $lines = [];
            foreach ($journal->lines as $line) {
                $lines[] = new JournalLineData(
                    accountId: (int) $line->account_id,
                    lineNumber: (int) $line->line_number,
                    debit: (string) $line->credit,
                    credit: (string) $line->debit,
                    description: 'Reversal: '.(string) ($line->description ?? ''),
                    dimensionId: $line->dimension_id,
                    sourceLineType: $line->source_line_type,
                    sourceLineId: $line->source_line_id,
                );
            }

            $reversal = $this->journals->create(new CreateJournalEntryData(
                tenantId: (int) $journal->tenant_id,
                journalDate: $reversalDate,
                journalType: JournalType::Reversal,
                organizationUnitId: $journal->organization_unit_id,
                source: new PostingSourceData(
                    sourceType: (string) ($journal->source_type ?: 'finance_journal_entry'),
                    sourceId: (int) ($journal->source_id ?: $journal->getKey()),
                    tenantId: (int) $journal->tenant_id,
                    organizationUnitId: $journal->organization_unit_id,
                    sourceModule: (string) ($journal->source_module ?: 'finance'),
                    sourceNumber: $journal->source_number,
                    sourceDate: $journal->source_date?->toDateString(),
                ),
                description: 'Reversal of '.$journal->journal_number.': '.$reason,
                currencyId: $journal->currency_id,
                exchangeRate: (string) $journal->exchange_rate,
                createdBy: $reversedBy,
                lines: $lines,
                postingProfileId: $journal->posting_profile_id,
                reversalOfId: (int) $journal->getKey(),
                reversalReason: $reason,
            ));

            $this->posting->post($reversal, $reversedBy);

            $journal->forceFill([
                'status' => JournalStatus::Reversed->value,
                'reversed_by' => $reversedBy,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
            ])->save();

            return $reversal->refresh()->load(['lines', 'ledgerEntries']);
        });
    }
}
