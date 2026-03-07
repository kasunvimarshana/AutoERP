<?php

namespace App\Repositories;

use App\Models\Order;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository implements OrderRepositoryInterface
{
    public function findById(int $id): ?Order
    {
        return Order::with(['items', 'sagaLogs'])->find($id);
    }

    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function update(Order $order, array $data): Order
    {
        $order->update($data);

        return $order->fresh(['items', 'sagaLogs']);
    }

    public function delete(Order $order): bool
    {
        return $order->delete();
    }

    public function paginate(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection
    {
        $query = Order::with('items')
            ->when(isset($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(isset($filters['user_id']), fn (Builder $q) => $q->where('user_id', $filters['user_id']))
            ->when(isset($filters['date_from']), fn (Builder $q) => $q->where('created_at', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn (Builder $q) => $q->where('created_at', '<=', $filters['date_to']))
            ->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_dir'] ?? 'desc');

        return $perPage !== null ? $query->paginate($perPage) : $query->get();
    }
}
