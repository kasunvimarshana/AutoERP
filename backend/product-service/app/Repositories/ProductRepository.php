<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    public function findById(int $id): ?Product
    {
        return Product::with('category')->find($id);
    }

    public function findBySku(string $sku): ?Product
    {
        return Product::where('sku', $sku)->first();
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->fresh(['category']);
    }

    public function delete(Product $product): bool
    {
        return $product->delete();
    }

    public function paginate(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection
    {
        $query = Product::with('category')
            ->when(isset($filters['search']), fn (Builder $q) => $q->where(fn (Builder $inner) => $inner->where('name', 'like', "%{$filters['search']}%")
                ->orWhere('sku', 'like', "%{$filters['search']}%")
                ->orWhere('description', 'like', "%{$filters['search']}%")
            )
            )
            ->when(isset($filters['category_id']), fn (Builder $q) => $q->where('category_id', $filters['category_id'])
            )
            ->when(isset($filters['is_active']), fn (Builder $q) => $q->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN))
            )
            ->when(isset($filters['min_price']), fn (Builder $q) => $q->where('price', '>=', $filters['min_price'])
            )
            ->when(isset($filters['max_price']), fn (Builder $q) => $q->where('price', '<=', $filters['max_price'])
            )
            ->when(isset($filters['tag']), fn (Builder $q) => $q->whereJsonContains('tags', $filters['tag'])
            )
            ->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_dir'] ?? 'desc');

        return $perPage !== null ? $query->paginate($perPage) : $query->get();
    }
}
