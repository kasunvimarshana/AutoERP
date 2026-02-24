<?php

namespace Modules\Inventory\Domain\Events;

use DateTimeImmutable;

class LotCreated
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly string $lotId,
        public readonly string $tenantId,
        public readonly string $productId,
        public readonly string $lotNumber,
        public readonly string $trackingType,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
