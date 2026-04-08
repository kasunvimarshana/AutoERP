<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\HR\Application\Contracts\UpdateLeaveRequestServiceInterface;
use Modules\HR\Application\DTOs\UpdateLeaveRequestData;
use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Domain\Events\LeaveRequestUpdated;
use Modules\HR\Domain\Exceptions\LeaveRequestNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;

class UpdateLeaveRequestService extends BaseService implements UpdateLeaveRequestServiceInterface
{
    public function __construct(private readonly LeaveRequestRepositoryInterface $leaveRequestRepository)
    {
        parent::__construct($leaveRequestRepository);
    }

    protected function handle(array $data): LeaveRequest
    {
        $dto          = UpdateLeaveRequestData::fromArray($data);
        $id           = (int) ($dto->id ?? 0);
        $leaveRequest = $this->leaveRequestRepository->find($id);
        if (! $leaveRequest) {
            throw new LeaveRequestNotFoundException($id);
        }

        // For LeaveRequest we reconstruct since there's no updateDetails method;
        // instead we save with modified fields via a new entity preserving existing values.
        $leaveType = $dto->isProvided('leave_type')
            ? (string) $dto->leave_type
            : $leaveRequest->getLeaveType();

        $startDate = $dto->isProvided('start_date')
            ? new \DateTimeImmutable((string) $dto->start_date)
            : $leaveRequest->getStartDate();

        $endDate = $dto->isProvided('end_date')
            ? new \DateTimeImmutable((string) $dto->end_date)
            : $leaveRequest->getEndDate();

        $reason = $dto->isProvided('reason')
            ? $dto->reason
            : $leaveRequest->getReason();

        $status = $dto->isProvided('status')
            ? (string) $dto->status
            : $leaveRequest->getStatus();

        $metadata = $dto->isProvided('metadata')
            ? ($dto->metadata !== null ? new Metadata($dto->metadata) : null)
            : $leaveRequest->getMetadata();

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

        $saved = $this->leaveRequestRepository->save($updated);
        $this->addEvent(new LeaveRequestUpdated($saved));

        return $saved;
    }
}
