<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Contracts\Services\OrderServiceInterface;
use App\Domain\Order\Models\Order;
use Psr\Log\LoggerInterface;

/**
 * Order Service - Saga participant.
 * Handles order creation and cancellation as part of distributed transactions.
 */
class OrderService implements OrderServiceInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Create an order as a Saga participant step.
     * Compensation: cancelForSaga()
     */
    public function createForSaga(array $data): Order
    {
        $order = $this->orderRepository->create([
            'tenant_id' => $data['tenant_id'],
            'customer_id' => $data['customer_id'],
            'saga_id' => $data['saga_id'],
            'status' => Order::STATUS_PENDING,
            'total_amount' => $data['total_amount'] ?? 0,
            'currency' => $data['currency'] ?? 'USD',
            'shipping_address' => $data['shipping_address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'items' => $data['items'] ?? [],
        ]);

        $this->logger->info('Order created for Saga', [
            'order_id' => $order->id,
            'saga_id' => $data['saga_id'],
        ]);

        return $order;
    }

    /**
     * Cancel an order - Saga compensation action.
     */
    public function cancelForSaga(string $orderId, string $sagaId): bool
    {
        $order = $this->orderRepository->findById($orderId);

        if (!$order) {
            // Try to find by saga_id
            $order = $this->orderRepository->findBySagaId($sagaId);
        }

        if (!$order || !$order->canBeCancelled()) {
            $this->logger->warning('Cannot cancel order for Saga compensation', [
                'order_id' => $orderId,
                'saga_id' => $sagaId,
            ]);
            return false;
        }

        $this->orderRepository->cancel($order->id, "Saga compensation: saga_id={$sagaId}");

        $this->logger->info('Order cancelled via Saga compensation', [
            'order_id' => $order->id,
            'saga_id' => $sagaId,
        ]);

        return true;
    }

    /**
     * Confirm an order after successful payment.
     */
    public function confirm(string $orderId): Order
    {
        return $this->orderRepository->updateStatus($orderId, Order::STATUS_CONFIRMED, [
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Get orders for a tenant with conditional pagination.
     */
    public function getOrders(string $tenantId, array $params = []): mixed
    {
        return $this->orderRepository->getByTenant($tenantId, $params);
    }

    /**
     * Get a specific order.
     */
    public function getOrder(string $id): Order
    {
        $order = $this->orderRepository->findById($id);
        if (!$order) {
            throw new \RuntimeException("Order {$id} not found.");
        }
        return $order;
    }
}
