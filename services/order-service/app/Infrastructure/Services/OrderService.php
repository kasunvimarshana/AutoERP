<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Domain\Contracts\OrderRepositoryInterface;
use App\Domain\Contracts\OrderServiceInterface;
use App\Domain\Entities\SagaTransaction;
use App\Infrastructure\Messaging\MessageBrokerFactory;
use App\Infrastructure\Saga\CreateOrderSaga;

/**
 * Order Service Implementation
 *
 * Coordinates order operations using the Saga pattern for distributed transactions.
 */
class OrderService implements OrderServiceInterface
{
    public function __construct(
        protected readonly OrderRepositoryInterface $orderRepository,
        protected readonly CreateOrderSaga $createOrderSaga,
        protected readonly MessageBrokerFactory $brokerFactory
    ) {}

    public function createOrder(array $data): array
    {
        return $this->createOrderSaga->execute($data);
    }

    public function cancelOrder(int|string $orderId): array
    {
        $order = $this->orderRepository->findById($orderId, ['items']);

        if (!$order) {
            return ['success' => false, 'error' => 'Order not found.'];
        }

        if (!$order->isCancellable()) {
            return ['success' => false, 'error' => 'Order cannot be cancelled in its current state.'];
        }

        // Release all stock reservations (Saga compensation)
        foreach ($order->items as $item) {
            if ($item->reservation_id) {
                \Illuminate\Support\Facades\Http::withHeaders([
                    'X-Tenant-ID' => $order->tenant_id,
                ])
                ->timeout(10)
                ->delete(
                    config('services.inventory.url') . "/api/products/reservations/{$item->reservation_id}"
                );
            }
        }

        $this->orderRepository->updateStatus($orderId, 'cancelled');

        $this->brokerFactory->getBroker()->publish('notification.order.cancelled', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'tenant_id' => $order->tenant_id,
        ]);

        return ['success' => true, 'message' => 'Order cancelled successfully.'];
    }

    public function getOrder(int|string $id): ?array
    {
        $order = $this->orderRepository->findById($id, ['items']);
        return $order?->toArray();
    }

    public function listOrders(int|string $tenantId, array $filters = []): array
    {
        $result = $this->orderRepository->findByTenant($tenantId, $filters);

        if ($result instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            return [
                'data' => $result->items(),
                'meta' => [
                    'current_page' => $result->currentPage(),
                    'last_page' => $result->lastPage(),
                    'per_page' => $result->perPage(),
                    'total' => $result->total(),
                ],
                'paginated' => true,
            ];
        }

        return ['data' => $result->toArray(), 'paginated' => false];
    }

    public function handleSagaEvent(string $sagaId, string $event, array $payload): void
    {
        $saga = SagaTransaction::where('saga_id', $sagaId)->first();

        if (!$saga) {
            return;
        }

        $completedSteps = $saga->completed_steps ?? [];
        $completedSteps[] = $event;

        $saga->update([
            'completed_steps' => $completedSteps,
            'current_step' => $event,
        ]);
    }
}
