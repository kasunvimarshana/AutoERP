<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Contract for order management operations.
 */
interface OrderServiceInterface
{
    /**
     * Create a new order and kick off the Saga transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function createOrder(string $tenantId, string $userId, array $data): Order;

    /**
     * Cancel an order and trigger Saga compensations.
     */
    public function cancelOrder(Order $order): Order;

    /**
     * List orders for a tenant.
     *
     * @return LengthAwarePaginator<Order>
     */
    public function listForTenant(string $tenantId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Find an order by ID within a tenant scope.
     */
    public function find(string $id, string $tenantId): ?Order;
}
