<?php

declare(strict_types=1);

namespace Modules\Sales\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Sales\Entities\SalesOrder;
use Modules\Sales\Repositories\Contracts\SalesOrderRepositoryInterface;

/**
 * Sales Order Repository Implementation
 *
 * Handles all sales order data access operations.
 */
class SalesOrderRepository extends BaseRepository implements SalesOrderRepositoryInterface
{
    /**
     * SalesOrderRepository constructor.
     */
    public function __construct(SalesOrder $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByOrderNumber(string $orderNumber): ?SalesOrder
    {
        return $this->model->where('order_number', $orderNumber)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrdersByCustomer(int $customerId): Collection
    {
        return $this->model->where('customer_id', $customerId)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrdersByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrdersByPaymentStatus(string $paymentStatus): Collection
    {
        return $this->model->where('payment_status', $paymentStatus)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findWithLines(int $id): ?SalesOrder
    {
        return $this->model->with('lines')->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query): Collection
    {
        return $this->model
            ->where('order_number', 'LIKE', "%{$query}%")
            ->orWhereHas('customer', function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%");
            })
            ->get();
    }
}
