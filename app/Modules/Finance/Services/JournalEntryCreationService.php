<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\DTOs\CreateJournalEntryData;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Models\FinanceJournalLine;
use Modules\Finance\Validators\FinanceValidationService;

final class JournalEntryCreationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly FinanceValidationService $validator,
        private readonly JournalNumberService $numbers,
    ) {}

    public function create(CreateJournalEntryData $data): FinanceJournalEntry
    {
        $this->validator->validateJournalCreation($data);
        [$totalDebit, $totalCredit] = $this->validator->journalTotals($data->lines);

        return DB::transaction(function () use ($data, $totalDebit, $totalCredit): FinanceJournalEntry {
            $journal = FinanceJournalEntry::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'journal_number' => $this->numbers->resolve($data),
                'journal_date' => $data->journalDate,
                'fiscal_year_id' => $data->fiscalYearId,
                'fiscal_period_id' => $data->fiscalPeriodId,
                'source_type' => $data->source?->sourceType,
                'source_id' => $data->source?->sourceId,
                'journal_type' => $data->journalType->value,
                'status' => $data->status->value,
                'description' => $data->description,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'currency_id' => $data->currencyId,
                'exchange_rate' => $this->math->normalize($data->exchangeRate),
                'created_by' => $data->createdBy,
            ]);

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

            return $journal->load(['lines.account', 'fiscalPeriod']);
        });
    }
}
