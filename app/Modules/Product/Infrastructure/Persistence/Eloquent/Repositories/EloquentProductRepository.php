<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;

class EloquentProductRepository extends EloquentRepository implements ProductRepositoryInterface
{
    public function __construct(ProductModel $model)
    {
        parent::__construct($model);
    }

    public function findBySku(string $sku, int $tenantId): mixed
    {
        return $this->model->where('sku', $sku)->where('tenant_id', $tenantId)->first();
    }

    public function findByBarcode(string $barcode, int $tenantId): mixed
    {
        return $this->model->where('barcode', $barcode)->where('tenant_id', $tenantId)->first();
    }

    public function findByCategory(int $categoryId): Collection
    {
        return $this->model->where('category_id', $categoryId)->get();
    }

    public function searchByName(string $query, int $tenantId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('name', 'like', "%{$query}%")
            ->get();
    }
}
