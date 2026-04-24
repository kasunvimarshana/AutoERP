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

    public function save(ComboItem $comboItem): ComboItem
    {
        $data = [
            'tenant_id' => $comboItem->getTenantId(),
            'combo_product_id' => $comboItem->getComboProductId(),
            'component_product_id' => $comboItem->getComponentProductId(),
            'component_variant_id' => $comboItem->getComponentVariantId(),
            'quantity' => $comboItem->getQuantity(),
            'uom_id' => $comboItem->getUomId(),
            'metadata' => $comboItem->getMetadata(),
        ];

        if ($comboItem->getId()) {
            $model = $this->update($comboItem->getId(), $data);
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
            tenantId: $model->tenant_id !== null ? (int) $model->tenant_id : null,
            comboProductId: (int) $model->combo_product_id,
            componentProductId: (int) $model->component_product_id,
            componentVariantId: $model->component_variant_id !== null ? (int) $model->component_variant_id : null,
            quantity: (string) $model->quantity,
            uomId: (int) $model->uom_id,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
