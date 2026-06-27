<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\DTOs\CreateJournalEntryData;
use Modules\Finance\DTOs\JournalLineData;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingResultData;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Finance\Enums\JournalStatus;
use Modules\Finance\Enums\JournalType;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Models\FinancePostingProfile;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class FinancePostingService implements FinancePostingInterface
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly JournalEntryCreationService $journals,
        private readonly JournalPostingService $posting,
        private readonly JournalReversalService $reversals,
        private readonly PostingProfileService $profiles,
        private readonly FiscalPeriodService $periods,
    ) {}

    public function createDraftJournal(PostingContext $request): PostingResultData
    {
        $this->validatePosting($request);
        $profile = $this->profiles->resolveProfile($request);

        $journal = $this->journals->create(new CreateJournalEntryData(
            tenantId: (int) $request->source->tenantId,
            journalDate: $request->postingDate,
            journalType: $this->journalTypeForSource($request->source->sourceType),
            organizationUnitId: $request->source->organizationUnitId,
            source: new PostingSourceData(
                sourceType: $request->source->sourceType,
                sourceId: $request->source->sourceId,
                tenantId: $request->source->tenantId,
                organizationUnitId: $request->source->organizationUnitId,
                sourceModule: $request->source->sourceModule ?: $request->source->sourceType,
                sourceNumber: $request->source->sourceNumber,
                sourceDate: $request->source->sourceDate ?: $request->postingDate,
                postingKey: $request->source->postingKey,
            ),
            description: $request->description,
            currencyId: $request->currencyId,
            exchangeRate: $request->exchangeRate,
            lines: $this->journalLines($request, $profile),
            postingProfileId: (int) $profile->getKey(),
        ));

        return $this->resultFromJournal($journal);
    }

    public function validatePosting(PostingContext $request): void
    {
        if (trim($request->postingDate) === '') {
            throw new InvalidArgumentException('Posting date is required.');
        }
        if ($request->lines === []) {
            throw new InvalidArgumentException('Posting request requires at least one line.');
        }
        if ($request->source->tenantId === null) {
            throw new InvalidArgumentException('Posting source tenant is required.');
        }
        if (trim((string) $request->source->sourceModule) === ''
            || trim($request->source->sourceType) === ''
            || $request->source->sourceId < 1
            || trim($request->source->postingKey) === '') {
            throw new InvalidArgumentException('Posting source module, type, ID, and posting key are required.');
        }

        $this->periods->resolve(
            $request->source->tenantId,
            $request->source->organizationUnitId,
            $request->postingDate,
            requireOpen: true,
        );

        $profile = $this->profiles->resolveProfile($request);
        if ($this->math->isNegative($request->exchangeRate) || $this->math->isZero($request->exchangeRate)) {
            throw new InvalidArgumentException('Posting exchange rate must be greater than zero.');
        }

        $totalDebit = '0.000000';
        $totalCredit = '0.000000';
        foreach ($request->lines as $line) {
            if ($this->math->isNegative($line->debit) || $this->math->isNegative($line->credit)) {
                throw new InvalidArgumentException('Posting line debit and credit cannot be negative.');
            }
            if (trim($line->profileKey) === '') {
                throw new InvalidArgumentException('Every operational posting line requires a posting profile key.');
            }

            $assignment = $this->profiles->resolveAssignment($request, $line, $profile);
            if (! $assignment->account->is_active || ! $assignment->account->is_posting_account) {
                throw new InvalidArgumentException('Resolved posting account must be active and postable.');
            }

            $totalDebit = $this->math->add($totalDebit, $line->debit);
            $totalCredit = $this->math->add($totalCredit, $line->credit);
        }

        if ($this->math->compare($totalDebit, $totalCredit) !== 0) {
            throw new InvalidArgumentException('Posting request must be balanced.');
        }
    }

    public function post(PostingContext $request, ?int $postedBy = null): PostingResultData
    {
        return DB::transaction(function () use ($request, $postedBy): PostingResultData {
            $draft = $this->createDraftJournal($request);
            if ($draft->status === JournalStatus::Posted->value) {
                return $draft;
            }
            if ($draft->status !== JournalStatus::Draft->value) {
                throw new ConflictHttpException('Existing source journal is not in a postable state.');
            }

            return $this->postJournal($draft->journalId, $postedBy);
        }, 3);
    }

    public function postJournal(int $journalId, ?int $postedBy = null): PostingResultData
    {
        $result = $this->posting->post(FinanceJournalEntry::query()->findOrFail($journalId), $postedBy);

        return new PostingResultData(
            journalId: $result->journalEntryId,
            journalNumber: $result->journalNumber,
            status: $result->status->value,
            totalDebit: $result->totalDebit,
            totalCredit: $result->totalCredit,
            ledgerEntryCount: $result->ledgerEntryCount,
        );
    }

    public function reverseJournal(
        int $journalId,
        string $reversalDate,
        ?int $reversedBy = null,
        ?string $reason = null,
    ): PostingResultData {
        return $this->resultFromJournal($this->reversals->reverse(
            FinanceJournalEntry::query()->findOrFail($journalId),
            $reversalDate,
            $reversedBy,
            $reason,
        ));
    }

    /** @return list<JournalLineData> */
    private function journalLines(PostingContext $request, FinancePostingProfile $profile): array
    {
        $lines = [];
        foreach ($request->lines as $index => $line) {
            $assignment = $this->profiles->resolveAssignment($request, $line, $profile);
            $dimension = $this->profiles->resolveDimension($request, $line->dimensionCode);
            $lines[] = new JournalLineData(
                accountId: (int) $assignment->account_id,
                lineNumber: $index + 1,
                debit: $line->debit,
                credit: $line->credit,
                description: $line->description,
                dimensionId: $dimension?->getKey(),
                sourceLineType: $line->sourceLineType,
                sourceLineId: $line->sourceLineId,
                accountRoleId: (int) $assignment->account_role_id,
            );
        }

        return $lines;
    }

    private function journalTypeForSource(string $sourceType): JournalType
    {
        return match ($sourceType) {
            'invoice', 'sales_invoice', 'purchase_invoice', 'vehicle_service_invoice' => JournalType::Invoice,
            'payment', 'payment_received', 'payment_made' => JournalType::Payment,
            'contra', 'cash_transfer', 'bank_transfer', 'account_transfer' => JournalType::Contra,
            'adjustment', 'write_off', 'reclassification', 'rounding_adjustment' => JournalType::Adjustment,
            'opening_balance', 'opening' => JournalType::Opening,
            'reversal' => JournalType::Reversal,
            default => JournalType::General,
        };
    }

    private function resultFromJournal(FinanceJournalEntry $journal): PostingResultData
    {
        $status = $journal->status instanceof JournalStatus ? $journal->status->value : (string) $journal->status;

        return new PostingResultData(
            journalId: (int) $journal->getKey(),
            journalNumber: (string) $journal->journal_number,
            status: $status,
            totalDebit: (string) $journal->total_debit,
            totalCredit: (string) $journal->total_credit,
            ledgerEntryCount: $journal->ledgerEntries()->count(),
        );
    }
}
