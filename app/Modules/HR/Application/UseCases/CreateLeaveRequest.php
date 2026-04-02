<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\HR\Application\DTOs\LeaveRequestData;
use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Domain\Events\LeaveRequestCreated;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;

class CreateLeaveRequest
{
    public function __construct(private readonly LeaveRequestRepositoryInterface $repo) {}

    public function execute(LeaveRequestData $data): LeaveRequest
    {
        $leaveRequest = new LeaveRequest(
            tenantId:   $data->tenant_id,
            employeeId: $data->employee_id,
            leaveType:  $data->leave_type,
            startDate:  new \DateTimeImmutable($data->start_date),
            endDate:    new \DateTimeImmutable($data->end_date),
            reason:     $data->reason,
            status:     $data->status,
            metadata:   $data->metadata !== null ? new Metadata($data->metadata) : null,
        );

        $saved = $this->repo->save($leaveRequest);
        LeaveRequestCreated::dispatch($saved);

        return $saved;
    }
}
