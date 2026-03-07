<?php

namespace App\Repositories;

use App\DTOs\ProductDTO;
use App\Models\Product;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProductRepository implements ProductRepositoryInterface
{
    public function queryBuilder(): QueryBuilder
    {
        return QueryBuilder::for(Product::class)
            ->allowedFilters([
                AllowedFilter::exact('category_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('is_active'),
                AllowedFilter::partial('name'),
                AllowedFilter::partial('sku'),
                AllowedFilter::partial('description'),
                AllowedFilter::scope('by_status'),
                AllowedFilter::scope('by_category'),
            ])
            ->allowedSorts([
                'name',
                'sku',
                'price',
                'created_at',
                'updated_at',
            ])
            ->allowedIncludes(['category'])
            ->defaultSort('-created_at')
            ->with('category');
    }

    public function findById(int $id): ?Product
    {
        return Product::with('category')->find($id);
    }

    public function findBySku(string $sku, string $tenantId): ?Product
    {
        return Product::withoutGlobalScope('tenant')
            ->where('sku', $sku)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function create(ProductDTO $dto): Product
    {
        return Product::create($dto->toArray());
    }

    public function update(Product $product, ProductDTO $dto): Product
    {
        $product->update($dto->toArray());
        return $product->fresh('category');
    }

    public function delete(Product $product): bool
    {
        return $product->delete();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->queryBuilder()->paginate($perPage)->appends(request()->query());
    }
}
