<?php

declare(strict_types=1);

namespace Modules\Payments\Application\DTOs;

use Modules\Payments\Domain\ValueObjects\PaymentMethod;

class CreatePaymentDTO
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $orgUnitId,
        public readonly string $paymentNumber,
        public readonly string $invoiceId,
        public readonly PaymentMethod $paymentMethod,
        public readonly string $amount,
        public readonly string $currency,
        public readonly ?string $referenceNumber,
        public readonly ?string $notes,
        public readonly ?array $metadata,
    ) {
    }
}
