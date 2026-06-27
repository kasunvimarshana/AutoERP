<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\DTOs\CreateJournalEntryData;
use Modules\Finance\Enums\JournalStatus;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceAccountRole;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Models\FinanceJournalLine;
use Modules\Finance\Validators\FinanceValidationService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class JournalEntryCreationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly FinanceValidationService $validator,
        private readonly JournalNumberService $numbers,
        private readonly FiscalPeriodService $periods,
        private readonly JournalSourceIdentityService $sourceIdentity,
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
        $identityKey = $data->source === null ? null : $this->sourceIdentity->identityKey($data->source);
        $fingerprint = $data->source === null ? null : $this->sourceIdentity->fingerprint($data);

        return DB::transaction(function () use (
            $data,
            $totalDebit,
            $totalCredit,
            $period,
            $identityKey,
            $fingerprint,
        ): FinanceJournalEntry {
            if ($identityKey !== null) {
                $existing = FinanceJournalEntry::query()
                    ->where('tenant_id', $data->tenantId)
                    ->where('source_identity_key', $identityKey)
                    ->lockForUpdate()
                    ->first();
                if ($existing instanceof FinanceJournalEntry) {
                    if (! hash_equals((string) $existing->source_fingerprint, (string) $fingerprint)) {
                        throw new ConflictHttpException('Finance source was already posted with different accounting facts.');
                    }

                    return $this->loaded($existing);
                }
            }

            $journal = FinanceJournalEntry::query()->forceCreate([
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
                'posting_key' => $data->source?->postingKey,
                'source_identity_key' => $identityKey,
                'source_fingerprint' => $fingerprint,
                'source_number' => $data->source?->sourceNumber,
                'source_date' => $data->source?->sourceDate,
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

            return $this->loaded($journal);
        }, 3);
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
            $this->assertVersion($journal, $data->expectedVersion);
            if ($journal->source_identity_key !== null) {
                throw new ConflictHttpException('System-generated journals cannot be edited. Reverse the posted document or correct its source workflow.');
            }

            $journal->lines()->delete();
            $journal->forceFill([
                'row_version' => (int) $journal->row_version + 1,
                'journal_date' => $data->journalDate,
                'fiscal_year_id' => $period->fiscal_year_id,
                'fiscal_period_id' => $period->getKey(),
                'posting_profile_id' => null,
                'journal_type' => $data->journalType->value,
                'description' => $data->description,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'currency_id' => $data->currencyId,
                'exchange_rate' => $this->math->normalize($data->exchangeRate),
            ])->save();

            $this->saveLines($journal, $data);

            return $this->loaded($journal->refresh());
        }, 3);
    }

    public function cancel(FinanceJournalEntry $journal, int $expectedVersion): FinanceJournalEntry
    {
        return DB::transaction(function () use ($journal, $expectedVersion): FinanceJournalEntry {
            $journal = FinanceJournalEntry::query()->lockForUpdate()->findOrFail($journal->getKey());
            $this->assertDraft($journal, 'Only draft journals can be cancelled.');
            $this->assertVersion($journal, $expectedVersion);
            $journal->forceFill([
                'row_version' => (int) $journal->row_version + 1,
                'status' => JournalStatus::Cancelled->value,
            ])->save();

            return $journal->refresh();
        }, 3);
    }

    private function saveLines(FinanceJournalEntry $journal, CreateJournalEntryData $data): void
    {
        foreach ($data->lines as $line) {
            $account = FinanceAccount::query()->findOrFail($line->accountId);
            $role = $line->accountRoleId === null
                ? null
                : FinanceAccountRole::query()->findOrFail($line->accountRoleId);

            FinanceJournalLine::query()->forceCreate([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'journal_entry_id' => $journal->getKey(),
                'account_id' => $account->getKey(),
                'account_role_id' => $role?->getKey(),
                'account_code_snapshot' => $line->accountCodeSnapshot ?? (string) $account->code,
                'account_name_snapshot' => $line->accountNameSnapshot ?? (string) $account->name,
                'account_role_code_snapshot' => $line->accountRoleCodeSnapshot ?? $role?->code,
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

    private function loaded(FinanceJournalEntry $journal): FinanceJournalEntry
    {
        return $journal->load([
            'lines.account',
            'lines.accountRole',
            'fiscalPeriod',
            'postingProfile',
            'ledgerEntries',
        ]);
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

    private function assertVersion(FinanceJournalEntry $journal, ?int $expectedVersion): void
    {
        if ($expectedVersion === null || $expectedVersion !== (int) $journal->row_version) {
            throw new ConflictHttpException('Finance journal was changed by another request.');
        }
    }
}
