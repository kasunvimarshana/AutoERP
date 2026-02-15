<?php

declare(strict_types=1);

namespace App\Modules\Sales\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Sales\Models\SalesOrder;

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
     * Find sales order by order number.
     */
    public function findByOrderNumber(string $orderNumber)
    {
        return $this->model->where('order_number', $orderNumber)->first();
    }

    /**
     * Get sales orders by customer.
     */
    public function getByCustomer(int $customerId, int $perPage = 15)
    {
        return $this->model
            ->where('customer_id', $customerId)
            ->with(['items', 'customer', 'currency', 'quote'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get sales orders by status.
     */
    public function getByStatus(string $status, int $perPage = 15)
    {
        return $this->model
            ->where('status', $status)
            ->with(['items', 'customer', 'currency'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get pending fulfillment orders.
     */
    public function getPendingFulfillment(int $perPage = 15)
    {
        return $this->model
            ->where('fulfillment_status', '!=', 'fulfilled')
            ->where('status', 'approved')
            ->with(['items', 'customer', 'currency'])
            ->orderBy('expected_delivery_date', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get sales orders by quote.
     */
    public function getByQuote(int $quoteId, int $perPage = 15)
    {
        return $this->model
            ->where('quote_id', $quoteId)
            ->with(['items', 'customer', 'currency'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
