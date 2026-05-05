<?php

declare(strict_types=1);

namespace Modules\Receipts\Application\DTOs;

use Modules\Receipts\Domain\ValueObjects\ReceiptType;

class CreateReceiptDTO
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $orgUnitId,
        public readonly string $receiptNumber,
        public readonly string $paymentId,
        public readonly ?string $invoiceId,
        public readonly ReceiptType $receiptType,
        public readonly string $amount,
        public readonly string $currency,
        public readonly ?string $notes,
        public readonly ?array $metadata,
    ) {
    }
}
