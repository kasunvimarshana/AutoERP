<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\Attribute;
use Modules\Product\Domain\RepositoryInterfaces\AttributeRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\AttributeModel;

class EloquentAttributeRepository extends EloquentRepository implements AttributeRepositoryInterface
{
    public function __construct(AttributeModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (AttributeModel $model): Attribute => $this->mapModelToDomainEntity($model));
    }

    public function save(Attribute $entity): Attribute
    {
        $data = [
            'tenant_id' => $entity->getTenantId(),
            'group_id' => $entity->getGroupId(),
            'name' => $entity->getName(),
            'type' => $entity->getType(),
            'is_required' => $entity->isRequired(),
            'code' => $entity->getCode(),
            'description' => $entity->getDescription(),
            'sort_order' => $entity->getSortOrder(),
            'is_active' => $entity->isActive(),
            'is_filterable' => $entity->isFilterable(),
            'metadata' => $entity->getMetadata(),
        ];

        if ($entity->getId()) {
            $model = $this->update($entity->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var AttributeModel $model */
        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?Attribute
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(AttributeModel $model): Attribute
    {
        return new Attribute(
            tenantId: (int) $model->tenant_id,
            name: (string) $model->name,
            type: (string) $model->type,
            isRequired: (bool) $model->is_required,
            groupId: $model->group_id !== null ? (int) $model->group_id : null,
            code: $model->code,
            description: $model->description,
            sortOrder: (int) $model->sort_order,
            isActive: (bool) $model->is_active,
            isFilterable: (bool) $model->is_filterable,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
