<?php

declare(strict_types=1);

namespace Modules\Sales\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\Sales\Entities\SalesOrder;

/**
 * Sales Order Repository Interface
 *
 * Defines the contract for sales order data access operations.
 */
interface SalesOrderRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find sales order by order number.
     */
    public function findByOrderNumber(string $orderNumber): ?SalesOrder;

    /**
     * Get sales orders by customer.
     */
    public function getOrdersByCustomer(int $customerId): Collection;

    /**
     * Get sales orders by status.
     */
    public function getOrdersByStatus(string $status): Collection;

    /**
     * Get sales orders by payment status.
     */
    public function getOrdersByPaymentStatus(string $paymentStatus): Collection;

    /**
     * Get sales orders with lines.
     */
    public function findWithLines(int $id): ?SalesOrder;

    /**
     * Search sales orders by order number or customer name.
     */
    public function search(string $query): Collection;
}
