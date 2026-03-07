<?php

namespace App\Modules\Product\Repositories;

use App\Modules\Product\Models\Product;
use App\Modules\Product\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProductRepository implements ProductRepositoryInterface
{
    private Product $model;

    public function __construct(Product $model)
    {
        $this->model = $model;
    }

    public function getAllWithFilters(array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // 1. Searching capabilities
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('sku', 'LIKE', "%{$search}%");
            });
        }

        // 2. Filtering
        if (!empty($filters['filter'])) {
            foreach ($filters['filter'] as $field => $value) {
                $query->where($field, $value);
            }
        }

        // 3. Sorting
        if (!empty($filters['sort'])) {
            $sorts = explode(',', $filters['sort']);
            foreach ($sorts as $sortColumn) {
                $direction = 'asc';
                if (str_starts_with($sortColumn, '-')) {
                    $direction = 'desc';
                    $sortColumn = ltrim($sortColumn, '-');
                }
                $query->orderBy($sortColumn, $direction);
            }
        } else {
            $query->latest(); // default sorting
        }

        // 4. Pagination
        $perPage = $filters['limit'] ?? 15;

        return $query->paginate($perPage);
    }

    public function findById(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $product = $this->findById($id);
        $product->update($data);
        return $product;
    }

    public function delete(int $id): bool
    {
        $product = $this->findById($id);
        return $product->delete();
    }
}
