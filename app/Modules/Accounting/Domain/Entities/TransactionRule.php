<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Entities;

use DateTimeInterface;

class TransactionRule
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly int $priority,
        public readonly array $conditions,
        public readonly string $applyTo,
        public readonly string $accountId,
        public readonly ?string $description,
        public readonly bool $isActive,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function matches(BankTransaction $transaction): bool
    {
        if ($this->applyTo !== 'all' && $this->applyTo !== $transaction->type) {
            return false;
        }

        foreach ($this->conditions as $condition) {
            $field    = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? '';
            $value    = $condition['value'] ?? '';

            $fieldValue = match ($field) {
                'description' => $transaction->description,
                'amount'      => (string) $transaction->amount,
                'type'        => $transaction->type,
                'reference'   => $transaction->reference ?? '',
                default       => '',
            };

            $matched = match ($operator) {
                'contains'     => str_contains(strtolower($fieldValue), strtolower($value)),
                'equals'       => strtolower($fieldValue) === strtolower($value),
                'starts_with'  => str_starts_with(strtolower($fieldValue), strtolower($value)),
                'ends_with'    => str_ends_with(strtolower($fieldValue), strtolower($value)),
                'greater_than' => (float) $fieldValue > (float) $value,
                'less_than'    => (float) $fieldValue < (float) $value,
                default        => false,
            };

            if (!$matched) {
                return false;
            }
        }

        return true;
    }
}
