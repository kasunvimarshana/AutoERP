<?php

namespace App\Modules\Order\Repositories;

use App\Base\BaseRepository;
use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository extends BaseRepository
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return $this->model->where('order_number', $orderNumber)->first();
    }

    public function findByCustomer(string $customerId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('customer_id', $customerId)
            ->orderBy('order_date', 'desc')
            ->get();
    }

    public function findByTenant(string $tenantId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('tenant_id', $tenantId)
            ->orderBy('order_date', 'desc')
            ->get();
    }

    public function findByBranch(string $branchId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('branch_id', $branchId)
            ->orderBy('order_date', 'desc')
            ->get();
    }

    public function getByStatus(string $status, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('status', $status)
            ->orderBy('order_date', 'desc')
            ->get();
    }

    public function getByDateRange(string $from, string $to, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->whereBetween('order_date', [$from, $to])
            ->orderBy('order_date', 'desc')
            ->get();
    }
}
