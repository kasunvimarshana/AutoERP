<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class ApTransactionData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $supplier_id,
        public readonly int $account_id,
        public readonly string $transaction_type,
        public readonly float $amount,
        public readonly float $balance_after,
        public readonly string $transaction_date,
        public readonly int $currency_id,
        public readonly ?string $reference_type = null,
        public readonly ?int $reference_id = null,
        public readonly ?string $due_date = null,
        public readonly bool $is_reconciled = false,
        public readonly int $row_version = 1,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            supplier_id: (int) $data['supplier_id'],
            account_id: (int) $data['account_id'],
            transaction_type: (string) $data['transaction_type'],
            amount: (float) $data['amount'],
            balance_after: (float) $data['balance_after'],
            transaction_date: (string) $data['transaction_date'],
            currency_id: (int) $data['currency_id'],
            reference_type: isset($data['reference_type']) ? (string) $data['reference_type'] : null,
            reference_id: isset($data['reference_id']) ? (int) $data['reference_id'] : null,
            due_date: isset($data['due_date']) ? (string) $data['due_date'] : null,
            is_reconciled: (bool) ($data['is_reconciled'] ?? false),
            row_version: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
