<?php

namespace Modules\Manufacturing\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class WorkOrderStarted extends DomainEvent
{
    public function __construct(
        public readonly string $workOrderId,
        public readonly string $tenantId,
    ) {
        parent::__construct();
    }
}
