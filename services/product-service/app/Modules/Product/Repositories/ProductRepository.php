<?php

namespace App\Modules\Product\Repositories;

use App\Modules\Product\DTOs\ProductDTO;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Repositories\Interfaces\ProductRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(private Product $model) {}

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $allowedSorts = ['name', 'price', 'created_at', 'updated_at', 'sku', 'category'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, in_array($sortDir, ['asc', 'desc']) ? $sortDir : 'desc');
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Product
    {
        return $this->model->find($id);
    }

    public function findBySku(string $sku): ?Product
    {
        return $this->model->where('sku', $sku)->first();
    }

    public function create(ProductDTO $dto): Product
    {
        return $this->model->create($dto->toArray());
    }

    public function update(int $id, array $data): ?Product
    {
        $product = $this->findById($id);
        if (!$product) {
            return null;
        }
        $product->update($data);
        return $product->fresh();
    }

    public function delete(int $id): bool
    {
        $product = $this->findById($id);
        if (!$product) {
            return false;
        }
        return (bool) $product->delete();
    }

    public function getAllCategories(): array
    {
        return $this->model->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->toArray();
    }
}
