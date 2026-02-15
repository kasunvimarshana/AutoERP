<?php

declare(strict_types=1);

namespace App\Modules\Sales\Repositories;

use App\Core\Repositories\BaseRepositoryInterface;

interface SalesOrderRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find sales order by order number.
     */
    public function findByOrderNumber(string $orderNumber);

    /**
     * Get sales orders by customer.
     */
    public function getByCustomer(int $customerId, int $perPage = 15);

    /**
     * Get sales orders by status.
     */
    public function getByStatus(string $status, int $perPage = 15);

    /**
     * Get pending fulfillment orders.
     */
    public function getPendingFulfillment(int $perPage = 15);

    /**
     * Get sales orders by quote.
     */
    public function getByQuote(int $quoteId, int $perPage = 15);
}
