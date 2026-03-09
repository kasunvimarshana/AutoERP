<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Domain\Order\Models\Order;

interface OrderServiceInterface
{
    /**
     * Create an order as part of a Saga transaction.
     */
    public function createForSaga(array $data): Order;

    /**
     * Cancel an order (Saga compensation).
     */
    public function cancelForSaga(string $orderId, string $sagaId): bool;

    /**
     * Confirm an order (after successful payment).
     */
    public function confirm(string $orderId): Order;

    /**
     * Get orders for a tenant.
     */
    public function getOrders(string $tenantId, array $params = []): mixed;

    /**
     * Get a specific order.
     */
    public function getOrder(string $id): Order;
}
