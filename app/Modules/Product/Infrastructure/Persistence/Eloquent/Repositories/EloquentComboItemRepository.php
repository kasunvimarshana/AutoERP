<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\ComboItem;
use Modules\Product\Domain\RepositoryInterfaces\ComboItemRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ComboItemModel;

class EloquentComboItemRepository extends EloquentRepository implements ComboItemRepositoryInterface
{
    public function __construct(ComboItemModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (ComboItemModel $model): ComboItem => $this->mapModelToDomainEntity($model));
    }

    public function save(ComboItem $entity): ComboItem
    {
        $data = [
            'tenant_id' => $entity->getTenantId(),
            'combo_product_id' => $entity->getComboProductId(),
            'component_product_id' => $entity->getComponentProductId(),
            'component_variant_id' => $entity->getComponentVariantId(),
            'quantity' => $entity->getQuantity(),
            'uom_id' => $entity->getUomId(),
            'metadata' => $entity->getMetadata(),
            'sort_order' => $entity->getSortOrder(),
            'is_optional' => $entity->isOptional(),
            'notes' => $entity->getNotes(),
        ];

        if ($entity->getId()) {
            $model = $this->update($entity->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var ComboItemModel $model */
        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?ComboItem
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(ComboItemModel $model): ComboItem
    {
        return new ComboItem(
            tenantId: (int) $model->tenant_id,
            comboProductId: (int) $model->combo_product_id,
            componentProductId: (int) $model->component_product_id,
            quantity: (string) $model->quantity,
            uomId: (int) $model->uom_id,
            componentVariantId: $model->component_variant_id !== null ? (int) $model->component_variant_id : null,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            sortOrder: (int) $model->sort_order,
            isOptional: (bool) $model->is_optional,
            notes: $model->notes,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
