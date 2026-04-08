<?php

declare(strict_types=1);

namespace Modules\Transaction\Domain\Entities;

use DateTimeInterface;

class TransactionLine
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $transactionId,
        public readonly ?string $accountId,
        public readonly ?string $productId,
        public readonly float $quantity,
        public readonly float $unitPrice,
        public readonly float $amount,
        public readonly float $debit,
        public readonly float $credit,
        public readonly ?string $notes,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}
}
