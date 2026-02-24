<?php

namespace Modules\Maintenance\Domain\Events;

use DateTimeImmutable;

class EquipmentRegistered
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly string $equipmentId,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly string $serialNumber,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
