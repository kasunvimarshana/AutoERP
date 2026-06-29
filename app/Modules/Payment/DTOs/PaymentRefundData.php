<?php

declare(strict_types=1);

namespace Modules\Payment\DTOs;

final readonly class PaymentRefundData
{
    public function __construct(
        public int $paymentId,
        public int $expectedVersion,
        public string $refundDate,
        public string $amount,
        public ?int $paymentMethodId = null,
        public ?string $reason = null,
        public ?int $refundedBy = null,
    ) {}
}
