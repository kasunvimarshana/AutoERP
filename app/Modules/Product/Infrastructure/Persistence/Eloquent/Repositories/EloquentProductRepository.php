<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;

class EloquentProductRepository extends EloquentRepository implements ProductRepositoryInterface
{
    public function __construct(ProductModel $model)
    {
        parent::__construct($model);
    }

    public function findBySku(string $sku): mixed
    {
        return $this->model->newQuery()->where('sku', $sku)->first();
    }

    public function findByType(string $type): Collection
    {
        return $this->model->newQuery()->where('type', $type)->get();
    }

    /**
     * Find a product by its GS1 GTIN within a tenant.
     */
    public function findByGtin(int $tenantId, string $gtin): mixed
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('gtin', $gtin)
            ->first();
    }
}
