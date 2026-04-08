<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Domain\ValueObjects\Money;
use Modules\Core\Domain\ValueObjects\Sku;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\ProductVariation;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariationRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductVariationModel;

class EloquentProductVariationRepository extends EloquentRepository implements ProductVariationRepositoryInterface
{
    public function __construct(ProductVariationModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(
            fn (ProductVariationModel $model): ProductVariation => $this->mapModelToDomainEntity($model)
        );
    }

    public function findByProduct(int $productId): Collection
    {
        return $this->toDomainCollection(
            $this->model->where('product_id', $productId)->orderBy('sort_order')->get()
        );
    }

    public function save(ProductVariation $variation): ProductVariation
    {
        $data = [
            'product_id'       => $variation->getProductId(),
            'tenant_id'        => $variation->getTenantId(),
            'sku'              => $variation->getSku()->value(),
            'name'             => $variation->getName(),
            'price'            => $variation->getPrice()->getAmount(),
            'currency'         => $variation->getPrice()->getCurrency(),
            'attribute_values' => $variation->getAttributeValues(),
            'status'           => $variation->getStatus(),
            'sort_order'       => $variation->getSortOrder(),
            'metadata'         => $variation->getMetadata(),
        ];

        if ($variation->getId()) {
            $model = $this->update($variation->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var ProductVariationModel $model */

        return $this->mapModelToDomainEntity($model);
    }

    private function mapModelToDomainEntity(ProductVariationModel $model): ProductVariation
    {
        return new ProductVariation(
            productId:       $model->product_id,
            tenantId:        $model->tenant_id,
            sku:             new Sku($model->sku),
            name:            $model->name,
            price:           new Money((float) $model->price, $model->currency ?? 'USD'),
            attributeValues: $model->attribute_values ?? [],
            status:          $model->status ?? 'active',
            sortOrder:       (int) ($model->sort_order ?? 0),
            metadata:        $model->metadata,
            id:              $model->id,
            createdAt:       $model->created_at,
            updatedAt:       $model->updated_at,
        );
    }
}
