<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\ProductAttributeGroup;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttributeGroupRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductAttributeGroupModel;

class EloquentProductAttributeGroupRepository extends EloquentRepository implements ProductAttributeGroupRepositoryInterface
{
    public function __construct(ProductAttributeGroupModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (ProductAttributeGroupModel $model): ProductAttributeGroup => $this->mapModelToDomainEntity($model));
    }

    public function save(ProductAttributeGroup $group): ProductAttributeGroup
    {
        $data = [
            'tenant_id' => $group->getTenantId(),
            'name' => $group->getName(),
        ];

        if ($group->getId()) {
            $model = $this->update($group->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var ProductAttributeGroupModel $model */

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?ProductAttributeGroup
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(ProductAttributeGroupModel $model): ProductAttributeGroup
    {
        return new ProductAttributeGroup(
            tenantId: (int) $model->tenant_id,
            name: (string) $model->name,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
