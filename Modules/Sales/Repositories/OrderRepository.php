<?php

declare(strict_types=1);

namespace Modules\Sales\Repositories;

use Modules\Core\Repositories\BaseRepository;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Exceptions\OrderNotFoundException;
use Modules\Sales\Models\Order;

class OrderRepository extends BaseRepository
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    protected function getModelClass(): string
    {
        return Order::class;
    }

    protected function getNotFoundExceptionClass(): string
    {
        return OrderNotFoundException::class;
    }

    public function findByCode(string $code): ?Order
    {
        return $this->model->where('order_code', $code)->first();
    }

    public function findByCodeOrFail(string $code): Order
    {
        $order = $this->findByCode($code);

        if (! $order) {
            throw new OrderNotFoundException("Order with code {$code} not found");
        }

        return $order;
    }

    public function getByStatus(OrderStatus $status, int $perPage = 15)
    {
        return $this->model->ofStatus($status)->latest()->paginate($perPage);
    }

    public function getByCustomer(string $customerId, int $perPage = 15)
    {
        return $this->model->forCustomer($customerId)->latest()->paginate($perPage);
    }

    public function getPending(int $perPage = 15)
    {
        return $this->model->pending()->latest()->paginate($perPage);
    }

    public function getActive(int $perPage = 15)
    {
        return $this->model->active()->latest()->paginate($perPage);
    }

    /**
     * Filter orders with complex criteria.
     *
     * @param  array  $filters  Filter criteria
     * @param  int  $perPage  Results per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function filter(array $filters = [], int $perPage = 15)
    {
        $query = $this->model->query()
            ->with(['organization', 'customer', 'items.product', 'quotation']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (! empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (! empty($filters['from_date'])) {
            $query->where('order_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->where('order_date', '<=', $filters['to_date']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('company_name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->latest('order_date')->paginate($perPage);
    }
}
