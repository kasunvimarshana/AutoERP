<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\HR\Domain\Entities\AttendanceLog;

class AttendanceLogCreated extends BaseEvent
{
    public function __construct(
        public readonly AttendanceLog $attendanceLog,
        int $tenantId,
        ?int $orgUnitId = null,
    ) {
        parent::__construct($tenantId, $orgUnitId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'attendanceLogId' => $this->attendanceLog->getId(),
            'employeeId' => $this->attendanceLog->getEmployeeId(),
            'punchTime' => $this->attendanceLog->getPunchTime()->format(\DateTimeInterface::ATOM),
            'punchType' => $this->attendanceLog->getPunchType(),
        ]);
    }
}
