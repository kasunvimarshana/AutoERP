<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\ProductAttribute;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttributeRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductAttributeModel;

class EloquentProductAttributeRepository extends EloquentRepository implements ProductAttributeRepositoryInterface
{
    public function __construct(ProductAttributeModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (ProductAttributeModel $model): ProductAttribute => $this->mapModelToDomainEntity($model));
    }

    public function save(ProductAttribute $attribute): ProductAttribute
    {
        $data = [
            'tenant_id' => $attribute->getTenantId(),
            'group_id' => $attribute->getGroupId(),
            'name' => $attribute->getName(),
            'type' => $attribute->getType(),
            'is_required' => $attribute->isRequired(),
        ];

        if ($attribute->getId()) {
            $model = $this->update($attribute->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var ProductAttributeModel $model */

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?ProductAttribute
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(ProductAttributeModel $model): ProductAttribute
    {
        return new ProductAttribute(
            tenantId: (int) $model->tenant_id,
            groupId: $model->group_id !== null ? (int) $model->group_id : null,
            name: (string) $model->name,
            type: (string) $model->type,
            isRequired: (bool) $model->is_required,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
