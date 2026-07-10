<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Database\QueryException;
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

final class FinancePostingService implements FinancePostingInterface
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly JournalEntryCreationService $journals,
        private readonly JournalPostingService $posting,
        private readonly JournalReversalService $reversals,
        private readonly PostingProfileService $profiles,
    ) {}

    public function createDraftJournal(PostingContext $request): PostingResultData
    {
        return DB::transaction(function () use ($request): PostingResultData {
            $profile = $this->profiles->resolveProfile($request);
            $this->validatePosting($request, $profile);
            $lines = $this->journalLines($request, $profile);
            $sourceKey = $this->sourceKey($request);
            $fingerprint = $this->postingFingerprint($request, $profile, $lines);

            $existing = $this->sourceJournal($sourceKey);
            if ($existing instanceof FinanceJournalEntry) {
                return $this->assertReplayMatches($existing, $fingerprint);
            }

            try {
                $journal = $this->journals->create(new CreateJournalEntryData(
                    tenantId: $request->source->tenantId,
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
                    ),
                    description: $request->description,
                    currencyId: $request->currencyId,
                    exchangeRate: $request->exchangeRate,
                    lines: $lines,
                    postingProfileId: $profile?->getKey(),
                    sourceKey: $sourceKey,
                    postingFingerprint: $fingerprint,
                ));
            } catch (QueryException $exception) {
                if (! $this->isUniqueViolation($exception)) {
                    throw $exception;
                }

                $journal = $this->sourceJournal($sourceKey);
                if (! $journal instanceof FinanceJournalEntry) {
                    throw $exception;
                }

                return $this->assertReplayMatches($journal, $fingerprint);
            }

            return $this->resultFromJournal($journal);
        });
    }

    public function validatePosting(PostingContext $request, ?FinancePostingProfile $profile = null): void
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

        if (trim($request->source->sourceType) === '' || $request->source->sourceId < 1) {
            throw new InvalidArgumentException('Posting source type and ID are required.');
        }

        $profile ??= $this->profiles->resolveProfile($request);

        if ($this->math->isNegative($request->exchangeRate) || $this->math->isZero($request->exchangeRate)) {
            throw new InvalidArgumentException('Posting exchange rate must be greater than zero.');
        }

        $totalDebit = '0.000000';
        $totalCredit = '0.000000';

        foreach ($request->lines as $line) {
            if ($this->math->isNegative($line->debit) || $this->math->isNegative($line->credit)) {
                throw new InvalidArgumentException('Posting line debit and credit cannot be negative.');
            }

            $account = $this->profiles->resolveAccount($request, $line, $profile);
            if (! (bool) $account->is_active || ! (bool) $account->is_posting_account) {
                throw new InvalidArgumentException('Posting account must be active and postable.');
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

            return $this->postJournal($draft->journalId, $postedBy);
        });
    }

    public function postJournal(int $journalId, ?int $postedBy = null): PostingResultData
    {
        $journal = FinanceJournalEntry::query()->lockForUpdate()->findOrFail($journalId);
        $status = $this->statusOf($journal);

        if (in_array($status, [JournalStatus::Posted, JournalStatus::Reversed], true)) {
            return $this->resultFromJournal($journal);
        }
        if ($status !== JournalStatus::Draft) {
            throw new InvalidArgumentException('Only draft journals can be posted.');
        }

        $result = $this->posting->post($journal, $postedBy);

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
        $reversal = $this->reversals->reverse(
            FinanceJournalEntry::query()->findOrFail($journalId),
            $reversalDate,
            $reversedBy,
            $reason,
        );

        return $this->resultFromJournal($reversal);
    }

    /**
     * @return list<JournalLineData>
     */
    private function journalLines(
        PostingContext $request,
        ?FinancePostingProfile $profile,
    ): array {
        $lines = [];
        foreach ($request->lines as $index => $line) {
            $account = $this->profiles->resolveAccount($request, $line, $profile);
            $dimension = $this->profiles->resolveDimension($request, $line->dimensionCode);
            $lines[] = new JournalLineData(
                accountId: (int) $account->getKey(),
                lineNumber: $index + 1,
                debit: $this->math->normalize($line->debit),
                credit: $this->math->normalize($line->credit),
                description: $line->description,
                dimensionId: $dimension?->getKey(),
                sourceLineType: $line->sourceLineType,
                sourceLineId: $line->sourceLineId,
            );
        }

        return $lines;
    }

    private function sourceKey(PostingContext $request): string
    {
        return hash('sha256', json_encode([
            'tenant_id' => $request->source->tenantId,
            'organization_unit_id' => $request->source->organizationUnitId,
            'source_module' => $request->source->sourceModule ?: $request->source->sourceType,
            'source_type' => $request->source->sourceType,
            'source_id' => $request->source->sourceId,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @param  list<JournalLineData>  $lines
     */
    private function postingFingerprint(
        PostingContext $request,
        ?FinancePostingProfile $profile,
        array $lines,
    ): string {
        return hash('sha256', json_encode([
            'posting_date' => $request->postingDate,
            'source_number' => $request->source->sourceNumber,
            'source_date' => $request->source->sourceDate ?: $request->postingDate,
            'description' => $request->description,
            'currency_id' => $request->currencyId,
            'exchange_rate' => $this->math->normalize($request->exchangeRate),
            'posting_profile_id' => $profile?->getKey(),
            'lines' => array_map(fn (JournalLineData $line): array => [
                'line_number' => $line->lineNumber,
                'account_id' => $line->accountId,
                'debit' => $this->math->normalize($line->debit),
                'credit' => $this->math->normalize($line->credit),
                'description' => $line->description,
                'dimension_id' => $line->dimensionId,
                'source_line_type' => $line->sourceLineType,
                'source_line_id' => $line->sourceLineId,
            ], $lines),
        ], JSON_THROW_ON_ERROR));
    }

    private function sourceJournal(string $sourceKey): ?FinanceJournalEntry
    {
        return FinanceJournalEntry::query()
            ->where('source_key', $sourceKey)
            ->lockForUpdate()
            ->first();
    }

    private function assertReplayMatches(FinanceJournalEntry $journal, string $fingerprint): PostingResultData
    {
        $stored = trim((string) $journal->posting_fingerprint);
        if ($stored === '' || ! hash_equals($stored, $fingerprint)) {
            throw new InvalidArgumentException('Finance posting source was already used with different posting facts.');
        }

        return $this->resultFromJournal($journal);
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);

        return $driverCode === 1062 || $sqlState === '23505';
    }

    private function statusOf(FinanceJournalEntry $journal): JournalStatus
    {
        return $journal->status instanceof JournalStatus
            ? $journal->status
            : JournalStatus::from((string) $journal->status);
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
        return new PostingResultData(
            journalId: (int) $journal->getKey(),
            journalNumber: (string) $journal->journal_number,
            status: $this->statusOf($journal)->value,
            totalDebit: (string) $journal->total_debit,
            totalCredit: (string) $journal->total_credit,
            ledgerEntryCount: $journal->ledgerEntries()->count(),
        );
    }
}
