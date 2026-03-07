<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\OrderCancelled;
use App\Events\OrderCompleted;
use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Models\Order;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderSagaService         $sagaService,
    ) {}

    /**
     * Return a paginated, filtered list of all orders.
     *
     * @param  array<string, mixed> $filters
     */
    public function getAllOrders(array $filters): LengthAwarePaginator
    {
        return $this->orderRepository->getAll($filters);
    }

    /**
     * Return a paginated list of orders belonging to a specific customer.
     *
     * @param  array<string, mixed> $filters
     */
    public function getOrdersByCustomer(string $customerId, array $filters = []): LengthAwarePaginator
    {
        return $this->orderRepository->getByCustomerId($customerId, $filters);
    }

    /**
     * Find an order by ID (with items).
     */
    public function getOrderById(int $id): ?Order
    {
        return $this->orderRepository->findById($id, withItems: true);
    }

    /**
     * Create a new order and run the Saga to reserve inventory.
     *
     * @param  array<string, mixed> $data
     * @throws Throwable
     */
    public function createOrder(array $data): Order
    {
        return $this->sagaService->createOrderSaga($data);
    }

    /**
     * Update non-status order metadata (notes, addresses) while order is still pending.
     *
     * @param  array<string, mixed> $data
     * @throws RuntimeException if the order is not found or in a non-editable state
     * @throws Throwable
     */
    public function updateOrder(int $id, array $data): Order
    {
        return DB::transaction(function () use ($id, $data): Order {
            $order = $this->orderRepository->lockForUpdate($id);

            if ($order === null) {
                throw new RuntimeException("Order {$id} not found.");
            }

            if (! in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_CONFIRMED], true)) {
                throw new RuntimeException(
                    "Order {$id} cannot be updated in status '{$order->status}'."
                );
            }

            // Only allow updating safe metadata fields
            $allowed = array_intersect_key($data, array_flip([
                'customer_name',
                'customer_email',
                'shipping_address',
                'billing_address',
                'notes',
            ]));

            $updated = $this->orderRepository->update($id, $allowed);

            Log::info('Order metadata updated', ['order_id' => $id]);

            event(new OrderUpdated($updated));

            return $updated;
        });
    }

    /**
     * Confirm an order (admin / system action after successful saga).
     *
     * @throws RuntimeException
     * @throws Throwable
     */
    public function confirmOrder(int $id): Order
    {
        return DB::transaction(function () use ($id): Order {
            $order = $this->orderRepository->lockForUpdate($id);

            if ($order === null) {
                throw new RuntimeException("Order {$id} not found.");
            }

            if (! $order->is_confirmable) {
                throw new RuntimeException(
                    "Order {$id} cannot be confirmed. Status: {$order->status}, Saga: {$order->saga_status}."
                );
            }

            $updated = $this->orderRepository->update($id, [
                'status'       => Order::STATUS_CONFIRMED,
                'confirmed_at' => now(),
            ]);

            Log::info('Order confirmed', ['order_id' => $id]);

            event(new OrderUpdated($updated));

            return $updated;
        });
    }

    /**
     * Cancel an order and run the Saga to release reserved inventory.
     *
     * @throws Throwable
     */
    public function cancelOrder(int $id, string $performedBy = 'customer'): Order
    {
        return $this->sagaService->cancelOrderSaga($id, $performedBy);
    }

    /**
     * Mark an order as shipped.
     *
     * @throws RuntimeException
     * @throws Throwable
     */
    public function shipOrder(int $id): Order
    {
        return DB::transaction(function () use ($id): Order {
            $order = $this->orderRepository->lockForUpdate($id);

            if ($order === null) {
                throw new RuntimeException("Order {$id} not found.");
            }

            if (! $order->is_shippable) {
                throw new RuntimeException(
                    "Order {$id} cannot be shipped in status '{$order->status}'."
                );
            }

            $updated = $this->orderRepository->update($id, [
                'status'    => Order::STATUS_SHIPPED,
                'shipped_at' => now(),
            ]);

            Log::info('Order shipped', ['order_id' => $id]);

            event(new OrderUpdated($updated));

            return $updated;
        });
    }

    /**
     * Mark an order as delivered.
     *
     * @throws RuntimeException
     * @throws Throwable
     */
    public function deliverOrder(int $id): Order
    {
        return DB::transaction(function () use ($id): Order {
            $order = $this->orderRepository->lockForUpdate($id);

            if ($order === null) {
                throw new RuntimeException("Order {$id} not found.");
            }

            if (! $order->is_deliverable) {
                throw new RuntimeException(
                    "Order {$id} cannot be delivered in status '{$order->status}'."
                );
            }

            $updated = $this->orderRepository->update($id, [
                'status'       => Order::STATUS_DELIVERED,
                'delivered_at' => now(),
            ]);

            Log::info('Order delivered', ['order_id' => $id]);

            event(new OrderCompleted($updated));

            return $updated;
        });
    }

    /**
     * Soft-delete an order (admin only, order must be cancelled first).
     *
     * @throws RuntimeException
     */
    public function deleteOrder(int $id): bool
    {
        $order = $this->orderRepository->findById($id);

        if ($order === null) {
            return false;
        }

        if ($order->status !== Order::STATUS_CANCELLED) {
            throw new RuntimeException(
                "Order {$id} must be cancelled before it can be deleted."
            );
        }

        return $this->orderRepository->delete($id);
    }
}
