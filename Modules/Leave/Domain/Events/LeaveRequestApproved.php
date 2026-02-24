<?php

namespace Modules\Leave\Domain\Events;

class LeaveRequestApproved
{
    public function __construct(
        public readonly string $leaveRequestId,
        public readonly string $tenantId,
        public readonly string $approverId,
        public readonly string $employeeId    = '',
        public readonly string $startDate     = '',
        public readonly string $endDate       = '',
        public readonly string $leaveTypeName = '',
    ) {}
}
