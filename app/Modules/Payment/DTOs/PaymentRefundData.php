<?php

declare(strict_types=1);

namespace Modules\Payment\DTOs;

final readonly class PaymentRefundData
{
    public function __construct(
        public int $paymentId,
        public string $refundNumber,
        public string $refundDate,
        public string $amount,
        public ?string $partyType = null,
        public ?int $partyId = null,
        public ?int $paymentMethodId = null,
        public ?string $reason = null,
        public string $status = 'posted',
    ) {}
}
