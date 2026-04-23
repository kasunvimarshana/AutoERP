<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\ShiftAssignment;
use Modules\HR\Domain\RepositoryInterfaces\ShiftAssignmentRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\ShiftAssignmentModel;

class EloquentShiftAssignmentRepository extends EloquentRepository implements ShiftAssignmentRepositoryInterface
{
    public function __construct(ShiftAssignmentModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (ShiftAssignmentModel $m): ShiftAssignment => $this->mapModelToDomainEntity($m));
    }

    public function save(ShiftAssignment $entity): ShiftAssignment
    {
        $data = ['tenant_id' => $entity->getTenantId(), 'employee_id' => $entity->getEmployeeId(), 'shift_id' => $entity->getShiftId(), 'effective_from' => $entity->getEffectiveFrom()->format('Y-m-d'), 'effective_to' => $entity->getEffectiveTo()?->format('Y-m-d')];
        $model = $entity->getId() ? $this->update($entity->getId(), $data) : $this->create($data);

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?ShiftAssignment
    {
        return parent::find($id, $columns);
    }

    public function findCurrentForEmployee(int $tenantId, int $employeeId, string $date): ?ShiftAssignment
    {
        $m = $this->model->where('tenant_id', $tenantId)->where('employee_id', $employeeId)->where('effective_from', '<=', $date)->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date))->latest('effective_from')->first();

        return $m ? $this->toDomainEntity($m) : null;
    }

    private function mapModelToDomainEntity(ShiftAssignmentModel $m): ShiftAssignment
    {
        $eto = $m->effective_to instanceof \DateTimeInterface ? $m->effective_to : ($m->effective_to ? new \DateTimeImmutable($m->effective_to) : null);

        return new ShiftAssignment($m->tenant_id, $m->employee_id, $m->shift_id, $m->effective_from instanceof \DateTimeInterface ? $m->effective_from : new \DateTimeImmutable($m->effective_from), $eto, $m->created_at instanceof \DateTimeInterface ? $m->created_at : new \DateTimeImmutable($m->created_at ?? 'now'), $m->updated_at instanceof \DateTimeInterface ? $m->updated_at : new \DateTimeImmutable($m->updated_at ?? 'now'), $m->id);
    }
}
