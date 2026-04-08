<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\HR\Application\Contracts\CreateLeaveRequestServiceInterface;
use Modules\HR\Application\DTOs\LeaveRequestData;
use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Domain\Events\LeaveRequestCreated;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;

class CreateLeaveRequestService extends BaseService implements CreateLeaveRequestServiceInterface
{
    public function __construct(private readonly LeaveRequestRepositoryInterface $leaveRequestRepository)
    {
        parent::__construct($leaveRequestRepository);
    }

    protected function handle(array $data): LeaveRequest
    {
        $dto = LeaveRequestData::fromArray($data);

        $leaveRequest = new LeaveRequest(
            tenantId:   $dto->tenant_id,
            employeeId: $dto->employee_id,
            leaveType:  $dto->leave_type,
            startDate:  new \DateTimeImmutable($dto->start_date),
            endDate:    new \DateTimeImmutable($dto->end_date),
            reason:     $dto->reason,
            status:     $dto->status,
            metadata:   $dto->metadata !== null ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->leaveRequestRepository->save($leaveRequest);
        $this->addEvent(new LeaveRequestCreated($saved));

        return $saved;
    }
}
