<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Domain\Contracts\RepositoryContract;

/**
 * Sales repository contract.
 */
interface SalesRepositoryContract extends RepositoryContract
{
    /**
     * Find a sales order by its order number (tenant-scoped).
     */
    public function findByOrderNumber(string $orderNumber): ?\Illuminate\Database\Eloquent\Model;

    /**
     * Find all sales orders for a given customer (tenant-scoped).
     */
    public function findByCustomer(int $customerId): Collection;

    /**
     * Return all customers (tenant-scoped).
     */
    public function allCustomers(): \Illuminate\Database\Eloquent\Collection;
}
