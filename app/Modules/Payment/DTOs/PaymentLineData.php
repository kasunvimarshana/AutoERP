<?php

declare(strict_types=1);

namespace Modules\Payment\DTOs;

final readonly class PaymentLineData
{
    public function __construct(
        public string $amount,
        public ?int $paymentMethodId = null,
        public ?string $referenceNumber = null,
        public string $clearedAmount = '0.000000',
        public string $status = 'pending',
        public ?string $notes = null,
    ) {}
}
