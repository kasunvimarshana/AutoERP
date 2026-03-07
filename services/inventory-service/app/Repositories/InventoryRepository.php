<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Inventory;
use App\Repositories\Interfaces\InventoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class InventoryRepository implements InventoryRepositoryInterface
{
    private const ALLOWED_SORT_COLUMNS = [
        'id', 'product_id', 'quantity', 'reserved_quantity', 'warehouse_location',
        'reorder_level', 'unit_cost', 'status', 'last_counted_at', 'created_at', 'updated_at',
    ];

    public function __construct(private readonly Inventory $model) {}

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (! empty($filters['warehouse_location'])) {
            $query->byWarehouseLocation($filters['warehouse_location']);
        }

        if (isset($filters['product_id'])) {
            $query->where('product_id', (int) $filters['product_id']);
        }

        if (isset($filters['low_stock']) && filter_var($filters['low_stock'], FILTER_VALIDATE_BOOLEAN)) {
            $query->lowStock();
        }

        if (isset($filters['out_of_stock']) && filter_var($filters['out_of_stock'], FILTER_VALIDATE_BOOLEAN)) {
            $query->outOfStock();
        }

        $sortBy        = $this->sanitiseSortColumn($filters['sort_by'] ?? 'created_at');
        $sortDirection = $this->sanitiseSortDirection($filters['sort_direction'] ?? 'desc');

        $query->orderBy($sortBy, $sortDirection);

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Inventory
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByProductId(int $productId): Collection
    {
        return $this->model->newQuery()
            ->where('product_id', $productId)
            ->get();
    }

    public function findFirstByProductId(int $productId): ?Inventory
    {
        return $this->model->newQuery()
            ->where('product_id', $productId)
            ->first();
    }

    public function create(array $data): Inventory
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(int $id, array $data): ?Inventory
    {
        $inventory = $this->findById($id);

        if ($inventory === null) {
            return null;
        }

        $inventory->update($data);

        return $inventory->fresh();
    }

    public function delete(int $id): bool
    {
        $inventory = $this->findById($id);

        if ($inventory === null) {
            return false;
        }

        return (bool) $inventory->delete();
    }

    public function deleteByProductId(int $productId): int
    {
        return $this->model->newQuery()
            ->where('product_id', $productId)
            ->delete();
    }

    public function lockForUpdate(int $id): ?Inventory
    {
        return $this->model->newQuery()
            ->lockForUpdate()
            ->find($id);
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
