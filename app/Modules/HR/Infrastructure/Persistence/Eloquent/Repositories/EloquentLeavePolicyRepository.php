<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\LeavePolicy;
use Modules\HR\Domain\RepositoryInterfaces\LeavePolicyRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeavePolicyModel;

class EloquentLeavePolicyRepository extends EloquentRepository implements LeavePolicyRepositoryInterface
{
    public function __construct(LeavePolicyModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (LeavePolicyModel $m): LeavePolicy => $this->mapModelToDomainEntity($m));
    }

    public function save(LeavePolicy $entity): LeavePolicy
    {
        $data = ['tenant_id' => $entity->getTenantId(), 'leave_type_id' => $entity->getLeaveTypeId(), 'name' => $entity->getName(), 'accrual_type' => $entity->getAccrualType(), 'accrual_amount' => $entity->getAccrualAmount(), 'org_unit_id' => $entity->getOrgUnitId(), 'is_active' => $entity->isActive(), 'metadata' => $entity->getMetadata()];
        $model = $entity->getId() ? $this->update($entity->getId(), $data) : $this->create($data);

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?LeavePolicy
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(LeavePolicyModel $m): LeavePolicy
    {
        return new LeavePolicy($m->tenant_id, $m->leave_type_id, $m->name, $m->accrual_type ?? 'annual', (float) $m->accrual_amount, $m->org_unit_id, (bool) $m->is_active, $m->metadata ?? [], $m->created_at instanceof \DateTimeInterface ? $m->created_at : new \DateTimeImmutable($m->created_at ?? 'now'), $m->updated_at instanceof \DateTimeInterface ? $m->updated_at : new \DateTimeImmutable($m->updated_at ?? 'now'), $m->id);
    }
}
