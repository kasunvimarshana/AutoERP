<?php

declare(strict_types=1);

namespace Modules\Payment\DTOs;

final readonly class PaymentReversalData
{
    public function __construct(
        public int $paymentId,
        public string $reversalNumber,
        public string $reversalDate,
        public string $reason,
        public ?int $reversedBy = null,
        public string $status = 'posted',
        public ?array $metadata = null,
    ) {}
}
