<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceBankReconciliation;
use Modules\Finance\Models\FinanceBankStatementLine;
use Modules\Finance\Models\FinanceLedgerEntry;

final class BankReconciliationService
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @param  list<array<string, mixed>>  $statementLines
     */
    public function create(
        int $tenantId,
        ?int $organizationUnitId,
        int $bankAccountId,
        string $statementReference,
        string $statementDate,
        string $openingBalance = '0.000000',
        string $closingBalance = '0.000000',
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $notes = null,
        array $statementLines = [],
    ): FinanceBankReconciliation {
        return DB::transaction(function () use (
            $tenantId,
            $organizationUnitId,
            $bankAccountId,
            $statementReference,
            $statementDate,
            $openingBalance,
            $closingBalance,
            $startDate,
            $endDate,
            $notes,
            $statementLines,
        ): FinanceBankReconciliation {
            $this->assertBankAccount($tenantId, $organizationUnitId, $bankAccountId);

            $reconciliation = FinanceBankReconciliation::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'bank_account_id' => $bankAccountId,
                'statement_reference' => trim($statementReference),
                'statement_date' => $statementDate,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'opening_balance' => $this->math->normalize($openingBalance),
                'closing_balance' => $this->math->normalize($closingBalance),
                'reconciled_balance' => $this->math->normalize($openingBalance),
                'status' => 'draft',
                'notes' => $notes,
            ]);

            foreach ($statementLines as $line) {
                $this->addStatementLine($reconciliation, $line);
            }

            return $reconciliation->refresh()->load(['bankAccount', 'statementLines.matchedLedgerEntry']);
        });
    }

    /**
     * @param  array<string, mixed>  $line
     */
    public function addStatementLine(FinanceBankReconciliation $reconciliation, array $line): FinanceBankStatementLine
    {
        $this->assertEditable($reconciliation);

        $debit = $this->math->normalize((string) ($line['debit'] ?? '0.000000'));
        $credit = $this->math->normalize((string) ($line['credit'] ?? '0.000000'));
        if ($this->math->isNegative($debit) || $this->math->isNegative($credit)) {
            throw new InvalidArgumentException('Bank statement line debit and credit cannot be negative.');
        }
        if ($this->math->isZero($debit) && $this->math->isZero($credit)) {
            throw new InvalidArgumentException('Bank statement line requires a debit or credit amount.');
        }
        if (! $this->math->isZero($debit) && ! $this->math->isZero($credit)) {
            throw new InvalidArgumentException('Bank statement line cannot have both debit and credit.');
        }

        return FinanceBankStatementLine::query()->create([
            'reconciliation_id' => $reconciliation->getKey(),
            'tenant_id' => $reconciliation->tenant_id,
            'organization_unit_id' => $reconciliation->organization_unit_id,
            'bank_account_id' => $reconciliation->bank_account_id,
            'statement_date' => (string) ($line['statement_date'] ?? $reconciliation->statement_date->toDateString()),
            'reference' => $line['reference'] ?? null,
            'description' => $line['description'] ?? null,
            'debit' => $debit,
            'credit' => $credit,
            'status' => 'unmatched',
        ]);
    }

    public function matchLine(FinanceBankStatementLine $statementLine, int $ledgerEntryId): FinanceBankStatementLine
    {
        return DB::transaction(function () use ($statementLine, $ledgerEntryId): FinanceBankStatementLine {
            $statementLine = FinanceBankStatementLine::query()
                ->with('reconciliation')
                ->lockForUpdate()
                ->findOrFail($statementLine->getKey());
            $this->assertEditable($statementLine->reconciliation);

            $ledger = FinanceLedgerEntry::query()->lockForUpdate()->findOrFail($ledgerEntryId);
            if ((int) $ledger->tenant_id !== (int) $statementLine->tenant_id
                || $ledger->organization_unit_id !== $statementLine->organization_unit_id
                || (int) $ledger->account_id !== (int) $statementLine->bank_account_id) {
                throw new InvalidArgumentException('Ledger entry does not belong to this bank reconciliation scope.');
            }
            if (FinanceBankStatementLine::query()
                ->where('matched_ledger_entry_id', $ledgerEntryId)
                ->whereKeyNot($statementLine->getKey())
                ->exists()) {
                throw new InvalidArgumentException('Ledger entry is already reconciled.');
            }
            if ($this->math->compare((string) $statementLine->debit, (string) $ledger->debit) !== 0
                || $this->math->compare((string) $statementLine->credit, (string) $ledger->credit) !== 0) {
                throw new InvalidArgumentException('Bank statement line amount does not match ledger entry amount.');
            }

            $statementLine->forceFill([
                'matched_ledger_entry_id' => $ledgerEntryId,
                'status' => 'matched',
                'matched_at' => now(),
            ])->save();

            $this->syncReconciledBalance($statementLine->reconciliation);

            return $statementLine->refresh()->load('matchedLedgerEntry');
        });
    }

    public function unmatchLine(FinanceBankStatementLine $statementLine): FinanceBankStatementLine
    {
        return DB::transaction(function () use ($statementLine): FinanceBankStatementLine {
            $statementLine = FinanceBankStatementLine::query()
                ->with('reconciliation')
                ->lockForUpdate()
                ->findOrFail($statementLine->getKey());
            $this->assertEditable($statementLine->reconciliation);
            $statementLine->forceFill([
                'matched_ledger_entry_id' => null,
                'status' => 'unmatched',
                'matched_at' => null,
            ])->save();
            $this->syncReconciledBalance($statementLine->reconciliation);

            return $statementLine->refresh();
        });
    }

    public function complete(FinanceBankReconciliation $reconciliation, ?int $completedBy = null): FinanceBankReconciliation
    {
        return DB::transaction(function () use ($reconciliation, $completedBy): FinanceBankReconciliation {
            $reconciliation = FinanceBankReconciliation::query()
                ->with('statementLines')
                ->lockForUpdate()
                ->findOrFail($reconciliation->getKey());
            $this->assertEditable($reconciliation);
            $this->syncReconciledBalance($reconciliation);

            $status = $reconciliation->statementLines->contains(
                fn (FinanceBankStatementLine $line): bool => $line->matched_ledger_entry_id === null,
            ) ? 'completed_with_unmatched' : 'completed';

            $reconciliation->forceFill([
                'status' => $status,
                'completed_by' => $completedBy,
                'completed_at' => now(),
            ])->save();

            return $reconciliation->refresh()->load(['bankAccount', 'statementLines.matchedLedgerEntry']);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function report(FinanceBankReconciliation $reconciliation): array
    {
        $reconciliation->loadMissing(['bankAccount', 'statementLines.matchedLedgerEntry']);
        $lines = $reconciliation->statementLines;
        $matched = $lines->whereNotNull('matched_ledger_entry_id');
        $unmatched = $lines->whereNull('matched_ledger_entry_id');

        return [
            'id' => (int) $reconciliation->getKey(),
            'statement_reference' => (string) $reconciliation->statement_reference,
            'bank_account' => $reconciliation->bankAccount === null ? null : [
                'id' => (int) $reconciliation->bankAccount->getKey(),
                'code' => (string) $reconciliation->bankAccount->code,
                'name' => (string) $reconciliation->bankAccount->name,
            ],
            'statement_date' => $reconciliation->statement_date?->toDateString(),
            'status' => (string) $reconciliation->status,
            'opening_balance' => (string) $reconciliation->opening_balance,
            'closing_balance' => (string) $reconciliation->closing_balance,
            'reconciled_balance' => (string) $reconciliation->reconciled_balance,
            'matched_count' => $matched->count(),
            'unmatched_count' => $unmatched->count(),
            'lines' => $lines->values()->map(fn (FinanceBankStatementLine $line): array => [
                'id' => (int) $line->getKey(),
                'statement_date' => $line->statement_date?->toDateString(),
                'reference' => $line->reference,
                'description' => $line->description,
                'debit' => (string) $line->debit,
                'credit' => (string) $line->credit,
                'status' => (string) $line->status,
                'matched_ledger_entry_id' => $line->matched_ledger_entry_id,
            ])->all(),
        ];
    }

    private function assertBankAccount(int $tenantId, ?int $organizationUnitId, int $bankAccountId): FinanceAccount
    {
        $account = FinanceAccount::query()->findOrFail($bankAccountId);
        if ((int) $account->tenant_id !== $tenantId || $account->organization_unit_id !== $organizationUnitId) {
            throw new InvalidArgumentException('Bank account scope mismatch.');
        }
        if (! (bool) $account->is_active || ! (bool) $account->is_posting_account || ! (bool) $account->is_bank_account) {
            throw new InvalidArgumentException('Bank reconciliation account must be active, postable, and marked as bank.');
        }

        return $account;
    }

    private function assertEditable(FinanceBankReconciliation $reconciliation): void
    {
        if (in_array((string) $reconciliation->status, ['completed', 'completed_with_unmatched'], true)) {
            throw new InvalidArgumentException('Completed bank reconciliations cannot be changed.');
        }
    }

    private function syncReconciledBalance(FinanceBankReconciliation $reconciliation): void
    {
        $matched = FinanceBankStatementLine::query()
            ->where('reconciliation_id', $reconciliation->getKey())
            ->whereNotNull('matched_ledger_entry_id')
            ->get(['debit', 'credit']);
        $balance = $this->math->normalize((string) $reconciliation->opening_balance);

        foreach ($matched as $line) {
            $balance = $this->math->add($balance, (string) $line->debit);
            $balance = $this->math->sub($balance, (string) $line->credit);
        }

        $reconciliation->forceFill(['reconciled_balance' => $balance])->save();
    }
}
