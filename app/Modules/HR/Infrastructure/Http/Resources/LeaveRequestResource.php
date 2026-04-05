<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\LeaveRequest;

class LeaveRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var LeaveRequest $req */
        $req = $this->resource;
        return [
            'id'               => $req->getId(),
            'tenant_id'        => $req->getTenantId(),
            'employee_id'      => $req->getEmployeeId(),
            'leave_type_id'    => $req->getLeaveTypeId(),
            'start_date'       => $req->getStartDate()->format('Y-m-d'),
            'end_date'         => $req->getEndDate()->format('Y-m-d'),
            'total_days'       => $req->getTotalDays(),
            'status'           => $req->getStatus(),
            'reason'           => $req->getReason(),
            'approved_by_id'   => $req->getApprovedById(),
            'approved_at'      => $req->getApprovedAt()?->format('Y-m-d H:i:s'),
            'rejection_reason' => $req->getRejectionReason(),
            'created_at'       => $req->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at'       => $req->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
