<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Domain\Exceptions\FinanceIntegrityException;

class FinanceIntegrityService
{
    public function normalizeResourceKey(string $resource): string
    {
        return str_replace('-', '_', strtolower(trim($resource)));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    public function prepareAttributes(string $resource, array $attributes, array $definition): array
    {
        $attributes['metadata'] = $this->normalizeMetadata($attributes['metadata'] ?? null);

        return match ($definition['recalculate'] ?? null) {
            'budget_line_total' => $this->withBudgetLineTotal($attributes),
            'journal_line_base_amounts' => $this->withJournalLineBaseAmounts($attributes),
            'bank_reconciliation_difference' => $this->withBankReconciliationDifference($attributes),
            default => $attributes,
        };
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function ensureMutable(string $resource, ?Model $record, array $definition, bool $isUpdate): void
    {
        $immutable = config("finance.immutable.{$resource}", []);

        if (($immutable['after_create'] ?? false) && $isUpdate) {
            throw FinanceIntegrityException::rule("{$resource} records are immutable after creation.");
        }

        $statusColumn = $immutable['status_column'] ?? null;

        if ($record !== null && $statusColumn !== null && in_array((string) $record->{$statusColumn}, $immutable['statuses'] ?? [], true)) {
            throw FinanceIntegrityException::rule("{$resource} record [{$record->getKey()}] is immutable in status [{$record->{$statusColumn}}].");
        }
    }

    /**
     * @param  Collection<int, Model>  $lines
     */
    public function assertBalancedJournalLines(Collection $lines): void
    {
        if ($lines->count() < 2) {
            throw FinanceIntegrityException::rule('A journal entry requires at least two lines before posting.');
        }

        $totalDebit = '0';
        $totalCredit = '0';

        foreach ($lines as $line) {
            $debit = (string) ($line->base_debit_amount ?? $line->debit_amount ?? 0);
            $credit = (string) ($line->base_credit_amount ?? $line->credit_amount ?? 0);

            if ($this->compare($debit, '0') > 0 && $this->compare($credit, '0') > 0) {
                throw FinanceIntegrityException::rule("Journal line [{$line->getKey()}] cannot contain both debit and credit amounts.");
            }

            if ($this->compare($debit, '0') === 0 && $this->compare($credit, '0') === 0) {
                throw FinanceIntegrityException::rule("Journal line [{$line->getKey()}] must contain a debit or credit amount.");
            }

            $totalDebit = $this->add($totalDebit, $debit);
            $totalCredit = $this->add($totalCredit, $credit);
        }

        if ($this->compare($totalDebit, $totalCredit) !== 0) {
            throw FinanceIntegrityException::rule("Journal entry is not balanced. Debit [{$totalDebit}], credit [{$totalCredit}].");
        }
    }

    public function calculateBankAccountBalance(string|int|float $openingBalance, Collection $transactions): string
    {
        $balance = $this->decimal($openingBalance);

        foreach ($transactions as $transaction) {
            $amount = $this->decimal($transaction->amount ?? 0);
            $type = strtoupper((string) ($transaction->type ?? 'DEBIT'));

            $balance = $type === 'CREDIT'
                ? $this->add($balance, $amount)
                : $this->sub($balance, $amount);
        }

        return $balance;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function withBudgetLineTotal(array $attributes): array
    {
        $total = '0';

        for ($period = 1; $period <= 12; $period++) {
            $column = "period_{$period}_amount";
            $attributes[$column] = $this->decimal($attributes[$column] ?? 0);
            $total = $this->add($total, (string) $attributes[$column]);
        }

        $attributes['total_amount'] = $total;

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function withJournalLineBaseAmounts(array $attributes): array
    {
        $debit = $this->decimal($attributes['debit_amount'] ?? 0);
        $credit = $this->decimal($attributes['credit_amount'] ?? 0);
        $exchangeRate = $this->decimal($attributes['exchange_rate'] ?? 1);

        if ($this->compare($debit, '0') > 0 && $this->compare($credit, '0') > 0) {
            throw FinanceIntegrityException::rule('A journal line cannot contain both debit and credit amounts.');
        }

        $attributes['debit_amount'] = $debit;
        $attributes['credit_amount'] = $credit;
        $attributes['exchange_rate'] = $exchangeRate;
        $attributes['base_debit_amount'] = $this->mul($debit, $exchangeRate);
        $attributes['base_credit_amount'] = $this->mul($credit, $exchangeRate);
        $attributes['tax_amount'] = $this->decimal($attributes['tax_amount'] ?? 0);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function withBankReconciliationDifference(array $attributes): array
    {
        $closingBalance = $this->decimal($attributes['closing_balance'] ?? 0);
        $statementBalance = $this->decimal($attributes['statement_balance'] ?? 0);
        $attributes['difference'] = $this->sub($closingBalance, $statementBalance);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>|null
     */
    private function normalizeMetadata(?array $metadata): ?array
    {
        return $metadata === [] ? null : $metadata;
    }

    private function decimal(string|int|float|null $value): string
    {
        return number_format((float) ($value ?? 0), (int) config('finance.precision.scale', 4), '.', '');
    }

    private function add(string $left, string $right): string
    {
        return $this->decimal((float) $left + (float) $right);
    }

    private function sub(string $left, string $right): string
    {
        return $this->decimal((float) $left - (float) $right);
    }

    private function mul(string $left, string $right): string
    {
        return $this->decimal((float) $left * (float) $right);
    }

    private function compare(string $left, string $right): int
    {
        return (float) $left <=> (float) $right;
    }
}
