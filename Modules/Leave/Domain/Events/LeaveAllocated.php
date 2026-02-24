<?php

namespace Modules\Leave\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class LeaveAllocated extends DomainEvent
{
    public function __construct(
        public readonly string $allocationId,
        public readonly string $tenantId,
        public readonly string $employeeId,
        public readonly string $leaveTypeId,
        public readonly string $totalDays,
    ) {
        parent::__construct();
    }
}
