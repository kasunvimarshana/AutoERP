<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\LeaveType;
use Modules\HR\Domain\RepositoryInterfaces\LeaveTypeRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveTypeModel;

class EloquentLeaveTypeRepository extends EloquentRepository implements LeaveTypeRepositoryInterface
{
    public function __construct(LeaveTypeModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (LeaveTypeModel $m): LeaveType => $this->mapModelToDomainEntity($m));
    }

    public function save(LeaveType $entity): LeaveType
    {
        $data = ['tenant_id' => $entity->getTenantId(), 'name' => $entity->getName(), 'code' => $entity->getCode(), 'description' => $entity->getDescription(), 'max_days_per_year' => $entity->getMaxDaysPerYear(), 'carry_forward_days' => $entity->getCarryForwardDays(), 'is_paid' => $entity->isPaid(), 'requires_approval' => $entity->requiresApproval(), 'applicable_gender' => $entity->getApplicableGender(), 'min_service_days' => $entity->getMinServiceDays(), 'is_active' => $entity->isActive(), 'metadata' => $entity->getMetadata()];
        $model = $entity->getId() ? $this->update($entity->getId(), $data) : $this->create($data);

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?LeaveType
    {
        return parent::find($id, $columns);
    }

    public function findByTenantAndCode(int $tenantId, string $code): ?LeaveType
    {
        $m = $this->model->where('tenant_id', $tenantId)->where('code', $code)->first();

        return $m ? $this->toDomainEntity($m) : null;
    }

    private function mapModelToDomainEntity(LeaveTypeModel $m): LeaveType
    {
        return new LeaveType($m->tenant_id, $m->name, $m->code, $m->description ?? '', (float) $m->max_days_per_year, (float) $m->carry_forward_days, (bool) $m->is_paid, (bool) $m->requires_approval, $m->applicable_gender, (int) $m->min_service_days, (bool) $m->is_active, $m->metadata ?? [], $m->created_at instanceof \DateTimeInterface ? $m->created_at : new \DateTimeImmutable($m->created_at ?? 'now'), $m->updated_at instanceof \DateTimeInterface ? $m->updated_at : new \DateTimeImmutable($m->updated_at ?? 'now'), $m->id);
    }
}
