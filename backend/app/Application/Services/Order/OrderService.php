<?php

declare(strict_types=1);

namespace App\Application\Services\Order;

use App\Application\DTOs\OrderDTO;
use App\Application\Pipelines\OrderProcessingPipeline;
use App\Application\Saga\Orchestrator\SagaOrchestrator;
use App\Application\Saga\Steps\ReserveStockStep;
use App\Application\Saga\Steps\ProcessPaymentStep;
use App\Application\Saga\Steps\CreateOrderStep;
use App\Domain\Inventory\Contracts\InventoryRepositoryInterface;
use App\Domain\Order\Contracts\OrderRepositoryInterface;
use App\Domain\Order\Events\OrderCancelled;
use App\Domain\Order\Events\OrderCompleted;
use App\Infrastructure\MessageBroker\Contracts\MessageBrokerInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Application service for Order management.
 *
 * Uses the Saga pattern to coordinate stock reservation, payment processing,
 * and order creation as a distributed transaction with compensation on failure.
 */
final class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly InventoryRepositoryInterface $inventoryRepository,
        private readonly MessageBrokerInterface $messageBroker,
        private readonly OrderProcessingPipeline $pipeline,
    ) {}

    public function list(array $filters = []): mixed
    {
        return $this->orderRepository->all($filters);
    }

    public function get(int|string $id): Model
    {
        return $this->orderRepository->findOrFail($id);
    }

    /**
     * Place a new order using the Saga orchestrator.
     *
     * @throws \Throwable On any saga step failure (after compensation).
     */
    public function placeOrder(OrderDTO $dto, int|string $tenantId): array
    {
        /** @var OrderDTO $dto */
        $dto = $this->pipeline->process($dto);

        $context = array_merge($dto->toArray(), [
            'tenant_id' => $tenantId,
            'saga_type' => 'place_order',
        ]);

        $orchestrator = new SagaOrchestrator([
            new ReserveStockStep($this->inventoryRepository),
            new ProcessPaymentStep(),
            new CreateOrderStep($this->orderRepository),
        ]);

        $result = $orchestrator->run($context);

        $this->messageBroker->publish('order.placed', [
            'order_id'     => $result['order_id'],
            'order_number' => $result['order_number'],
            'tenant_id'    => $tenantId,
            'customer_id'  => $dto->customerId,
        ]);

        Log::info("[OrderService] Order #{$result['order_number']} placed via saga {$orchestrator->getSagaId()}.");

        return [
            'order_id'     => $result['order_id'],
            'order_number' => $result['order_number'],
            'saga_id'      => $orchestrator->getSagaId(),
        ];
    }

    /**
     * Cancel an order and release reserved stock.
     */
    public function cancel(int|string $orderId, string $reason): Model
    {
        return DB::transaction(function () use ($orderId, $reason): Model {
            $order = $this->orderRepository->updateStatus($orderId, 'cancelled', [
                'cancellation_reason' => $reason,
            ]);

            // Release reserved stock for each item.
            foreach ($order->items as $item) {
                $this->inventoryRepository->adjustStock($item->product_id, $item->quantity);
            }

            event(new OrderCancelled($order, $reason, $order->tenant_id, Auth::id()));

            $this->messageBroker->publish('order.cancelled', [
                'order_id'  => $order->id,
                'tenant_id' => $order->tenant_id,
                'reason'    => $reason,
            ]);

            return $order;
        });
    }

    /**
     * Mark an order as completed.
     */
    public function complete(int|string $orderId): Model
    {
        return DB::transaction(function () use ($orderId): Model {
            $order = $this->orderRepository->updateStatus($orderId, 'completed');

            event(new OrderCompleted($order, $order->tenant_id, Auth::id()));

            $this->messageBroker->publish('order.completed', [
                'order_id'  => $order->id,
                'tenant_id' => $order->tenant_id,
            ]);

            return $order;
        });
    }

    /**
     * Update order status with validation.
     */
    public function updateStatus(int|string $orderId, string $status, array $metadata = []): Model
    {
        return DB::transaction(
            fn () => $this->orderRepository->updateStatus($orderId, $status, $metadata)
        );
    }

    /**
     * Return a summary of order counts by status.
     */
    public function statusSummary(): Collection
    {
        return $this->orderRepository->statusSummary();
    }
}
