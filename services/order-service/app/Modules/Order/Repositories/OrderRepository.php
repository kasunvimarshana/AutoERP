<?php

namespace App\Modules\Order\Repositories;

use App\Modules\Order\Models\Order;
use App\Modules\Order\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderRepository implements OrderRepositoryInterface
{
    public function __construct(private Order $model) {}

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with('items');

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $allowedSorts = ['order_number', 'total_amount', 'status', 'created_at', 'updated_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, in_array($sortDir, ['asc', 'desc']) ? $sortDir : 'desc');
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Order
    {
        return $this->model->with('items')->find($id);
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return $this->model->with('items')->where('order_number', $orderNumber)->first();
    }

    public function create(array $data): Order
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Order
    {
        $order = $this->findById($id);
        if (!$order) {
            return null;
        }
        $order->update($data);
        return $order->fresh(['items']);
    }

    public function delete(int $id): bool
    {
        $order = $this->findById($id);
        if (!$order) {
            return false;
        }
        return (bool) $order->delete();
    }

    public function updateStatus(int $id, string $status): ?Order
    {
        return $this->update($id, ['status' => $status]);
    }
}
