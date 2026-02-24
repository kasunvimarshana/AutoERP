<?php

namespace Modules\FieldService\Domain\Events;

class ServiceOrderAssigned
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $tenantId,
        public readonly string $technicianId,
    ) {}
}
