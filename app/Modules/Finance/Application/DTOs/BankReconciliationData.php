<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class BankReconciliationData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $bank_account_id,
        public readonly string $period_start,
        public readonly string $period_end,
        public readonly float $opening_balance,
        public readonly float $closing_balance,
        public readonly string $status = 'draft',
        public readonly ?int $completed_by = null,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            bank_account_id: (int) $data['bank_account_id'],
            period_start: (string) $data['period_start'],
            period_end: (string) $data['period_end'],
            opening_balance: (float) $data['opening_balance'],
            closing_balance: (float) $data['closing_balance'],
            status: (string) ($data['status'] ?? 'draft'),
            completed_by: isset($data['completed_by']) ? (int) $data['completed_by'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
