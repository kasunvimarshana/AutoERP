<?php

namespace Modules\POS\Domain\Events;

/**
 * Domain event emitted when a discount code is successfully applied to a POS order.
 */
class DiscountCodeApplied
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $tenantId,
        public readonly string $discountCodeId,
        public readonly string $discountCode,
        public readonly string $discountAmount,
        public readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}
