<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\InventoryTransaction;
use App\Repositories\Interfaces\InventoryTransactionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InventoryTransactionRepository implements InventoryTransactionRepositoryInterface
{
    private const ALLOWED_SORT_COLUMNS = [
        'id', 'inventory_id', 'product_id', 'type', 'quantity',
        'previous_quantity', 'new_quantity', 'created_at',
    ];

    public function __construct(private readonly InventoryTransaction $model) {}

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (! empty($filters['inventory_id'])) {
            $query->byInventory((int) $filters['inventory_id']);
        }

        if (! empty($filters['product_id'])) {
            $query->byProduct((int) $filters['product_id']);
        }

        if (! empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            $query->inDateRange($filters['date_from'], $filters['date_to']);
        } elseif (! empty($filters['date_from'])) {
            $query->afterDate($filters['date_from']);
        } elseif (! empty($filters['date_to'])) {
            $query->beforeDate($filters['date_to']);
        }

        $sortBy        = $this->sanitiseSortColumn($filters['sort_by'] ?? 'created_at');
        $sortDirection = $this->sanitiseSortDirection($filters['sort_direction'] ?? 'desc');

        $query->orderBy($sortBy, $sortDirection);

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?InventoryTransaction
    {
        return $this->model->newQuery()->find($id);
    }

    public function create(array $data): InventoryTransaction
    {
        return $this->model->newQuery()->create($data);
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
