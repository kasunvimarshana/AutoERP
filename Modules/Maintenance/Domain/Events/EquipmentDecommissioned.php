<?php

namespace Modules\Maintenance\Domain\Events;

use DateTimeImmutable;

class EquipmentDecommissioned
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly string $equipmentId,
        public readonly string $tenantId,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
