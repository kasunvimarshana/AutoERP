<?php

namespace App\Services;

use App\Events\OrderCancelledEvent;
use App\Events\OrderCreatedEvent;
use App\Events\OrderStatusChangedEvent;
use App\Models\Order;
use App\Repositories\OrderRepository;
use App\Saga\SagaOrchestrator;
use App\Saga\Steps\CreateOrderStep;
use App\Saga\Steps\ReserveInventoryStep;
use App\Saga\Steps\ProcessPaymentStep;
use Illuminate\Database\Eloquent\Collection;

class OrderService extends BaseService
{
    public function __construct(
        protected OrderRepository $repository,
        private readonly SagaOrchestrator $sagaOrchestrator
    ) {
        parent::__construct($repository);
    }

    /**
     * Place a new order using Saga orchestration for distributed consistency.
     */
    public function placeOrder(array $data): Order
    {
        $orderNumber     = $this->repository->generateOrderNumber();
        $data['order_number'] = $orderNumber;
        $data['status']       = Order::STATUS_PENDING;
        $data['payment_status'] = Order::PAYMENT_PENDING;

        $sagaId = \Illuminate\Support\Str::uuid()->toString();
        $data['saga_id'] = $sagaId;

        $result = $this->sagaOrchestrator->run($sagaId, [
            new CreateOrderStep(),
            new ReserveInventoryStep(),
            new ProcessPaymentStep(),
        ], $data, 'place_order');

        if (!$result->isSuccessful()) {
            throw new \App\Exceptions\SagaException(
                'Order placement failed: ' . $result->getError(),
                $result->getCompensationLog()
            );
        }

        $order = $result->getData('order');

        event(new OrderCreatedEvent($order));

        return $order;
    }

    public function cancelOrder(int $orderId, string $reason = ''): Order
    {
        return $this->repository->transaction(function () use ($orderId, $reason) {
            $order = $this->repository->find($orderId);

            if (!$order) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Order not found');
            }

            if (!$order->canBeCancelled()) {
                throw new \InvalidArgumentException("Order in status '{$order->status}' cannot be cancelled.");
            }

            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'metadata' => array_merge($order->metadata ?? [], [
                    'cancellation_reason' => $reason,
                    'cancelled_at'        => now()->toIso8601String(),
                ]),
            ]);

            // Release inventory reservations
            foreach ($order->items as $item) {
                try {
                    app(InventoryService::class)->release($item->product_id, $item->quantity);
                } catch (\Throwable) {
                    // Log but don't fail the cancellation
                    \Illuminate\Support\Facades\Log::warning("Failed to release inventory for product {$item->product_id}");
                }
            }

            $order = $order->fresh('items');

            event(new OrderCancelledEvent($order, $reason));

            return $order;
        });
    }

    public function updateStatus(int $orderId, string $status): Order
    {
        $order = $this->repository->find($orderId);

        if (!$order) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Order not found');
        }

        $previousStatus = $order->status;

        $order->update(['status' => $status]);
        $order->refresh();

        if ($previousStatus !== $status) {
            event(new OrderStatusChangedEvent($order, $previousStatus, $status));
        }

        return $order;
    }

    public function getOrdersByUser(int $userId): Collection
    {
        return $this->repository->getByUser($userId);
    }

    public function findByOrderNumber(string $number): ?Order
    {
        return $this->repository->findByOrderNumber($number);
    }
}
