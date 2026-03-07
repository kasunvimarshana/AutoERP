<?php
namespace App\Modules\Order\Repositories;

use App\Interfaces\RepositoryInterface;
use App\Models\Order;

class OrderRepository implements RepositoryInterface
{
    public function __construct(private Order $model) {}

    public function all(array $filters = [], array $relations = [])
    {
        $query = $this->model->newQuery()->with($relations);

        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['order_number'])) {
            $query->where('order_number', 'like', "%{$filters['order_number']}%");
        }

        return $query;
    }

    public function find(int $id, array $relations = []): ?Order
    {
        return $this->model->with($relations)->findOrFail($id);
    }

    public function create(array $data): Order
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Order
    {
        $order = $this->find($id);
        $order->update($data);
        return $order->fresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->findOrFail($id)->delete();
    }

    public function paginate(int $perPage = 15, array $filters = [], array $relations = [])
    {
        return $this->all($filters, $relations)->paginate($perPage);
    }
}
