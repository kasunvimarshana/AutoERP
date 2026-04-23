<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\AttributeValue;
use Modules\Product\Domain\RepositoryInterfaces\AttributeValueRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\AttributeValueModel;

class EloquentAttributeValueRepository extends EloquentRepository implements AttributeValueRepositoryInterface
{
    public function __construct(AttributeValueModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (AttributeValueModel $model): AttributeValue => $this->mapModelToDomainEntity($model));
    }

    public function save(AttributeValue $entity): AttributeValue
    {
        $data = [
            'tenant_id' => $entity->getTenantId(),
            'attribute_id' => $entity->getAttributeId(),
            'value' => $entity->getValue(),
            'sort_order' => $entity->getSortOrder(),
            'label' => $entity->getLabel(),
            'color_code' => $entity->getColorCode(),
            'is_active' => $entity->isActive(),
            'metadata' => $entity->getMetadata(),
        ];

        if ($entity->getId()) {
            $model = $this->update($entity->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var AttributeValueModel $model */
        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?AttributeValue
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(AttributeValueModel $model): AttributeValue
    {
        return new AttributeValue(
            tenantId: (int) $model->tenant_id,
            attributeId: (int) $model->attribute_id,
            value: (string) $model->value,
            sortOrder: (int) $model->sort_order,
            label: $model->label,
            colorCode: $model->color_code,
            isActive: (bool) $model->is_active,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
