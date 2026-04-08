<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\HR\Domain\Entities\LeaveRequest;

class LeaveRequestRejected extends BaseEvent
{
    public function __construct(public readonly LeaveRequest $leaveRequest)
    {
        parent::__construct($leaveRequest->getTenantId(), $leaveRequest->getId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'          => $this->leaveRequest->getId(),
            'employee_id' => $this->leaveRequest->getEmployeeId(),
            'leave_type'  => $this->leaveRequest->getLeaveType(),
            'approved_by' => $this->leaveRequest->getApprovedBy(),
        ]);
    }
}
