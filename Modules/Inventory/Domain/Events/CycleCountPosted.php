<?php

namespace Modules\Inventory\Domain\Events;

use DateTimeImmutable;

class CycleCountPosted
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly string $cycleCountId,
        public readonly string $tenantId,
        public readonly string $warehouseId,
        public readonly int    $linesAdjusted,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
