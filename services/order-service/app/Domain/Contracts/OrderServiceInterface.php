<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

/**
 * Order Service Interface
 */
interface OrderServiceInterface
{
    /**
     * Create a new order using the Saga pattern for distributed transaction.
     */
    public function createOrder(array $data): array;

    /**
     * Cancel an order and compensate (rollback) all saga steps.
     */
    public function cancelOrder(int|string $orderId): array;

    /**
     * Get order details.
     */
    public function getOrder(int|string $id): ?array;

    /**
     * List orders for a tenant.
     */
    public function listOrders(int|string $tenantId, array $filters = []): array;

    /**
     * Handle saga step completion event.
     */
    public function handleSagaEvent(string $sagaId, string $event, array $payload): void;
}
