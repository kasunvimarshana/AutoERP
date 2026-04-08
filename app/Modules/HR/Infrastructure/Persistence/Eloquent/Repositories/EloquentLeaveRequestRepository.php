<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveRequestModel;

class EloquentLeaveRequestRepository extends EloquentRepository implements LeaveRequestRepositoryInterface
{
    public function __construct(LeaveRequestModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (LeaveRequestModel $model): LeaveRequest => $this->mapModelToDomainEntity($model));
    }

    public function save(LeaveRequest $leaveRequest): LeaveRequest
    {
        $savedModel = null;

        DB::transaction(function () use ($leaveRequest, &$savedModel) {
            $data = [
                'tenant_id'   => $leaveRequest->getTenantId(),
                'employee_id' => $leaveRequest->getEmployeeId(),
                'leave_type'  => $leaveRequest->getLeaveType(),
                'start_date'  => $leaveRequest->getStartDate()->format('Y-m-d'),
                'end_date'    => $leaveRequest->getEndDate()->format('Y-m-d'),
                'reason'      => $leaveRequest->getReason(),
                'status'      => $leaveRequest->getStatus(),
                'approved_by' => $leaveRequest->getApprovedBy(),
                'approved_at' => $leaveRequest->getApprovedAt()?->format('Y-m-d H:i:s'),
                'notes'       => $leaveRequest->getNotes(),
                'metadata'    => $leaveRequest->getMetadata()->toArray(),
            ];

            if ($leaveRequest->getId()) {
                $savedModel = $this->update($leaveRequest->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof LeaveRequestModel) {
            throw new \RuntimeException('Failed to save leave request.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function getByEmployee(int $employeeId): array
    {
        return $this->model->where('employee_id', $employeeId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m))
            ->all();
    }

    public function getPendingByEmployee(int $employeeId): array
    {
        return $this->model->where('employee_id', $employeeId)
            ->where('status', 'pending')
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m))
            ->all();
    }

    private function mapModelToDomainEntity(LeaveRequestModel $model): LeaveRequest
    {
        return new LeaveRequest(
            tenantId:   $model->tenant_id,
            employeeId: $model->employee_id,
            leaveType:  $model->leave_type,
            startDate:  $model->start_date instanceof \DateTimeInterface ? $model->start_date : new \DateTimeImmutable($model->start_date),
            endDate:    $model->end_date instanceof \DateTimeInterface ? $model->end_date : new \DateTimeImmutable($model->end_date),
            reason:     $model->reason,
            status:     $model->status,
            approvedBy: $model->approved_by,
            approvedAt: $model->approved_at ? ($model->approved_at instanceof \DateTimeInterface ? $model->approved_at : new \DateTimeImmutable($model->approved_at)) : null,
            notes:      $model->notes,
            metadata:   isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            id:         $model->id,
            createdAt:  $model->created_at,
            updatedAt:  $model->updated_at,
        );
    }
}
