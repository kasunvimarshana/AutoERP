<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Application\Support\FinancialServiceSupport;

final class JournalEntryService
{
    public function __construct(private readonly FinancialServiceSupport $support) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createJournalEntry(array $payload): array
    {
        return DB::transaction(function () use ($payload): array {
            $tenantId = $this->support->tenantId();
            $organizationUnitId = $this->support->organizationUnitId($payload['organization_unit_id'] ?? null);
            $lines = array_values($payload['lines'] ?? []);
            if ($lines === []) {
                throw ValidationException::withMessages(['lines' => ['At least one journal line is required.']]);
            }

            $totalDebit = 0.0;
            $totalCredit = 0.0;
            foreach ($lines as $index => $line) {
                $debit = (float) ($line['debit_amount'] ?? 0);
                $credit = (float) ($line['credit_amount'] ?? 0);
                if ($debit < 0 || $credit < 0 || ($debit > 0 && $credit > 0) || ($debit == 0.0 && $credit == 0.0)) {
                    throw ValidationException::withMessages(["lines.$index" => ['Each journal line must contain either a debit or a credit amount.']]);
                }
                $this->support->assertTenantRow('accounts', (int) $line['account_id'], "lines.$index.account_id");
                $totalDebit += $debit;
                $totalCredit += $credit;
            }

            if (round($totalDebit, 4) !== round($totalCredit, 4)) {
                throw ValidationException::withMessages(['lines' => ['Journal entry is not balanced.']]);
            }

            $journalEntryId = DB::table('journal_entries')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'entry_number' => $payload['entry_number'] ?? $this->support->nextNumber('JE', 'journal_entries', 'entry_number'),
                'entry_type' => $payload['entry_type'] ?? 'AUTO',
                'reference_type' => $payload['reference_type'] ?? null,
                'reference_id' => $payload['reference_id'] ?? null,
                'source_module' => $payload['source_module'] ?? null,
                'source_type' => $payload['source_type'] ?? null,
                'source_id' => $payload['source_id'] ?? null,
                'source_reference' => $payload['source_reference'] ?? null,
                'description' => $payload['description'] ?? null,
                'entry_date' => $payload['entry_date'] ?? now()->toDateString(),
                'posting_date' => $payload['posting_date'] ?? now()->toDateString(),
                'status' => 'POSTED',
                'currency_id' => $payload['currency_id'] ?? null,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'created_by' => $this->support->userId(),
                'posted_by' => $this->support->userId(),
                'posted_at' => now(),
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($lines as $index => $line) {
                $debit = (float) ($line['debit_amount'] ?? 0);
                $credit = (float) ($line['credit_amount'] ?? 0);
                DB::table('journal_entry_lines')->insert([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'journal_entry_id' => $journalEntryId,
                    'account_id' => (int) $line['account_id'],
                    'description' => $line['description'] ?? null,
                    'debit_amount' => $debit,
                    'credit_amount' => $credit,
                    'currency_id' => $payload['currency_id'] ?? null,
                    'exchange_rate' => $payload['exchange_rate'] ?? 1,
                    'base_debit_amount' => $debit,
                    'base_credit_amount' => $credit,
                    'party_type' => $line['party_type'] ?? null,
                    'party_id' => $line['party_id'] ?? null,
                    'line_number' => $index + 1,
                    'source_line_reference' => $line['source_line_reference'] ?? null,
                    'row_version' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return ['journal_entry_id' => $journalEntryId, 'total_debit' => $totalDebit, 'total_credit' => $totalCredit];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function reverseJournalEntry(int $journalEntryId, ?string $reason = null): array
    {
        return DB::transaction(function () use ($journalEntryId, $reason): array {
            $tenantId = $this->support->tenantId();
            $entry = DB::table('journal_entries')->where('tenant_id', $tenantId)->where('id', $journalEntryId)->lockForUpdate()->first();
            if ($entry === null) {
                throw ValidationException::withMessages(['journal_entry_id' => ['Journal entry was not found.']]);
            }
            if ((bool) $entry->is_reversed) {
                throw ValidationException::withMessages(['journal_entry_id' => ['Journal entry is already reversed.']]);
            }

            $lines = DB::table('journal_entry_lines')->where('tenant_id', $tenantId)->where('journal_entry_id', $journalEntryId)->orderBy('line_number')->get();
            $reversal = $this->createJournalEntry([
                'organization_unit_id' => $entry->organization_unit_id,
                'entry_type' => 'ADJUSTMENT',
                'reference_type' => $entry->reference_type,
                'reference_id' => $entry->reference_id,
                'source_module' => $entry->source_module,
                'source_type' => 'journal_reversal',
                'source_id' => $journalEntryId,
                'source_reference' => $entry->entry_number,
                'description' => $reason ?? 'Journal reversal',
                'entry_date' => now()->toDateString(),
                'currency_id' => $entry->currency_id,
                'lines' => $lines->map(fn (object $line): array => [
                    'account_id' => (int) $line->account_id,
                    'debit_amount' => (float) $line->credit_amount,
                    'credit_amount' => (float) $line->debit_amount,
                    'party_type' => $line->party_type,
                    'party_id' => $line->party_id,
                    'description' => 'Reversal: '.$line->description,
                ])->all(),
            ]);

            DB::table('journal_entries')->where('id', $journalEntryId)->update([
                'is_reversed' => true,
                'reversal_entry_id' => $reversal['journal_entry_id'],
                'status' => 'REVERSED',
                'reversed_at' => now(),
                'row_version' => ((int) $entry->row_version) + 1,
                'updated_at' => now(),
            ]);

            return $reversal;
        });
    }
}
