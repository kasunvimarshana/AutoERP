<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitType;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitTypeRepositoryInterface;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitTypeModel;

class EloquentOrganizationUnitTypeRepository extends EloquentRepository implements OrganizationUnitTypeRepositoryInterface
{
    public function __construct(OrganizationUnitTypeModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (OrganizationUnitTypeModel $model): OrganizationUnitType => $this->mapModelToDomainEntity($model));
    }

    public function save(OrganizationUnitType $organizationUnitType): OrganizationUnitType
    {
        $data = [
            'tenant_id' => $organizationUnitType->getTenantId(),
            'name' => $organizationUnitType->getName(),
            'level' => $organizationUnitType->getLevel(),
            'is_active' => $organizationUnitType->isActive(),
        ];

        if ($organizationUnitType->getId()) {
            $model = $this->update($organizationUnitType->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var OrganizationUnitTypeModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByTenantAndName(int $tenantId, string $name): ?OrganizationUnitType
    {
        /** @var OrganizationUnitTypeModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function find(int|string $id, array $columns = ['*']): ?OrganizationUnitType
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(OrganizationUnitTypeModel $model): OrganizationUnitType
    {
        return new OrganizationUnitType(
            tenantId: (int) $model->tenant_id,
            name: (string) $model->name,
            level: (int) $model->level,
            isActive: (bool) $model->is_active,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
