<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\DTOs\CreateJournalEntryData;
use Modules\Finance\DTOs\FinancePostingRequest;
use Modules\Finance\DTOs\JournalLineData;
use Modules\Finance\DTOs\PostingResultData;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Finance\Enums\JournalStatus;
use Modules\Finance\Enums\JournalType;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceJournalEntry;

final class FinancePostingService implements FinancePostingInterface
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly JournalEntryCreationService $journals,
        private readonly JournalPostingService $posting,
        private readonly JournalReversalService $reversals,
    ) {}

    public function createDraftJournal(FinancePostingRequest $request): PostingResultData
    {
        $this->validatePosting($request);

        $journal = $this->journals->create(new CreateJournalEntryData(
            tenantId: $request->source->tenantId,
            journalDate: $request->postingDate,
            journalType: $this->journalTypeForSource($request->source->sourceType),
            organizationUnitId: $request->source->organizationUnitId,
            source: new PostingSourceData($request->source->sourceType, $request->source->sourceId),
            description: $request->description,
            currencyId: $request->currencyId,
            exchangeRate: $request->exchangeRate,
            lines: $this->journalLines($request),
        ));

        return $this->resultFromJournal($journal);
    }

    public function validatePosting(FinancePostingRequest $request): void
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

        if ($this->math->isNegative($request->exchangeRate) || $this->math->isZero($request->exchangeRate)) {
            throw new InvalidArgumentException('Posting exchange rate must be greater than zero.');
        }

        $totalDebit = '0.000000';
        $totalCredit = '0.000000';

        foreach ($request->lines as $line) {
            if ($this->math->isNegative($line->debit) || $this->math->isNegative($line->credit)) {
                throw new InvalidArgumentException('Posting line debit and credit cannot be negative.');
            }

            $account = $this->resolveAccount($request, $line->accountCode);
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

    public function reverseJournal(int $journalId, string $reversalDate, ?int $reversedBy = null): PostingResultData
    {
        $reversal = $this->reversals->reverse(
            FinanceJournalEntry::query()->findOrFail($journalId),
            $reversalDate,
            $reversedBy,
        );

        return $this->resultFromJournal($reversal);
    }

    /**
     * @return list<JournalLineData>
     */
    private function journalLines(FinancePostingRequest $request): array
    {
        $lines = [];
        foreach ($request->lines as $index => $line) {
            $account = $this->resolveAccount($request, $line->accountCode);
            $lines[] = new JournalLineData(
                accountId: (int) $account->getKey(),
                lineNumber: $index + 1,
                debit: $line->debit,
                credit: $line->credit,
                description: $line->description,
            );
        }

        return $lines;
    }

    private function resolveAccount(FinancePostingRequest $request, string $accountCode): FinanceAccount
    {
        $query = FinanceAccount::query()
            ->where('tenant_id', $request->source->tenantId)
            ->where('code', $accountCode);

        $request->source->organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $request->source->organizationUnitId);

        return $query->firstOrFail();
    }

    private function journalTypeForSource(string $sourceType): JournalType
    {
        return match ($sourceType) {
            'invoice' => JournalType::Invoice,
            'payment' => JournalType::Payment,
            default => JournalType::General,
        };
    }

    private function resultFromJournal(FinanceJournalEntry $journal): PostingResultData
    {
        $status = $journal->status instanceof JournalStatus
            ? $journal->status->value
            : (string) $journal->status;

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
