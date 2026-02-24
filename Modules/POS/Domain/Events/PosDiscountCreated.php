<?php

namespace Modules\POS\Domain\Events;

use DateTimeImmutable;

class PosDiscountCreated
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly string $discountId,
        public readonly string $tenantId,
        public readonly string $code,
        public readonly string $name,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
