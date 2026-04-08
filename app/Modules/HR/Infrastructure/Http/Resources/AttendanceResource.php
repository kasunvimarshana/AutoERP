<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->getId(),
            'tenant_id'      => $this->getTenantId(),
            'employee_id'    => $this->getEmployeeId(),
            'date'           => $this->getDate(),
            'check_in_time'  => $this->getCheckInTime()->format('H:i:s'),
            'check_out_time' => $this->getCheckOutTime()?->format('H:i:s'),
            'status'         => $this->getStatus(),
            'notes'          => $this->getNotes(),
            'hours_worked'   => $this->getHoursWorked(),
            'created_at'     => $this->getCreatedAt()?->format('c'),
            'updated_at'     => $this->getUpdatedAt()?->format('c'),
        ];
    }
}
