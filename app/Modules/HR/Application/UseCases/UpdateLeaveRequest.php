<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\HR\Application\DTOs\UpdateLeaveRequestData;
use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Domain\Events\LeaveRequestUpdated;
use Modules\HR\Domain\Exceptions\LeaveRequestNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;

class UpdateLeaveRequest
{
    public function __construct(private readonly LeaveRequestRepositoryInterface $repo) {}

    public function execute(UpdateLeaveRequestData $data): LeaveRequest
    {
        $id           = (int) ($data->id ?? 0);
        $leaveRequest = $this->repo->find($id);
        if (! $leaveRequest) {
            throw new LeaveRequestNotFoundException($id);
        }

        $leaveType = $data->isProvided('leave_type') ? (string) $data->leave_type : $leaveRequest->getLeaveType();
        $startDate = $data->isProvided('start_date') ? new \DateTimeImmutable((string) $data->start_date) : $leaveRequest->getStartDate();
        $endDate   = $data->isProvided('end_date') ? new \DateTimeImmutable((string) $data->end_date) : $leaveRequest->getEndDate();
        $reason    = $data->isProvided('reason') ? $data->reason : $leaveRequest->getReason();
        $status    = $data->isProvided('status') ? (string) $data->status : $leaveRequest->getStatus();
        $metadata  = $data->isProvided('metadata') ? ($data->metadata !== null ? new Metadata($data->metadata) : null) : $leaveRequest->getMetadata();

        $updated = new LeaveRequest(
            tenantId:   $leaveRequest->getTenantId(),
            employeeId: $leaveRequest->getEmployeeId(),
            leaveType:  $leaveType,
            startDate:  $startDate,
            endDate:    $endDate,
            reason:     $reason,
            status:     $status,
            approvedBy: $leaveRequest->getApprovedBy(),
            approvedAt: $leaveRequest->getApprovedAt(),
            notes:      $leaveRequest->getNotes(),
            metadata:   $metadata,
            id:         $leaveRequest->getId(),
            createdAt:  $leaveRequest->getCreatedAt(),
        );

        $saved = $this->repo->save($updated);
        LeaveRequestUpdated::dispatch($saved);

        return $saved;
    }
}
