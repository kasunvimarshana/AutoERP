<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\LeaveBalance;
use Modules\HR\Domain\RepositoryInterfaces\LeaveBalanceRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveBalanceModel;

class EloquentLeaveBalanceRepository extends EloquentRepository implements LeaveBalanceRepositoryInterface
{
    public function __construct(LeaveBalanceModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (LeaveBalanceModel $m): LeaveBalance => $this->mapModelToDomainEntity($m));
    }

    public function save(LeaveBalance $entity): LeaveBalance
    {
        $data = ['tenant_id' => $entity->getTenantId(), 'employee_id' => $entity->getEmployeeId(), 'leave_type_id' => $entity->getLeaveTypeId(), 'year' => $entity->getYear(), 'allocated' => $entity->getAllocated(), 'used' => $entity->getUsed(), 'pending' => $entity->getPending(), 'carried' => $entity->getCarried()];
        $model = $entity->getId() ? $this->update($entity->getId(), $data) : $this->create($data);

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?LeaveBalance
    {
        return parent::find($id, $columns);
    }

    public function findByEmployeeAndType(int $tenantId, int $employeeId, int $leaveTypeId, int $year): ?LeaveBalance
    {
        $m = $this->model->where('tenant_id', $tenantId)->where('employee_id', $employeeId)->where('leave_type_id', $leaveTypeId)->where('year', $year)->first();

        return $m ? $this->toDomainEntity($m) : null;
    }

    public function getBalancesForEmployee(int $tenantId, int $employeeId, int $year): array
    {
        return $this->model->where('tenant_id', $tenantId)->where('employee_id', $employeeId)->where('year', $year)->get()->map(fn ($m) => $this->toDomainEntity($m))->all();
    }

    private function mapModelToDomainEntity(LeaveBalanceModel $m): LeaveBalance
    {
        return new LeaveBalance($m->tenant_id, $m->employee_id, $m->leave_type_id, (int) $m->year, (float) $m->allocated, (float) $m->used, (float) $m->pending, (float) $m->carried, $m->created_at instanceof \DateTimeInterface ? $m->created_at : new \DateTimeImmutable($m->created_at ?? 'now'), $m->updated_at instanceof \DateTimeInterface ? $m->updated_at : new \DateTimeImmutable($m->updated_at ?? 'now'), $m->id);
    }
}
