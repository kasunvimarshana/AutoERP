<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\AttendanceRecord;

class AttendanceResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var AttendanceRecord $rec */
        $rec = $this->resource;
        return [
            'id'              => $rec->getId(),
            'tenant_id'       => $rec->getTenantId(),
            'employee_id'     => $rec->getEmployeeId(),
            'attendance_date' => $rec->getAttendanceDate()->format('Y-m-d'),
            'check_in'        => $rec->getCheckIn()?->format('Y-m-d H:i:s'),
            'check_out'       => $rec->getCheckOut()?->format('Y-m-d H:i:s'),
            'worked_hours'    => $rec->getWorkedHours(),
            'source'          => $rec->getSource(),
            'device_id'       => $rec->getDeviceId(),
            'notes'           => $rec->getNotes(),
            'is_approved'     => $rec->isApproved(),
            'created_at'      => $rec->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at'      => $rec->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
