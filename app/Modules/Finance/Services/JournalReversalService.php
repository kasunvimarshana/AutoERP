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

    public function reverse(FinanceJournalEntry $journal, string $reversalDate, ?int $reversedBy = null): FinanceJournalEntry
    {
        return DB::transaction(function () use ($journal, $reversalDate, $reversedBy): FinanceJournalEntry {
            $journal = FinanceJournalEntry::query()
                ->with(['lines', 'reversals'])
                ->lockForUpdate()
                ->findOrFail($journal->getKey());

            $this->validator->assertReversible($journal);

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
                fiscalYearId: $journal->fiscal_year_id,
                fiscalPeriodId: $journal->fiscal_period_id,
                source: new PostingSourceData('finance_journal_entry', (int) $journal->getKey()),
                description: 'Reversal of '.$journal->journal_number,
                currencyId: $journal->currency_id,
                exchangeRate: (string) $journal->exchange_rate,
                createdBy: $reversedBy,
                lines: $lines,
            ));

            $reversal->forceFill([
                'reversal_of_id' => $journal->getKey(),
            ])->save();

            $this->posting->post($reversal, $reversedBy);

            $journal->forceFill([
                'status' => JournalStatus::Reversed->value,
                'reversed_by' => $reversedBy,
                'reversed_at' => now(),
            ])->save();

            return $reversal->refresh()->load(['lines', 'ledgerEntries']);
        });
    }
}
