<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Models\Order;
use App\Models\OrderItem;
use App\Repositories\Interfaces\OrderItemRepositoryInterface;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Implements the Saga pattern for distributed order transactions.
 *
 * Expected inventory-service HTTP contract
 * ─────────────────────────────────────────
 * POST /api/v1/inventory/product/{productId}/reserve
 *   Body: { quantity, reference_type, reference_id, performed_by }
 *   Success: 200 { message: "Reserved" }
 *   Insufficient stock: 422 { message: "..." }
 *
 * POST /api/v1/inventory/product/{productId}/release
 *   Body: { quantity, reference_type, reference_id, performed_by }
 *   Success: 200 { message: "Released" }
 *
 * Note: the inventory-service exposes reserveStock() / releaseStock()
 * via its InventoryService; those methods must be wired to HTTP endpoints
 * (e.g. in inventory-service routes/api.php) before this saga runs in production.
 */
class OrderSagaService
{
    public function __construct(
        private readonly OrderRepositoryInterface     $orderRepository,
        private readonly OrderItemRepositoryInterface $orderItemRepository,
    ) {}

    // ── Create Order Saga ─────────────────────────────────────────────────────

    /**
     * Orchestrated saga to create an order and reserve inventory.
     *
     * Step 1 – Create the order record (local DB transaction).
     * Step 2 – Call inventory-service to reserve stock for each item.
     * Step 3 – Confirm the order if all reservations succeed.
     *
     * If step 2 fails the saga compensates by cancelling the order and
     * releasing any partially-reserved inventory.
     *
     * @param  array<string, mixed> $data
     * @throws Throwable
     */
    public function createOrderSaga(array $data): Order
    {
        $sagaId = uniqid('saga_order_create_', true);

        Log::info('Order creation saga started', ['saga_id' => $sagaId]);

        // ── Step 1: Persist the order ────────────────────────────────────────

        $order = $this->step1CreateOrder($data, $sagaId);

        // ── Step 2: Reserve inventory ────────────────────────────────────────

        try {
            $reservations = $this->step2ReserveInventory($order, $sagaId);
        } catch (Throwable $e) {
            Log::error('Order saga step 2 failed – compensating', [
                'saga_id'  => $sagaId,
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            $this->compensateOrder($order, [], $e->getMessage());

            throw new RuntimeException(
                "Order could not be placed: {$e->getMessage()}",
                previous: $e
            );
        }

        // ── Step 3: Confirm the order ────────────────────────────────────────

        $order = $this->step3ConfirmOrder($order, $reservations, $sagaId);

        Log::info('Order creation saga completed', [
            'saga_id'  => $sagaId,
            'order_id' => $order->id,
        ]);

        event(new OrderCreated($order));

        return $order;
    }

    // ── Cancel Order Saga ─────────────────────────────────────────────────────

    /**
     * Orchestrated saga to cancel an order and release reserved inventory.
     *
     * Step 1 – Call inventory-service to release reserved stock.
     * Step 2 – Mark the order as cancelled in the local DB.
     *
     * If step 1 fails the release is logged for manual retry and the order
     * is still cancelled locally (inventory service may reconcile later via events).
     *
     * @throws RuntimeException if the order is not found or not cancellable
     * @throws Throwable
     */
    public function cancelOrderSaga(int $orderId, string $performedBy = 'customer'): Order
    {
        $sagaId = uniqid('saga_order_cancel_', true);

        Log::info('Order cancellation saga started', [
            'saga_id'      => $sagaId,
            'order_id'     => $orderId,
            'performed_by' => $performedBy,
        ]);

        $order = DB::transaction(function () use ($orderId): Order {
            $order = $this->orderRepository->lockForUpdate($orderId);

            if ($order === null) {
                throw new RuntimeException("Order {$orderId} not found.");
            }

            if (! $order->is_cancellable) {
                throw new RuntimeException(
                    "Order {$orderId} cannot be cancelled in status '{$order->status}'."
                );
            }

            return $this->orderRepository->update($orderId, [
                'saga_status' => Order::SAGA_COMPENSATING,
            ]);
        });

        // Attempt to release inventory; on failure log but still cancel the order
        $compensationData = $order->saga_compensation_data ?? [];

        try {
            $this->releaseInventoryReservations($order, $compensationData, $sagaId);
        } catch (Throwable $e) {
            Log::critical('Failed to release inventory reservations during order cancellation', [
                'saga_id'           => $sagaId,
                'order_id'          => $orderId,
                'compensation_data' => $compensationData,
                'error'             => $e->getMessage(),
            ]);
        }

        $order = DB::transaction(function () use ($orderId): Order {
            return $this->orderRepository->update($orderId, [
                'status'       => Order::STATUS_CANCELLED,
                'saga_status'  => Order::SAGA_COMPENSATED,
                'cancelled_at' => now(),
            ]);
        });

        Log::info('Order cancellation saga completed', [
            'saga_id'  => $sagaId,
            'order_id' => $orderId,
        ]);

        event(new OrderCancelled($order));

        return $order;
    }

    // ── Compensating Transaction ──────────────────────────────────────────────

    /**
     * Compensate a failed order by marking it cancelled and releasing any
     * inventory that was partially reserved before the saga failed.
     *
     * @param  array<int, array<string, mixed>> $reservations  Already-confirmed reservations to undo
     */
    public function compensateOrder(Order $order, array $reservations, string $reason = ''): void
    {
        Log::warning('Running order saga compensation', [
            'order_id'     => $order->id,
            'order_number' => $order->order_number,
            'reason'       => $reason,
        ]);

        // Mark the order as compensating so idempotency checks work
        $this->orderRepository->update($order->id, [
            'saga_status' => Order::SAGA_COMPENSATING,
        ]);

        if (! empty($reservations)) {
            $sagaId = uniqid('saga_compensate_', true);
            $this->releaseInventoryReservations($order, $reservations, $sagaId);
        }

        $this->orderRepository->update($order->id, [
            'status'       => Order::STATUS_CANCELLED,
            'saga_status'  => Order::SAGA_COMPENSATED,
            'cancelled_at' => now(),
        ]);

        Log::info('Order saga compensation complete', ['order_id' => $order->id]);
    }

    // ── Private steps ─────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed> $data
     */
    private function step1CreateOrder(array $data, string $sagaId): Order
    {
        return DB::transaction(function () use ($data, $sagaId): Order {
            $items = $data['items'] ?? [];
            unset($data['items']);

            // Compute amounts from the items array
            $subtotal = array_reduce($items, function (float $carry, array $item): float {
                return $carry + ((float) $item['unit_price'] * (int) $item['quantity']);
            }, 0.0);

            $taxAmount      = (float) ($data['tax_amount']      ?? 0.0);
            $discountAmount = (float) ($data['discount_amount'] ?? 0.0);
            $totalAmount    = $subtotal + $taxAmount - $discountAmount;

            $order = $this->orderRepository->create(array_merge($data, [
                'total_amount'    => round($totalAmount, 2),
                'tax_amount'      => round($taxAmount, 2),
                'discount_amount' => round($discountAmount, 2),
                'status'          => Order::STATUS_PENDING,
                'saga_status'     => Order::SAGA_STARTED,
                'placed_at'       => now(),
            ]));

            $this->orderItemRepository->createMany($order->id, $items);

            Log::info('Saga step 1 complete – order created', [
                'saga_id'  => $sagaId,
                'order_id' => $order->id,
            ]);

            return $order->fresh(['items']);
        });
    }

    /**
     * Call the inventory-service to reserve stock for every item in the order.
     *
     * Returns the list of successful reservation payloads (used for compensation).
     *
     * @param  Order $order
     * @return array<int, array<string, mixed>>
     * @throws RuntimeException on inventory failure
     */
    private function step2ReserveInventory(Order $order, string $sagaId): array
    {
        $inventoryUrl  = rtrim(config('services.inventory.url'), '/');
        $timeout       = (int) config('services.inventory.timeout', 5);
        $retryTimes    = (int) config('services.inventory.retry_times', 3);
        $retrySleep    = (int) config('services.inventory.retry_sleep', 500);

        $reservations = [];

        foreach ($order->items as $item) {
            $reserved = $this->reserveItemWithRetry(
                inventoryUrl: $inventoryUrl,
                timeout:      $timeout,
                retryTimes:   $retryTimes,
                retrySleep:   $retrySleep,
                item:         $item,
                order:        $order,
                sagaId:       $sagaId,
            );

            $reservations[] = $reserved;
        }

        // Persist compensation data and advance saga status
        $this->orderRepository->update($order->id, [
            'saga_status'            => Order::SAGA_INVENTORY_RESERVED,
            'saga_compensation_data' => $reservations,
        ]);

        Log::info('Saga step 2 complete – inventory reserved', [
            'saga_id'      => $sagaId,
            'order_id'     => $order->id,
            'reservations' => $reservations,
        ]);

        return $reservations;
    }

    /**
     * Attempt to reserve stock for one item with exponential-like retry.
     *
     * @return array<string, mixed>
     * @throws RuntimeException
     */
    private function reserveItemWithRetry(
        string $inventoryUrl,
        int $timeout,
        int $retryTimes,
        int $retrySleep,
        OrderItem $item,
        Order $order,
        string $sagaId,
    ): array {
        $attempt = 0;
        $lastError = '';

        while ($attempt < $retryTimes) {
            $attempt++;

            try {
                $response = Http::timeout($timeout)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->post("{$inventoryUrl}/api/v1/inventory/product/{$item->product_id}/reserve", [
                        'quantity'       => $item->quantity,
                        'reference_type' => 'order',
                        'reference_id'   => (string) $order->id,
                        'performed_by'   => "order-service:{$sagaId}",
                    ]);

                if ($response->successful()) {
                    Log::info('Inventory reserved for item', [
                        'saga_id'    => $sagaId,
                        'order_id'   => $order->id,
                        'product_id' => $item->product_id,
                        'quantity'   => $item->quantity,
                        'attempt'    => $attempt,
                    ]);

                    return [
                        'product_id'   => $item->product_id,
                        'order_item_id' => $item->id,
                        'quantity'     => $item->quantity,
                        'response'     => $response->json(),
                    ];
                }

                $lastError = $response->json('message', $response->body());

                // 422 = business rule failure (insufficient stock) – no point retrying
                if ($response->status() === 422) {
                    throw new RuntimeException(
                        "Insufficient stock for product {$item->product_id}: {$lastError}"
                    );
                }

                Log::warning('Inventory reservation attempt failed', [
                    'saga_id'    => $sagaId,
                    'order_id'   => $order->id,
                    'product_id' => $item->product_id,
                    'attempt'    => $attempt,
                    'status'     => $response->status(),
                    'error'      => $lastError,
                ]);
            } catch (RuntimeException $e) {
                throw $e; // Re-throw business-logic errors immediately
            } catch (Throwable $e) {
                $lastError = $e->getMessage();

                Log::warning('Inventory HTTP call threw an exception', [
                    'saga_id'  => $sagaId,
                    'order_id' => $order->id,
                    'attempt'  => $attempt,
                    'error'    => $lastError,
                ]);
            }

            if ($attempt < $retryTimes) {
                // Progressive sleep: 500ms, 1000ms, 1500ms …
                usleep($retrySleep * $attempt * 1000);
            }
        }

        throw new RuntimeException(
            "Failed to reserve inventory for product {$item->product_id} after {$retryTimes} attempts: {$lastError}"
        );
    }

    /**
     * @param  array<int, array<string, mixed>> $reservations
     */
    private function step3ConfirmOrder(Order $order, array $reservations, string $sagaId): Order
    {
        return DB::transaction(function () use ($order, $sagaId): Order {
            $updated = $this->orderRepository->update($order->id, [
                'status'       => Order::STATUS_CONFIRMED,
                'saga_status'  => Order::SAGA_COMPLETED,
                'confirmed_at' => now(),
            ]);

            // Mark all items as confirmed
            foreach ($order->items as $item) {
                $item->update(['status' => OrderItem::STATUS_CONFIRMED]);
            }

            Log::info('Saga step 3 complete – order confirmed', [
                'saga_id'  => $sagaId,
                'order_id' => $order->id,
            ]);

            return $updated->fresh(['items']);
        });
    }

    /**
     * Release inventory reservations stored as compensation data.
     *
     * @param  array<int, array<string, mixed>> $compensationData
     */
    private function releaseInventoryReservations(Order $order, array $compensationData, string $sagaId): void
    {
        if (empty($compensationData)) {
            return;
        }

        $inventoryUrl = rtrim(config('services.inventory.url'), '/');
        $timeout      = (int) config('services.inventory.timeout', 5);

        foreach ($compensationData as $reservation) {
            $productId = $reservation['product_id'] ?? null;
            $quantity  = $reservation['quantity']   ?? 0;

            if ($productId === null || $quantity <= 0) {
                continue;
            }

            try {
                $response = Http::timeout($timeout)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->post("{$inventoryUrl}/api/v1/inventory/product/{$productId}/release", [
                        'quantity'       => $quantity,
                        'reference_type' => 'order',
                        'reference_id'   => (string) $order->id,
                        'performed_by'   => "order-service:{$sagaId}",
                    ]);

                if ($response->failed()) {
                    Log::critical('Inventory release HTTP call failed – manual intervention required', [
                        'saga_id'    => $sagaId,
                        'order_id'   => $order->id,
                        'product_id' => $productId,
                        'quantity'   => $quantity,
                        'status'     => $response->status(),
                        'body'       => $response->body(),
                    ]);
                } else {
                    Log::info('Inventory released for product', [
                        'saga_id'    => $sagaId,
                        'order_id'   => $order->id,
                        'product_id' => $productId,
                        'quantity'   => $quantity,
                    ]);
                }
            } catch (Throwable $e) {
                Log::critical('Exception during inventory release – manual intervention required', [
                    'saga_id'    => $sagaId,
                    'order_id'   => $order->id,
                    'product_id' => $productId,
                    'quantity'   => $quantity,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }
}
