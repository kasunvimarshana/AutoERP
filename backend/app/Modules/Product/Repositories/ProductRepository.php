<?php

namespace App\Modules\Product\Repositories;

use App\Base\BaseRepository;
use App\Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository extends BaseRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function findBySku(string $sku): ?Product
    {
        return $this->model->where('sku', $sku)->first();
    }

    public function findByTenant(string $tenantId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('tenant_id', $tenantId)
            ->get();
    }

    public function searchProducts(string $query, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->get();
    }

    public function getActive(array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('status', 'active')
            ->get();
    }

    public function getWithVariants(array $relations = []): Collection
    {
        $relations[] = 'productVariants';
        
        return $this->model->with($relations)
            ->where('is_variant_product', true)
            ->get();
    }

    public function getByCategory(string $category, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('category', $category)
            ->get();
    }

    public function getByType(string $type, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('type', $type)
            ->get();
    }
}
