<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\Department;
use Modules\HR\Domain\RepositoryInterfaces\DepartmentRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\DepartmentModel;

class EloquentDepartmentRepository extends EloquentRepository implements DepartmentRepositoryInterface
{
    public function __construct(DepartmentModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (DepartmentModel $model): Department => $this->mapModelToDomainEntity($model));
    }

    public function save(Department $department): Department
    {
        $savedModel = null;

        DB::transaction(function () use ($department, &$savedModel) {
            $data = [
                'tenant_id'   => $department->getTenantId(),
                'name'        => $department->getName()->value(),
                'code'        => $department->getCode()?->value(),
                'description' => $department->getDescription(),
                'manager_id'  => $department->getManagerId(),
                'parent_id'   => $department->getParentId(),
                'lft'         => $department->getLft(),
                'rgt'         => $department->getRgt(),
                'metadata'    => $department->getMetadata()->toArray(),
                'is_active'   => $department->isActive(),
            ];

            if ($department->getId()) {
                $savedModel = $this->update($department->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof DepartmentModel) {
            throw new \RuntimeException('Failed to save department.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function getTree(): array
    {
        return $this->model->orderBy('lft')
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m))
            ->all();
    }

    public function getByParent(?int $parentId): array
    {
        return $this->model->where('parent_id', $parentId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m))
            ->all();
    }

    private function mapModelToDomainEntity(DepartmentModel $model): Department
    {
        return new Department(
            tenantId:    $model->tenant_id,
            name:        new Name($model->name),
            code:        $model->code !== null ? new Code($model->code) : null,
            description: $model->description,
            managerId:   $model->manager_id,
            parentId:    $model->parent_id,
            lft:         (int) ($model->lft ?? 0),
            rgt:         (int) ($model->rgt ?? 0),
            metadata:    isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            isActive:    (bool) $model->is_active,
            id:          $model->id,
            createdAt:   $model->created_at,
            updatedAt:   $model->updated_at,
        );
    }
}
