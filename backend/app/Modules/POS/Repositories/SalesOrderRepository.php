<?php

namespace App\Modules\POS\Repositories;

use App\Modules\POS\Models\SalesOrder;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class SalesOrderRepository extends BaseRepository
{
    protected function model(): string
    {
        return SalesOrder::class;
    }

    public function findByOrderNumber(string $orderNumber): ?SalesOrder
    {
        return $this->model->where('order_number', $orderNumber)->first();
    }

    public function findByCustomer(int $customerId, array $filters = []): Collection
    {
        $query = $this->model->where('customer_id', $customerId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_date'])) {
            $query->where('order_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('order_date', '<=', $filters['to_date']);
        }

        return $query->with(['items.product', 'customer'])->get();
    }

    public function findByStatus(string $status): Collection
    {
        return $this->model
            ->where('status', $status)
            ->with(['items.product', 'customer'])
            ->get();
    }

    public function searchByCustomerOrOrderNumber(string $search): Collection
    {
        return $this->model
            ->where('order_number', 'like', "%{$search}%")
            ->orWhereHas('customer', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->with(['items.product', 'customer'])
            ->get();
    }

    public function generateOrderNumber(): string
    {
        $lastOrder = $this->model->orderBy('id', 'desc')->first();
        $lastNumber = $lastOrder ? intval(substr($lastOrder->order_number, 3)) : 0;

        return 'SO-'.str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
    }
}
