<?php

namespace App\Modules\Product\Repositories;

use App\Base\BaseRepository;
use App\Modules\Product\Models\ProductVariant;
use Illuminate\Database\Eloquent\Collection;

class ProductVariantRepository extends BaseRepository
{
    public function __construct(ProductVariant $model)
    {
        parent::__construct($model);
    }

    public function findBySku(string $sku): ?ProductVariant
    {
        return $this->model->where('sku', $sku)->first();
    }

    public function findByBarcode(string $barcode): ?ProductVariant
    {
        return $this->model->where('barcode', $barcode)->first();
    }

    public function findByProduct(string $productId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('product_id', $productId)
            ->get();
    }

    public function getActive(array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('status', 'active')
            ->get();
    }

    public function findByTenant(string $tenantId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('tenant_id', $tenantId)
            ->get();
    }

    public function searchVariants(string $query, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where(function ($q) use ($query) {
                $q->where('sku', 'like', "%{$query}%")
                  ->orWhere('variant_name', 'like', "%{$query}%")
                  ->orWhere('barcode', 'like', "%{$query}%");
            })
            ->get();
    }
}
