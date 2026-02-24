<?php

namespace Modules\Maintenance\Domain\Events;

use DateTimeImmutable;

class MaintenanceRequestCreated
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly string $requestId,
        public readonly string $tenantId,
        public readonly string $equipmentId,
        public readonly string $requestedBy,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
