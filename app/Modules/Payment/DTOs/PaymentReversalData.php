<?php

declare(strict_types=1);

namespace Modules\Payment\DTOs;

final readonly class PaymentReversalData
{
    public function __construct(
        public int $paymentId,
        public int $expectedVersion,
        public string $reversalDate,
        public string $reason,
        public ?int $reversedBy = null,
    ) {}
}
