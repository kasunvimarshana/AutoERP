<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Finance\Domain\Entities\CostCenter;
use Modules\Finance\Domain\RepositoryInterfaces\CostCenterRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\CostCenterModel;

class EloquentCostCenterRepository extends EloquentRepository implements CostCenterRepositoryInterface
{
    public function __construct(CostCenterModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (CostCenterModel $model): CostCenter => $this->mapModelToDomainEntity($model));
    }

    public function save(CostCenter $costCenter): CostCenter
    {
        $data = [
            'tenant_id' => $costCenter->getTenantId(),
            'parent_id' => $costCenter->getParentId(),
            'code' => $costCenter->getCode(),
            'name' => $costCenter->getName(),
            'description' => $costCenter->getDescription(),
            'is_active' => $costCenter->isActive(),
            'path' => $costCenter->getPath(),
            'depth' => $costCenter->getDepth(),
            'row_version' => $costCenter->getRowVersion(),
        ];

        if ($costCenter->getId()) {
            $model = $this->update($costCenter->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var CostCenterModel $model */
        return $this->toDomainEntity($model);
    }

    public function findByTenantAndCode(int $tenantId, string $code): ?CostCenter
    {
        /** @var CostCenterModel|null $model */
        $model = $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    private function mapModelToDomainEntity(CostCenterModel $model): CostCenter
    {
        return new CostCenter(
            tenantId: (int) $model->tenant_id,
            code: (string) $model->code,
            name: (string) $model->name,
            parentId: $model->parent_id !== null ? (int) $model->parent_id : null,
            description: $model->description,
            isActive: (bool) $model->is_active,
            path: $model->path,
            depth: (int) ($model->depth ?? 0),
            rowVersion: (int) ($model->row_version ?? 1),
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
