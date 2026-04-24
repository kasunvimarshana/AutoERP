<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\VariantAttribute;
use Modules\Product\Domain\RepositoryInterfaces\VariantAttributeRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\VariantAttributeModel;

class EloquentVariantAttributeRepository extends EloquentRepository implements VariantAttributeRepositoryInterface
{
    public function __construct(VariantAttributeModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (VariantAttributeModel $model): VariantAttribute => $this->mapModelToDomainEntity($model));
    }

    public function save(VariantAttribute $variantAttribute): VariantAttribute
    {
        $data = [
            'tenant_id' => $variantAttribute->getTenantId(),
            'product_id' => $variantAttribute->getProductId(),
            'attribute_id' => $variantAttribute->getAttributeId(),
            'is_required' => $variantAttribute->isRequired(),
            'is_variation_axis' => $variantAttribute->isVariationAxis(),
            'display_order' => $variantAttribute->getDisplayOrder(),
        ];

        if ($variantAttribute->getId()) {
            $model = $this->update($variantAttribute->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var VariantAttributeModel $model */

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?VariantAttribute
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(VariantAttributeModel $model): VariantAttribute
    {
        return new VariantAttribute(
            tenantId: (int) $model->tenant_id,
            productId: (int) $model->product_id,
            attributeId: (int) $model->attribute_id,
            isRequired: (bool) $model->is_required,
            isVariationAxis: (bool) $model->is_variation_axis,
            displayOrder: (int) $model->display_order,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
