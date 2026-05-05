<?php

declare(strict_types=1);

namespace Modules\Receipts\Domain\Entities;

use DateTimeImmutable;
use Modules\Receipts\Domain\ValueObjects\ReceiptStatus;
use Modules\Receipts\Domain\ValueObjects\ReceiptType;

class Receipt
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $orgUnitId,
        public readonly int $rowVersion,
        public readonly string $receiptNumber,
        public readonly string $paymentId,
        public readonly ?string $invoiceId,
        public readonly ReceiptType $receiptType,
        public readonly ReceiptStatus $status,
        public readonly string $amount,
        public readonly string $currency,
        public readonly ?DateTimeImmutable $issuedAt,
        public readonly ?string $notes,
        public readonly ?array $metadata,
        public readonly bool $isActive,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
    ) {
    }
}
