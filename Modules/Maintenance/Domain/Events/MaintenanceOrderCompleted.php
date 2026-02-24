<?php

namespace Modules\Maintenance\Domain\Events;

use DateTimeImmutable;

class MaintenanceOrderCompleted
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly string $orderId,
        public readonly string $tenantId,
        public readonly string $equipmentId,
        public readonly string $laborCost,
        public readonly string $partsCost,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
