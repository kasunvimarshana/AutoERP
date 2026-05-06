<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class BankReconciliationData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $bankAccountId,
        public readonly string $periodStart,
        public readonly string $periodEnd,
        public readonly float $openingBalance,
        public readonly float $closingBalance,
        public readonly string $status = 'draft',
        public readonly ?int $completedBy = null,
        public readonly int $rowVersion = 1,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            bankAccountId: (int) $data['bank_account_id'],
            periodStart: (string) $data['period_start'],
            periodEnd: (string) $data['period_end'],
            openingBalance: (float) $data['opening_balance'],
            closingBalance: (float) $data['closing_balance'],
            status: (string) ($data['status'] ?? 'draft'),
            completedBy: isset($data['completed_by']) ? (int) $data['completed_by'] : null,
            rowVersion: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
