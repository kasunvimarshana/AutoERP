<?php

declare(strict_types=1);

namespace Modules\Financial\Domain\Entities;

/**
 * Bank / credit-card account domain entity.
 */
class BankAccount
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly ?string $accountId,
        public readonly string $name,
        public readonly ?string $accountNumber,
        public readonly ?string $bankName,
        public readonly string $accountType,
        public readonly string $currencyCode,
        public readonly float $currentBalance,
        public readonly string $status,
    ) {}
}
