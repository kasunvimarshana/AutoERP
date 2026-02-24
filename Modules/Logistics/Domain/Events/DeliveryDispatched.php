<?php

namespace Modules\Logistics\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class DeliveryDispatched extends DomainEvent
{
    public function __construct(
        public readonly string $deliveryOrderId,
        public readonly string $tenantId,
    ) {
        parent::__construct();
    }
}
