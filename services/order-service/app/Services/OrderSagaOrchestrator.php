<?php

namespace App\Services;

use App\Models\Order;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Choreography-based Saga Orchestrator for Order Processing.
 *
 * Saga Steps:
 *   1. Create Order (local)         → publish OrderCreated
 *   2. Reserve Inventory            → on success: publish InventoryReserved
 *   3. Process Payment              → on success: publish PaymentProcessed
 *   4. Confirm Order                → publish OrderConfirmed → send Notification
 *
 * Compensation (rollback) on failure:
 *   - Payment failed   → release inventory reservation → cancel order
 *   - Inventory failed → cancel order (no payment taken yet)
 */
class OrderSagaOrchestrator
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client(['timeout' => 10.0]);
    }

    /**
     * Step 1 – Create order locally and kick off the Saga.
     */
    public function startOrderSaga(array $data): Order
    {
        $order = Order::create([
            ...$data,
            'status'      => Order::STATUS_PENDING,
            'saga_status' => Order::SAGA_STARTED,
        ]);

        Log::info('[Saga] Started', ['order_id' => $order->id, 'saga_id' => $order->saga_id]);

        // Step 2 – Reserve inventory
        $this->reserveInventory($order);

        return $order->fresh();
    }

    /**
     * Step 2 – Call Inventory Service to reserve items.
     */
    private function reserveInventory(Order $order): void
    {
        try {
            $order->update(['status' => Order::STATUS_INVENTORY_RESERVING]);

            $this->http->post(
                rtrim(config('services.inventory.url'), '/') . '/api/inventory/reserve',
                [
                    'json' => [
                        'order_id'  => $order->id,
                        'saga_id'   => $order->saga_id,
                        'tenant_id' => $order->tenant_id,
                        'items'     => $order->items,
                    ],
                    'headers' => ['Accept' => 'application/json'],
                ]
            );

            Log::info('[Saga] Inventory reserved', ['order_id' => $order->id]);
            $order->update(['status' => Order::STATUS_INVENTORY_RESERVED]);

            // Step 3 – Process payment
            $this->processPayment($order);
        } catch (GuzzleException $e) {
            Log::error('[Saga] Inventory reservation failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
            $this->failOrder($order, 'inventory_reservation_failed');
        }
    }

    /**
     * Step 3 – Call Payment Service to charge the customer.
     */
    private function processPayment(Order $order): void
    {
        $order->update(['status' => Order::STATUS_PAYMENT_PROCESSING]);

        try {
            $response = $this->http->post(
                rtrim(config('services.payment.url'), '/') . '/api/payments/charge',
                [
                    'json' => [
                        'order_id'    => $order->id,
                        'saga_id'     => $order->saga_id,
                        'tenant_id'   => $order->tenant_id,
                        'customer_id' => $order->customer_id,
                        'amount'      => $order->total_amount,
                        'currency'    => $order->currency,
                    ],
                    'headers' => ['Accept' => 'application/json'],
                ]
            );

            $result    = json_decode($response->getBody()->getContents(), true);
            $paymentId = $result['payment_id'] ?? null;

            Log::info('[Saga] Payment processed', [
                'order_id'   => $order->id,
                'payment_id' => $paymentId,
            ]);

            $order->update([
                'status'      => Order::STATUS_CONFIRMED,
                'saga_status' => Order::SAGA_COMPLETED,
                'payment_id'  => $paymentId,
            ]);

            // Step 4 – Notify customer of success
            $this->sendNotification($order, 'order_confirmed');
        } catch (GuzzleException $e) {
            Log::error('[Saga] Payment failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            // Compensation: release the inventory reservation
            $this->compensateInventory($order, 'payment_failed');
        }
    }

    /**
     * Compensation Step A – Release inventory reservation.
     */
    private function compensateInventory(Order $order, string $reason): void
    {
        $order->update([
            'status'      => Order::STATUS_COMPENSATING,
            'saga_status' => Order::SAGA_FAILED,
        ]);

        try {
            $this->http->post(
                rtrim(config('services.inventory.url'), '/') . '/api/inventory/release',
                [
                    'json' => [
                        'order_id'  => $order->id,
                        'saga_id'   => $order->saga_id,
                        'tenant_id' => $order->tenant_id,
                        'reason'    => $reason,
                    ],
                    'headers' => ['Accept' => 'application/json'],
                ]
            );
            Log::info('[Saga][Compensation] Inventory released', ['order_id' => $order->id]);
        } catch (GuzzleException $e) {
            Log::error('[Saga][Compensation] Failed to release inventory', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }

        $this->failOrder($order, $reason);
    }

    /**
     * Mark order as failed and notify customer.
     */
    private function failOrder(Order $order, string $reason): void
    {
        $status = $reason === 'payment_failed'
            ? Order::STATUS_PAYMENT_FAILED
            : Order::STATUS_CANCELLED;

        $order->update([
            'status'      => $status,
            'saga_status' => Order::SAGA_COMPENSATED,
            'metadata'    => array_merge($order->metadata ?? [], ['failure_reason' => $reason]),
        ]);

        $this->sendNotification($order, 'order_failed', ['reason' => $reason]);
    }

    /**
     * Public compensation entry – cancel a previously confirmed order.
     */
    public function compensateOrderSaga(Order $order, string $reason): Order
    {
        $order->update(['status' => Order::STATUS_COMPENSATING]);

        // Release inventory first
        $this->compensateInventory($order, $reason);

        // Refund payment if one was taken
        if ($order->payment_id) {
            $this->refundPayment($order, $reason);
        }

        return $order->fresh();
    }

    private function refundPayment(Order $order, string $reason): void
    {
        try {
            $this->http->post(
                rtrim(config('services.payment.url'), '/') . '/api/payments/' . $order->payment_id . '/refund',
                [
                    'json'    => ['order_id' => $order->id, 'reason' => $reason],
                    'headers' => ['Accept' => 'application/json'],
                ]
            );
            Log::info('[Saga][Compensation] Payment refunded', ['order_id' => $order->id]);
        } catch (GuzzleException $e) {
            Log::error('[Saga][Compensation] Failed to refund payment', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    private function sendNotification(Order $order, string $event, array $extra = []): void
    {
        try {
            $this->http->post(
                rtrim(config('services.notification.url'), '/') . '/api/notifications/send',
                [
                    'json' => array_merge([
                        'tenant_id'   => $order->tenant_id,
                        'customer_id' => $order->customer_id,
                        'order_id'    => $order->id,
                        'event'       => $event,
                        'channel'     => 'email',
                    ], $extra),
                    'headers' => ['Accept' => 'application/json'],
                ]
            );
        } catch (GuzzleException $e) {
            // Notification failure is non-critical; log and continue
            Log::warning('[Saga] Notification failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle incoming Saga event callbacks from other services.
     */
    public function handleSagaEvent(Order $order, string $event, array $data): Order
    {
        Log::info('[Saga] Event received', ['order_id' => $order->id, 'event' => $event]);

        match ($event) {
            'payment_processed'  => $order->update(['status' => Order::STATUS_CONFIRMED]),
            'payment_failed'     => $this->compensateInventory($order, 'payment_failed'),
            'inventory_reserved' => $this->processPayment($order),
            'inventory_failed'   => $this->failOrder($order, 'inventory_unavailable'),
            default              => Log::warning('[Saga] Unknown event', ['event' => $event]),
        };

        return $order->fresh();
    }
}
