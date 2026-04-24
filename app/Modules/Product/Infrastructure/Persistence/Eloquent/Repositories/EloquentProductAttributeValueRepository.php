<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\ProductAttributeValue;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttributeValueRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductAttributeValueModel;

class EloquentProductAttributeValueRepository extends EloquentRepository implements ProductAttributeValueRepositoryInterface
{
    public function __construct(ProductAttributeValueModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (ProductAttributeValueModel $model): ProductAttributeValue => $this->mapModelToDomainEntity($model));
    }

    public function save(ProductAttributeValue $attributeValue): ProductAttributeValue
    {
        $data = [
            'tenant_id' => $attributeValue->getTenantId(),
            'attribute_id' => $attributeValue->getAttributeId(),
            'value' => $attributeValue->getValue(),
            'sort_order' => $attributeValue->getSortOrder(),
        ];

        if ($attributeValue->getId()) {
            $model = $this->update($attributeValue->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var ProductAttributeValueModel $model */

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?ProductAttributeValue
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(ProductAttributeValueModel $model): ProductAttributeValue
    {
        return new ProductAttributeValue(
            tenantId: $model->tenant_id !== null ? (int) $model->tenant_id : null,
            attributeId: (int) $model->attribute_id,
            value: (string) $model->value,
            sortOrder: (int) $model->sort_order,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
