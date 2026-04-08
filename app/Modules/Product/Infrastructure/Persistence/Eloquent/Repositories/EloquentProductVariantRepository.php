<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductVariantModel;

class EloquentProductVariantRepository extends EloquentRepository implements ProductVariantRepositoryInterface
{
    public function __construct(ProductVariantModel $model)
    {
        parent::__construct($model);
    }

    public function findBySku(string $sku, int $tenantId): mixed
    {
        return $this->model->where('sku', $sku)->where('tenant_id', $tenantId)->first();
    }

    public function getByProduct(int $productId): Collection
    {
        return $this->model->where('product_id', $productId)->get();
    }
}
