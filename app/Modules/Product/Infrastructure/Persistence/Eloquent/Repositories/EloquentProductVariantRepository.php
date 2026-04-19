<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\ProductVariant;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductVariantModel;

class EloquentProductVariantRepository extends EloquentRepository implements ProductVariantRepositoryInterface
{
    public function __construct(ProductVariantModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (ProductVariantModel $model): ProductVariant => $this->mapModelToDomainEntity($model));
    }

    public function save(ProductVariant $productVariant): ProductVariant
    {
        $data = [
            'product_id' => $productVariant->getProductId(),
            'sku' => $productVariant->getSku(),
            'name' => $productVariant->getName(),
            'is_default' => $productVariant->isDefault(),
            'is_active' => $productVariant->isActive(),
            'metadata' => $productVariant->getMetadata(),
        ];

        if ($productVariant->getId()) {
            $model = $this->update($productVariant->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var ProductVariantModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByProductAndSku(int $productId, string $sku): ?ProductVariant
    {
        /** @var ProductVariantModel|null $model */
        $model = $this->model->newQuery()
            ->where('product_id', $productId)
            ->where('sku', $sku)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function find(int|string $id, array $columns = ['*']): ?ProductVariant
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(ProductVariantModel $model): ProductVariant
    {
        return new ProductVariant(
            id: (int) $model->id,
            productId: (int) $model->product_id,
            sku: $model->sku,
            name: (string) $model->name,
            isDefault: (bool) $model->is_default,
            isActive: (bool) $model->is_active,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
