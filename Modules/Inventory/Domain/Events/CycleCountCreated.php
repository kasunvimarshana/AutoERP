<?php

namespace Modules\Inventory\Domain\Events;

use DateTimeImmutable;

class CycleCountCreated
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly string  $cycleCountId,
        public readonly string  $tenantId,
        public readonly string  $warehouseId,
        public readonly string  $reference,
        public readonly string  $countDate,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
