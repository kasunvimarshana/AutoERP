<?php

namespace Modules\Logistics\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class DeliveryCompleted extends DomainEvent
{
    public function __construct(
        public readonly string $deliveryOrderId,
        public readonly string $tenantId,
        public readonly array  $lines = [],
    ) {
        parent::__construct();
    }
}
