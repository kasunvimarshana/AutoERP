<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\AttributeGroup;
use Modules\Product\Domain\RepositoryInterfaces\AttributeGroupRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\AttributeGroupModel;

class EloquentAttributeGroupRepository extends EloquentRepository implements AttributeGroupRepositoryInterface
{
    public function __construct(AttributeGroupModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (AttributeGroupModel $model): AttributeGroup => $this->mapModelToDomainEntity($model));
    }

    public function save(AttributeGroup $entity): AttributeGroup
    {
        $data = [
            'tenant_id' => $entity->getTenantId(),
            'name' => $entity->getName(),
            'code' => $entity->getCode(),
            'description' => $entity->getDescription(),
            'sort_order' => $entity->getSortOrder(),
            'is_active' => $entity->isActive(),
            'metadata' => $entity->getMetadata(),
        ];

        if ($entity->getId()) {
            $model = $this->update($entity->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var AttributeGroupModel $model */
        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?AttributeGroup
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(AttributeGroupModel $model): AttributeGroup
    {
        return new AttributeGroup(
            tenantId: (int) $model->tenant_id,
            name: (string) $model->name,
            code: $model->code,
            description: $model->description,
            sortOrder: (int) $model->sort_order,
            isActive: (bool) $model->is_active,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
