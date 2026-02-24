<?php

namespace Modules\POS\Domain\Events;

class SplitPaymentProcessed
{
    public \DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly string $orderId,
        public readonly string $tenantId,
        public readonly int $paymentCount,
        public readonly string $totalAmount,
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }
}
