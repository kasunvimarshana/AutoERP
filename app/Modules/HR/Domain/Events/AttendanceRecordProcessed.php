<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\HR\Domain\Entities\AttendanceRecord;

class AttendanceRecordProcessed extends BaseEvent
{
    public function __construct(
        public readonly AttendanceRecord $attendanceRecord,
        int $tenantId,
        ?int $orgUnitId = null,
    ) {
        parent::__construct($tenantId, $orgUnitId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'attendanceRecordId' => $this->attendanceRecord->getId(),
            'employeeId' => $this->attendanceRecord->getEmployeeId(),
            'attendanceDate' => $this->attendanceRecord->getAttendanceDate()->format('Y-m-d'),
            'status' => $this->attendanceRecord->getStatus()->value,
        ]);
    }
}
