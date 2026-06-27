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
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class JournalReversalService
{
    private const REVERSAL_POSTING_KEY_PREFIX = 'reversal:';

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
        ?int $expectedVersion = null,
    ): FinanceJournalEntry {
        return DB::transaction(function () use (
            $journal,
            $reversalDate,
            $reversedBy,
            $reason,
            $expectedVersion,
        ): FinanceJournalEntry {
            $journal = FinanceJournalEntry::query()
                ->with(['lines', 'reversals'])
                ->lockForUpdate()
                ->findOrFail($journal->getKey());

            if ($expectedVersion !== null && $expectedVersion !== (int) $journal->row_version) {
                throw new ConflictHttpException('Finance journal was changed by another request.');
            }
            $this->validator->assertReversible($journal);
            if ($reversalDate < $journal->journal_date->toDateString()) {
                throw new \InvalidArgumentException('Reversal date cannot be before the original journal date.');
            }

            $reason = trim((string) $reason);
            if ($reason === '') {
                throw new \InvalidArgumentException('A reversal reason is required.');
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
                    accountRoleId: $line->account_role_id,
                    accountCodeSnapshot: (string) $line->account_code_snapshot,
                    accountNameSnapshot: (string) $line->account_name_snapshot,
                    accountRoleCodeSnapshot: $line->account_role_code_snapshot,
                );
            }

            $reversal = $this->journals->create(new CreateJournalEntryData(
                tenantId: (int) $journal->tenant_id,
                journalDate: $reversalDate,
                journalType: JournalType::Reversal,
                organizationUnitId: $journal->organization_unit_id,
                source: new PostingSourceData(
                    sourceType: 'finance_journal_reversal',
                    sourceId: (int) $journal->getKey(),
                    tenantId: (int) $journal->tenant_id,
                    organizationUnitId: $journal->organization_unit_id,
                    sourceModule: 'finance',
                    sourceNumber: (string) $journal->journal_number,
                    sourceDate: $journal->journal_date->toDateString(),
                    postingKey: self::REVERSAL_POSTING_KEY_PREFIX.$journal->getKey(),
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

            $this->posting->post($reversal, $reversedBy, (int) $reversal->row_version);

            $journal->forceFill([
                'row_version' => (int) $journal->row_version + 1,
                'status' => JournalStatus::Reversed->value,
                'reversed_by' => $reversedBy,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
            ])->save();

            return $reversal->refresh()->load(['lines', 'ledgerEntries']);
        }, 3);
    }
}
