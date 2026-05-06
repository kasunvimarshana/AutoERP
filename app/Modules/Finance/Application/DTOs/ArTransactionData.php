<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class ArTransactionData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $customerId,
        public readonly int $accountId,
        public readonly string $transactionType,
        public readonly float $amount,
        public readonly float $balanceAfter,
        public readonly string $transactionDate,
        public readonly int $currencyId,
        public readonly ?string $referenceType = null,
        public readonly ?int $referenceId = null,
        public readonly ?string $dueDate = null,
        public readonly bool $isReconciled = false,
        public readonly int $rowVersion = 1,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            customerId: (int) $data['customer_id'],
            accountId: (int) $data['account_id'],
            transactionType: (string) $data['transaction_type'],
            amount: (float) $data['amount'],
            balanceAfter: (float) $data['balance_after'],
            transactionDate: (string) $data['transaction_date'],
            currencyId: (int) $data['currency_id'],
            referenceType: isset($data['reference_type']) ? (string) $data['reference_type'] : null,
            referenceId: isset($data['reference_id']) ? (int) $data['reference_id'] : null,
            dueDate: isset($data['due_date']) ? (string) $data['due_date'] : null,
            isReconciled: (bool) ($data['is_reconciled'] ?? false),
            rowVersion: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
