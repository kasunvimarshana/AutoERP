<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\DTOs\CreateJournalEntryData;
use Modules\Finance\Enums\JournalStatus;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Models\FinanceJournalLine;
use Modules\Finance\Validators\FinanceValidationService;

final class JournalEntryCreationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly FinanceValidationService $validator,
        private readonly JournalNumberService $numbers,
        private readonly FiscalPeriodService $periods,
    ) {}

    public function create(CreateJournalEntryData $data): FinanceJournalEntry
    {
        $this->validator->validateJournalCreation($data);
        [$totalDebit, $totalCredit] = $this->validator->journalTotals($data->lines);
        $period = $this->periods->resolve(
            $data->tenantId,
            $data->organizationUnitId,
            $data->journalDate,
            $data->fiscalPeriodId,
        );

        return DB::transaction(function () use ($data, $totalDebit, $totalCredit, $period): FinanceJournalEntry {
            $journal = FinanceJournalEntry::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'journal_number' => $this->numbers->resolve($data),
                'journal_date' => $data->journalDate,
                'fiscal_year_id' => $period->fiscal_year_id,
                'fiscal_period_id' => $period->getKey(),
                'posting_profile_id' => $data->postingProfileId,
                'source_module' => $data->source?->sourceModule,
                'source_type' => $data->source?->sourceType,
                'source_id' => $data->source?->sourceId,
                'source_number' => $data->source?->sourceNumber,
                'source_date' => $data->source?->sourceDate,
                'source_key' => $data->sourceKey,
                'posting_fingerprint' => $data->postingFingerprint,
                'journal_type' => $data->journalType->value,
                'status' => $data->status->value,
                'description' => $data->description,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'currency_id' => $data->currencyId,
                'exchange_rate' => $this->math->normalize($data->exchangeRate),
                'created_by' => $data->createdBy,
                'reversal_of_id' => $data->reversalOfId,
                'reversal_reason' => $data->reversalReason,
            ]);

            $this->saveLines($journal, $data);

            return $journal->load(['lines.account', 'fiscalPeriod']);
        });
    }

    public function update(FinanceJournalEntry $journal, CreateJournalEntryData $data): FinanceJournalEntry
    {
        $this->validator->validateJournalCreation($data);
        [$totalDebit, $totalCredit] = $this->validator->journalTotals($data->lines);
        $period = $this->periods->resolve(
            $data->tenantId,
            $data->organizationUnitId,
            $data->journalDate,
            $data->fiscalPeriodId,
        );

        return DB::transaction(function () use ($journal, $data, $period, $totalDebit, $totalCredit): FinanceJournalEntry {
            $journal = FinanceJournalEntry::query()->lockForUpdate()->findOrFail($journal->getKey());
            $this->assertDraft($journal, 'Only draft journals can be edited.');

            $journal->lines()->delete();
            $journal->forceFill([
                'journal_date' => $data->journalDate,
                'fiscal_year_id' => $period->fiscal_year_id,
                'fiscal_period_id' => $period->getKey(),
                'posting_profile_id' => $data->postingProfileId,
                'source_module' => $data->source?->sourceModule,
                'source_type' => $data->source?->sourceType,
                'source_id' => $data->source?->sourceId,
                'source_number' => $data->source?->sourceNumber,
                'source_date' => $data->source?->sourceDate,
                'source_key' => $data->sourceKey,
                'posting_fingerprint' => $data->postingFingerprint,
                'journal_type' => $data->journalType->value,
                'description' => $data->description,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'currency_id' => $data->currencyId,
                'exchange_rate' => $this->math->normalize($data->exchangeRate),
            ])->save();

            $this->saveLines($journal, $data);

            return $journal->refresh()->load(['lines.account', 'fiscalPeriod', 'postingProfile']);
        });
    }

    public function cancel(FinanceJournalEntry $journal): FinanceJournalEntry
    {
        return DB::transaction(function () use ($journal): FinanceJournalEntry {
            $journal = FinanceJournalEntry::query()->lockForUpdate()->findOrFail($journal->getKey());
            $this->assertDraft($journal, 'Only draft journals can be cancelled.');
            $journal->forceFill(['status' => JournalStatus::Cancelled->value])->save();

            return $journal->refresh();
        });
    }

    private function saveLines(FinanceJournalEntry $journal, CreateJournalEntryData $data): void
    {
        foreach ($data->lines as $line) {
            FinanceJournalLine::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'journal_entry_id' => $journal->getKey(),
                'account_id' => $line->accountId,
                'description' => $line->description,
                'debit' => $this->math->normalize($line->debit),
                'credit' => $this->math->normalize($line->credit),
                'dimension_id' => $line->dimensionId,
                'source_line_type' => $line->sourceLineType,
                'source_line_id' => $line->sourceLineId,
                'line_number' => $line->lineNumber,
            ]);
        }
    }

    private function assertDraft(FinanceJournalEntry $journal, string $message): void
    {
        $status = $journal->status instanceof JournalStatus
            ? $journal->status
            : JournalStatus::from((string) $journal->status);

        if ($status !== JournalStatus::Draft) {
            throw new \InvalidArgumentException($message);
        }
    }
}
