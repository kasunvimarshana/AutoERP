<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Domain\ValueObjects\Money;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\ComboItem;
use Modules\Product\Domain\RepositoryInterfaces\ComboItemRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductComboItemModel;

class EloquentComboItemRepository extends EloquentRepository implements ComboItemRepositoryInterface
{
    public function __construct(ProductComboItemModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(
            fn (ProductComboItemModel $model): ComboItem => $this->mapModelToDomainEntity($model)
        );
    }

    public function findByProduct(int $productId): Collection
    {
        return $this->toDomainCollection(
            $this->model->where('product_id', $productId)->orderBy('sort_order')->get()
        );
    }

    public function save(ComboItem $comboItem): ComboItem
    {
        $data = [
            'product_id'           => $comboItem->getProductId(),
            'tenant_id'            => $comboItem->getTenantId(),
            'component_product_id' => $comboItem->getComponentProductId(),
            'quantity'             => $comboItem->getQuantity(),
            'price_override'       => $comboItem->getPriceOverride()?->getAmount(),
            'currency'             => $comboItem->getPriceOverride()?->getCurrency(),
            'sort_order'           => $comboItem->getSortOrder(),
            'metadata'             => $comboItem->getMetadata(),
        ];

        if ($comboItem->getId()) {
            $model = $this->update($comboItem->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var ProductComboItemModel $model */

        return $this->mapModelToDomainEntity($model);
    }

    private function mapModelToDomainEntity(ProductComboItemModel $model): ComboItem
    {
        $priceOverride = null;
        if ($model->price_override !== null) {
            $priceOverride = new Money((float) $model->price_override, $model->currency ?? 'USD');
        }

        return new ComboItem(
            productId:          $model->product_id,
            tenantId:           $model->tenant_id,
            componentProductId: $model->component_product_id,
            quantity:           (float) $model->quantity,
            priceOverride:      $priceOverride,
            sortOrder:          (int) ($model->sort_order ?? 0),
            metadata:           $model->metadata,
            id:                 $model->id,
            createdAt:          $model->created_at,
            updatedAt:          $model->updated_at,
        );
    }
}
