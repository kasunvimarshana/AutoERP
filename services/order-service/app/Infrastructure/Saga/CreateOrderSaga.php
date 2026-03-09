<?php

declare(strict_types=1);

namespace App\Infrastructure\Saga;

use App\Domain\Entities\OrderItem;
use App\Domain\Entities\SagaTransaction;
use App\Infrastructure\Messaging\MessageBrokerFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Create Order Saga
 *
 * Orchestrates the distributed "create order" transaction across services.
 * Implements the Saga pattern with compensating transactions for rollback.
 *
 * Steps:
 *   1. Create order record (local)
 *   2. Reserve stock in Inventory Service
 *   3. Process payment (stub)
 *   4. Send confirmation notification
 *   5. Confirm stock deduction
 *
 * Each step has a corresponding compensation (rollback) action.
 */
class CreateOrderSaga
{
    private string $sagaId;
    private array $context = [];
    private array $completedSteps = [];

    private const STEPS = [
        'create_order',
        'reserve_stock',
        'process_payment',
        'send_notification',
        'confirm_stock',
    ];

    public function __construct(
        protected readonly MessageBrokerFactory $brokerFactory
    ) {}

    /**
     * Execute the Create Order Saga.
     *
     * @param array $payload  Order data including items
     * @return array  Result with order data or error
     */
    public function execute(array $payload): array
    {
        $this->sagaId = Str::uuid()->toString();
        $this->context = $payload;

        // Record saga start
        $saga = SagaTransaction::create([
            'saga_id' => $this->sagaId,
            'saga_type' => 'create_order',
            'status' => 'started',
            'current_step' => 'create_order',
            'completed_steps' => [],
            'context' => $payload,
        ]);

        try {
            // Step 1: Create order record
            $order = $this->stepCreateOrder($payload);
            $this->context['order_id'] = $order->id;
            $this->context['order_number'] = $order->order_number;
            $this->completedSteps[] = 'create_order';

            // Step 2: Reserve stock for all items
            $reservations = $this->stepReserveStock($order, $payload['items']);
            $this->context['reservations'] = $reservations;
            $this->completedSteps[] = 'reserve_stock';

            // Step 3: Process payment (stub - would call payment service)
            $this->stepProcessPayment($payload);
            $this->completedSteps[] = 'process_payment';

            // Step 4: Send notification
            $this->stepSendNotification($order, $payload);
            $this->completedSteps[] = 'send_notification';

            // Step 5: Confirm stock deduction
            $this->stepConfirmStock($reservations);
            $this->completedSteps[] = 'confirm_stock';

            // Update order status to confirmed
            $order->update(['status' => 'confirmed', 'saga_id' => $this->sagaId]);

            // Mark saga as completed
            $saga->update([
                'status' => 'completed',
                'current_step' => 'done',
                'completed_steps' => $this->completedSteps,
                'context' => $this->context,
            ]);

            $this->publishEvent('saga.create_order.completed', [
                'saga_id' => $this->sagaId,
                'order_id' => $order->id,
            ]);

            return [
                'success' => true,
                'saga_id' => $this->sagaId,
                'order' => $order->fresh(['items'])->toArray(),
            ];

        } catch (\Exception $e) {
            Log::error("Saga {$this->sagaId} failed at step", [
                'error' => $e->getMessage(),
                'completed_steps' => $this->completedSteps,
            ]);

            $saga->update([
                'status' => 'compensating',
                'failed_step' => end($this->completedSteps) ?: 'unknown',
                'error_message' => $e->getMessage(),
                'completed_steps' => $this->completedSteps,
            ]);

            // Execute compensation (rollback)
            $this->compensate($e);

            $saga->update(['status' => 'compensated']);

            return [
                'success' => false,
                'saga_id' => $this->sagaId,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get the current saga ID.
     */
    public function getSagaId(): string
    {
        return $this->sagaId;
    }

    // -------------------------------------------------------------------------
    // Saga Steps
    // -------------------------------------------------------------------------

    private function stepCreateOrder(array $payload): \App\Domain\Entities\Order
    {
        $orderNumber = 'ORD-' . strtoupper(Str::random(10));

        $order = \App\Domain\Entities\Order::create([
            'tenant_id' => $payload['tenant_id'],
            'user_id' => $payload['user_id'],
            'order_number' => $orderNumber,
            'status' => 'pending',
            'total_amount' => $this->calculateTotal($payload['items']),
            'currency' => $payload['currency'] ?? 'USD',
            'shipping_address' => $payload['shipping_address'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ]);

        foreach ($payload['items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'product_sku' => $item['sku'] ?? '',
                'product_name' => $item['name'] ?? '',
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['quantity'] * $item['unit_price'],
            ]);
        }

        return $order;
    }

    private function stepReserveStock(\App\Domain\Entities\Order $order, array $items): array
    {
        $reservations = [];

        foreach ($items as $item) {
            $response = Http::withHeaders([
                'X-Tenant-ID' => $this->context['tenant_id'],
                'X-Saga-ID' => $this->sagaId,
            ])
            ->timeout(10)
            ->post(
                config('services.inventory.url') . "/api/products/{$item['product_id']}/reserve-stock",
                [
                    'quantity' => $item['quantity'],
                    'order_id' => (string) $order->id,
                ]
            );

            if (!$response->successful()) {
                throw new \RuntimeException(
                    "Failed to reserve stock for product {$item['product_id']}: " .
                    ($response->json('message') ?? 'Unknown error')
                );
            }

            $reservationId = $response->json('data.reservation_id');
            $reservations[$item['product_id']] = $reservationId;

            // Update order item with reservation ID
            OrderItem::where('order_id', $order->id)
                ->where('product_id', $item['product_id'])
                ->update(['reservation_id' => $reservationId]);
        }

        return $reservations;
    }

    private function stepProcessPayment(array $payload): void
    {
        // Stub: In production, call payment service
        // If payment fails, throw exception to trigger compensation
        Log::info("Payment processed for saga {$this->sagaId}", [
            'amount' => $payload['total_amount'] ?? 0,
        ]);
    }

    private function stepSendNotification(\App\Domain\Entities\Order $order, array $payload): void
    {
        $this->publishEvent('notification.order.created', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'tenant_id' => $payload['tenant_id'],
            'user_id' => $payload['user_id'],
            'total_amount' => $order->total_amount,
        ]);
    }

    private function stepConfirmStock(array $reservations): void
    {
        foreach ($reservations as $productId => $reservationId) {
            Http::withHeaders(['X-Saga-ID' => $this->sagaId])
                ->timeout(10)
                ->post(
                    config('services.inventory.url') . "/api/products/confirm-stock",
                    ['reservation_id' => $reservationId]
                );
        }
    }

    // -------------------------------------------------------------------------
    // Compensation (Rollback) Actions
    // -------------------------------------------------------------------------

    private function compensate(\Exception $cause): void
    {
        Log::info("Starting compensation for saga {$this->sagaId}", [
            'steps_to_compensate' => array_reverse($this->completedSteps),
        ]);

        // Compensate in reverse order
        foreach (array_reverse($this->completedSteps) as $step) {
            try {
                $this->compensateStep($step);
            } catch (\Exception $e) {
                Log::error("Compensation failed for step {$step} in saga {$this->sagaId}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function compensateStep(string $step): void
    {
        match ($step) {
            'create_order' => $this->compensateCreateOrder(),
            'reserve_stock' => $this->compensateReserveStock(),
            'process_payment' => $this->compensateProcessPayment(),
            'send_notification' => $this->compensateSendNotification(),
            'confirm_stock' => null, // Cannot undo confirmed deduction; handled by refund
            default => null,
        };
    }

    private function compensateCreateOrder(): void
    {
        if (isset($this->context['order_id'])) {
            \App\Domain\Entities\Order::where('id', $this->context['order_id'])
                ->update(['status' => 'failed']);
        }
    }

    private function compensateReserveStock(): void
    {
        if (!isset($this->context['reservations'])) {
            return;
        }

        foreach ($this->context['reservations'] as $productId => $reservationId) {
            Http::withHeaders(['X-Saga-ID' => $this->sagaId])
                ->timeout(10)
                ->delete(
                    config('services.inventory.url') . "/api/products/reservations/{$reservationId}"
                );
        }

        $this->publishEvent('inventory.stock.compensation', [
            'saga_id' => $this->sagaId,
            'reservations' => $this->context['reservations'],
        ]);
    }

    private function compensateProcessPayment(): void
    {
        // Stub: Call payment service refund endpoint
        Log::info("Payment refunded for saga {$this->sagaId}");
    }

    private function compensateSendNotification(): void
    {
        $this->publishEvent('notification.order.cancelled', [
            'saga_id' => $this->sagaId,
            'order_id' => $this->context['order_id'] ?? null,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function calculateTotal(array $items): float
    {
        return array_sum(
            array_map(fn ($item) => $item['quantity'] * $item['unit_price'], $items)
        );
    }

    private function publishEvent(string $topic, array $payload): void
    {
        $this->brokerFactory->getBroker()->publish($topic, array_merge($payload, [
            'saga_id' => $this->sagaId,
            'timestamp' => now()->toIso8601String(),
        ]));
    }
}
