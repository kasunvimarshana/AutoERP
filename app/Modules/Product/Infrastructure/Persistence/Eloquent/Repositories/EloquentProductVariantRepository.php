<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Contracts\Repositories\ProductVariantRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductVariantModel;

class EloquentProductVariantRepository extends EloquentRepository implements ProductVariantRepositoryInterface
{
    public function __construct(ProductVariantModel $model)
    {
        parent::__construct($model);
    }

    public function findByProduct(string $productId): Collection
    {
        return $this->model->newQuery()->where('product_id', $productId)->get();
    }

    public function findBySku(string $sku): mixed
    {
        return $this->model->newQuery()->where('sku', $sku)->first();
    }
}
