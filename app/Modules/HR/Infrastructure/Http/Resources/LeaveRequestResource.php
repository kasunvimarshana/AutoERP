<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\LeaveRequest;

class LeaveRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var LeaveRequest $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'employee_id' => $entity->getEmployeeId(),
            'leave_type_id' => $entity->getLeaveTypeId(),
            'start_date' => $entity->getStartDate()->format('Y-m-d'),
            'end_date' => $entity->getEndDate()->format('Y-m-d'),
            'total_days' => $entity->getTotalDays(),
            'reason' => $entity->getReason(),
            'status' => $entity->getStatus()->value,
            'approver_id' => $entity->getApproverId(),
            'approver_note' => $entity->getApproverNote(),
            'attachment_path' => $entity->getAttachmentPath(),
            'metadata' => $entity->getMetadata(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
