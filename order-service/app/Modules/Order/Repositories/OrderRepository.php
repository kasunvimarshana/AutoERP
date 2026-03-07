<?php

namespace App\Modules\Order\Repositories;

use App\Modules\Order\Models\Order;
use App\Modules\Order\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderRepository implements OrderRepositoryInterface
{
    private Order $model;

    public function __construct(Order $model)
    {
        $this->model = $model;
    }

    public function getAllWithFilters(array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (!empty($filters['filter'])) {
            // E.g. ?filter[status]=PENDING
            foreach ($filters['filter'] as $field => $value) {
                $query->where($field, $value);
            }
        }

        $query->latest();
        return $query->paginate($filters['limit'] ?? 15);
    }

    public function findById(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }
}
