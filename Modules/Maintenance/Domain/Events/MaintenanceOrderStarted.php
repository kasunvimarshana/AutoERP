<?php

namespace Modules\Maintenance\Domain\Events;

use DateTimeImmutable;

class MaintenanceOrderStarted
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly string $orderId,
        public readonly string $tenantId,
        public readonly string $equipmentId,
        public readonly string $orderType,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
