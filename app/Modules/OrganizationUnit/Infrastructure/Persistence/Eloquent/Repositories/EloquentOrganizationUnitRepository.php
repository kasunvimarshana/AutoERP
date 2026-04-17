<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;

class EloquentOrganizationUnitRepository extends EloquentRepository implements OrganizationUnitRepositoryInterface
{
    public function __construct(OrganizationUnitModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (OrganizationUnitModel $model): OrganizationUnit => $this->mapModelToDomainEntity($model));
    }

    public function save(OrganizationUnit $organizationUnit): OrganizationUnit
    {
        $data = [
            'tenant_id' => $organizationUnit->getTenantId(),
            'type_id' => $organizationUnit->getTypeId(),
            'parent_id' => $organizationUnit->getParentId(),
            'manager_user_id' => $organizationUnit->getManagerUserId(),
            'name' => $organizationUnit->getName(),
            'code' => $organizationUnit->getCode(),
            'path' => $organizationUnit->getPath(),
            'depth' => $organizationUnit->getDepth(),
            'metadata' => $organizationUnit->getMetadata(),
            'is_active' => $organizationUnit->isActive(),
            'description' => $organizationUnit->getDescription(),
            '_lft' => $organizationUnit->getLeft(),
            '_rgt' => $organizationUnit->getRight(),
        ];

        if ($organizationUnit->getId()) {
            $model = $this->update($organizationUnit->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var OrganizationUnitModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByCode(int $tenantId, string $code): ?OrganizationUnit
    {
        /** @var OrganizationUnitModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    private function mapModelToDomainEntity(OrganizationUnitModel $model): OrganizationUnit
    {
        return new OrganizationUnit(
            tenantId: (int) $model->tenant_id,
            typeId: $model->type_id !== null ? (int) $model->type_id : null,
            parentId: $model->parent_id !== null ? (int) $model->parent_id : null,
            managerUserId: $model->manager_user_id !== null ? (int) $model->manager_user_id : null,
            name: (string) $model->name,
            code: $model->code,
            path: $model->path,
            depth: (int) $model->depth,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            isActive: (bool) $model->is_active,
            description: $model->description,
            left: (int) $model->_lft,
            right: (int) $model->_rgt,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
