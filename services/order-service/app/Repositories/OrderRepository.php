<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Order;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository implements OrderRepositoryInterface
{
    private const ALLOWED_SORT_COLUMNS = [
        'id', 'order_number', 'customer_name', 'customer_email',
        'status', 'total_amount', 'placed_at', 'confirmed_at',
        'shipped_at', 'delivered_at', 'created_at', 'updated_at',
    ];

    public function __construct(private readonly Order $model) {}

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with('items');

        $this->applyFilters($query, $filters);

        $sortBy        = $this->sanitiseSortColumn($filters['sort_by'] ?? 'placed_at');
        $sortDirection = $this->sanitiseSortDirection($filters['sort_direction'] ?? 'desc');

        $query->orderBy($sortBy, $sortDirection);

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage);
    }

    public function getByCustomerId(string $customerId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with('items')
            ->byCustomer($customerId);

        $this->applyFilters($query, $filters);

        $sortBy        = $this->sanitiseSortColumn($filters['sort_by'] ?? 'placed_at');
        $sortDirection = $this->sanitiseSortDirection($filters['sort_direction'] ?? 'desc');

        $query->orderBy($sortBy, $sortDirection);

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage);
    }

    public function findById(int $id, bool $withItems = false): ?Order
    {
        $query = $this->model->newQuery();

        if ($withItems) {
            $query->with('items');
        }

        return $query->find($id);
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return $this->model->newQuery()
            ->with('items')
            ->where('order_number', $orderNumber)
            ->first();
    }

    public function create(array $data): Order
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(int $id, array $data): ?Order
    {
        $order = $this->findById($id);

        if ($order === null) {
            return null;
        }

        $order->update($data);

        return $order->fresh(['items']);
    }

    public function delete(int $id): bool
    {
        $order = $this->findById($id);

        if ($order === null) {
            return false;
        }

        return (bool) $order->delete();
    }

    public function lockForUpdate(int $id): ?Order
    {
        return $this->model->newQuery()
            ->lockForUpdate()
            ->find($id);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Order> $query
     * @param  array<string, mixed>                         $filters
     */
    private function applyFilters(\Illuminate\Database\Eloquent\Builder $query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (! empty($filters['customer_id'])) {
            $query->byCustomer($filters['customer_id']);
        }

        if (! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            $query->placedBetween($filters['date_from'], $filters['date_to']);
        }
    }

    private function sanitiseSortColumn(string $column): string
    {
        return in_array($column, self::ALLOWED_SORT_COLUMNS, true) ? $column : 'placed_at';
    }

    private function sanitiseSortDirection(string $direction): string
    {
        return in_array(strtolower($direction), ['asc', 'desc'], true) ? strtolower($direction) : 'desc';
    }
}
