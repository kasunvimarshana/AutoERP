<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    private const ALLOWED_SORT_COLUMNS = [
        'id', 'name', 'price', 'category', 'status', 'stock_quantity', 'created_at', 'updated_at',
    ];

    public function __construct(private readonly Product $model) {}

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (! empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['min_price'], $filters['max_price'])) {
            $query->priceBetween((float) $filters['min_price'], (float) $filters['max_price']);
        } elseif (isset($filters['min_price'])) {
            $query->where('price', '>=', (float) $filters['min_price']);
        } elseif (isset($filters['max_price'])) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        $sortBy        = $this->sanitiseSortColumn($filters['sort_by'] ?? 'created_at');
        $sortDirection = $this->sanitiseSortDirection($filters['sort_direction'] ?? 'desc');

        $query->orderBy($sortBy, $sortDirection);

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Product
    {
        return $this->model->newQuery()->find($id);
    }

    public function findBySku(string $sku): ?Product
    {
        return $this->model->newQuery()->where('sku', $sku)->first();
    }

    public function create(array $data): Product
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(int $id, array $data): ?Product
    {
        $product = $this->findById($id);

        if ($product === null) {
            return null;
        }

        $product->update($data);

        return $product->fresh();
    }

    public function delete(int $id): bool
    {
        $product = $this->findById($id);

        if ($product === null) {
            return false;
        }

        return (bool) $product->delete();
    }

    public function search(string $term, int $limit = 15): Collection
    {
        return $this->model->newQuery()
            ->search($term)
            ->limit($limit)
            ->get();
    }

    private function sanitiseSortColumn(string $column): string
    {
        return in_array($column, self::ALLOWED_SORT_COLUMNS, true) ? $column : 'created_at';
    }

    private function sanitiseSortDirection(string $direction): string
    {
        return in_array(strtolower($direction), ['asc', 'desc'], true) ? strtolower($direction) : 'desc';
    }
}
