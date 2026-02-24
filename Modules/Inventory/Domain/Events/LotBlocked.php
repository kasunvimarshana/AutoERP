<?php

namespace Modules\Inventory\Domain\Events;

use DateTimeImmutable;

class LotBlocked
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly string $lotId,
        public readonly string $tenantId,
        public readonly string $productId,
        public readonly string $lotNumber,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
