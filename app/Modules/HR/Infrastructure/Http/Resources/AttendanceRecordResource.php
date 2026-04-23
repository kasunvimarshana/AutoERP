<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\AttendanceRecord;

class AttendanceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var AttendanceRecord $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'employee_id' => $entity->getEmployeeId(),
            'attendance_date' => $entity->getAttendanceDate()->format('Y-m-d'),
            'check_in' => $entity->getCheckIn()?->format('c'),
            'check_out' => $entity->getCheckOut()?->format('c'),
            'break_duration' => $entity->getBreakDuration(),
            'worked_minutes' => $entity->getWorkedMinutes(),
            'overtime_minutes' => $entity->getOvertimeMinutes(),
            'status' => $entity->getStatus()->value,
            'shift_id' => $entity->getShiftId(),
            'remarks' => $entity->getRemarks(),
            'metadata' => $entity->getMetadata(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
