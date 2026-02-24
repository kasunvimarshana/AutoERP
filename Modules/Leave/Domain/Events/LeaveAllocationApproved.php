<?php

namespace Modules\Leave\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class LeaveAllocationApproved extends DomainEvent
{
    public function __construct(
        public readonly string $allocationId,
        public readonly string $tenantId,
        public readonly string $approvedBy,
    ) {
        parent::__construct();
    }
}
