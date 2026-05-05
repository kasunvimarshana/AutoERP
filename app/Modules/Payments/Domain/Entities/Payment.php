<?php

declare(strict_types=1);

namespace Modules\Payments\Domain\Entities;

use DateTimeImmutable;
use Modules\Payments\Domain\ValueObjects\PaymentMethod;
use Modules\Payments\Domain\ValueObjects\PaymentStatus;

class Payment
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $orgUnitId,
        public readonly int $rowVersion,
        public readonly string $paymentNumber,
        public readonly string $invoiceId,
        public readonly PaymentMethod $paymentMethod,
        public readonly PaymentStatus $status,
        public readonly string $amount,
        public readonly string $currency,
        public readonly ?DateTimeImmutable $paidAt,
        public readonly ?string $referenceNumber,
        public readonly ?string $notes,
        public readonly ?array $metadata,
        public readonly bool $isActive,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
    ) {
    }
}
