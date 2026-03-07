<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

interface OrderRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get all orders scoped to a tenant, with optional filters.
     */
    public function getByTenant(string $tenantId, Request $request): Collection|LengthAwarePaginator;

    /**
     * Get orders by status within a tenant.
     */
    public function getByStatus(string $tenantId, string $status): Collection;

    /**
     * Get orders for a specific customer within a tenant.
     */
    public function getByCustomer(string $tenantId, string $customerId, Request $request): Collection|LengthAwarePaginator;

    /**
     * Find an order by its order number within a tenant.
     */
    public function findByOrderNumber(string $tenantId, string $orderNumber): ?Order;

    /**
     * Create an order together with its line items in a single transaction.
     *
     * @param  array<string, mixed>  $orderData
     * @param  array<int, array<string, mixed>>  $items
     */
    public function createWithItems(array $orderData, array $items): Order;
}
