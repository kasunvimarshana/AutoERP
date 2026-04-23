<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\HR\Domain\Entities\LeaveRequest;

class LeaveRequestSubmitted extends BaseEvent
{
    public function __construct(
        public readonly LeaveRequest $leaveRequest,
        int $tenantId,
        ?int $orgUnitId = null,
    ) {
        parent::__construct($tenantId, $orgUnitId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'leaveRequestId' => $this->leaveRequest->getId(),
            'employeeId' => $this->leaveRequest->getEmployeeId(),
            'leaveTypeId' => $this->leaveRequest->getLeaveTypeId(),
            'totalDays' => $this->leaveRequest->getTotalDays(),
            'status' => $this->leaveRequest->getStatus()->value,
        ]);
    }
}
