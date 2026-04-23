<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;
use Modules\HR\Domain\ValueObjects\LeaveRequestStatus;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveRequestModel;

class EloquentLeaveRequestRepository extends EloquentRepository implements LeaveRequestRepositoryInterface
{
    public function __construct(LeaveRequestModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (LeaveRequestModel $m): LeaveRequest => $this->mapModelToDomainEntity($m));
    }

    public function save(LeaveRequest $entity): LeaveRequest
    {
        $data = ['tenant_id' => $entity->getTenantId(), 'employee_id' => $entity->getEmployeeId(), 'leave_type_id' => $entity->getLeaveTypeId(), 'start_date' => $entity->getStartDate()->format('Y-m-d'), 'end_date' => $entity->getEndDate()->format('Y-m-d'), 'total_days' => $entity->getTotalDays(), 'reason' => $entity->getReason(), 'status' => $entity->getStatus()->value, 'approver_id' => $entity->getApproverId(), 'approver_note' => $entity->getApproverNote(), 'attachment_path' => $entity->getAttachmentPath(), 'metadata' => $entity->getMetadata()];
        $model = $entity->getId() ? $this->update($entity->getId(), $data) : $this->create($data);

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?LeaveRequest
    {
        return parent::find($id, $columns);
    }

    public function findOverlapping(int $tenantId, int $employeeId, string $startDate, string $endDate, ?int $excludeId = null): array
    {
        $q = $this->model->where('tenant_id', $tenantId)->where('employee_id', $employeeId)->where('start_date', '<=', $endDate)->where('end_date', '>=', $startDate)->whereNotIn('status', ['cancelled', 'rejected', 'recalled']);
        if ($excludeId) {
            $q->where('id', '!=', $excludeId);
        }

        return $q->get()->map(fn ($m) => $this->toDomainEntity($m))->all();
    }

    private function mapModelToDomainEntity(LeaveRequestModel $m): LeaveRequest
    {
        return new LeaveRequest($m->tenant_id, $m->employee_id, $m->leave_type_id, $m->start_date instanceof \DateTimeInterface ? $m->start_date : new \DateTimeImmutable($m->start_date), $m->end_date instanceof \DateTimeInterface ? $m->end_date : new \DateTimeImmutable($m->end_date), (float) $m->total_days, $m->reason, LeaveRequestStatus::from($m->status), $m->approver_id, $m->approver_note ?? '', $m->attachment_path, $m->metadata ?? [], $m->created_at instanceof \DateTimeInterface ? $m->created_at : new \DateTimeImmutable($m->created_at ?? 'now'), $m->updated_at instanceof \DateTimeInterface ? $m->updated_at : new \DateTimeImmutable($m->updated_at ?? 'now'), $m->id);
    }
}
