<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->getId(),
            'tenant_id'   => $this->getTenantId(),
            'employee_id' => $this->getEmployeeId(),
            'leave_type'  => $this->getLeaveType(),
            'start_date'  => $this->getStartDate()->format('Y-m-d'),
            'end_date'    => $this->getEndDate()->format('Y-m-d'),
            'reason'      => $this->getReason(),
            'status'      => $this->getStatus(),
            'approved_by' => $this->getApprovedBy(),
            'approved_at' => $this->getApprovedAt()?->format('c'),
            'notes'       => $this->getNotes(),
            'metadata'    => $this->getMetadata()->toArray(),
            'created_at'  => $this->getCreatedAt()->format('c'),
            'updated_at'  => $this->getUpdatedAt()->format('c'),
        ];
    }
}
