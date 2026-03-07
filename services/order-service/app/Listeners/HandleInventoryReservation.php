<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\Order;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Triggered after an OrderCreated event when the saga needs to reserve stock.
 *
 * This listener is used for event-driven (asynchronous) reservation in contrast
 * to the synchronous reservation performed inside OrderSagaService::createOrderSaga().
 * It handles retries and compensation if the reservation fails.
 */
class HandleInventoryReservation implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'order-events';

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository
    ) {}

    public function handle(OrderCreated $event): void
    {
        $order = $event->order->fresh(['items']);

        if ($order === null) {
            Log::warning('HandleInventoryReservation: order not found', [
                'order_id' => $event->order->id,
            ]);
            return;
        }

        // Idempotency guard – only act if saga is still in 'started' state
        if ($order->saga_status !== Order::SAGA_STARTED) {
            Log::info('HandleInventoryReservation: skipping – saga already advanced', [
                'order_id'    => $order->id,
                'saga_status' => $order->saga_status,
            ]);
            return;
        }

        $inventoryUrl = rtrim(config('services.inventory.url'), '/');
        $timeout      = (int) config('services.inventory.timeout', 5);
        $reservations = [];

        foreach ($order->items as $item) {
            try {
                $response = Http::timeout($timeout)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->post("{$inventoryUrl}/api/v1/inventory/product/{$item->product_id}/reserve", [
                        'quantity'       => $item->quantity,
                        'reference_type' => 'order',
                        'reference_id'   => (string) $order->id,
                        'performed_by'   => 'order-service:HandleInventoryReservation',
                    ]);

                if ($response->failed()) {
                    $error = $response->json('message', $response->body());

                    Log::error('Inventory reservation failed in listener', [
                        'order_id'   => $order->id,
                        'product_id' => $item->product_id,
                        'status'     => $response->status(),
                        'error'      => $error,
                    ]);

                    // Release previously reserved items and cancel the order
                    $this->compensate($order, $reservations, $inventoryUrl, $timeout);

                    return;
                }

                $reservations[] = [
                    'product_id'    => $item->product_id,
                    'order_item_id' => $item->id,
                    'quantity'      => $item->quantity,
                ];
            } catch (Throwable $e) {
                Log::error('Exception during inventory reservation in listener', [
                    'order_id'   => $order->id,
                    'product_id' => $item->product_id,
                    'error'      => $e->getMessage(),
                ]);

                $this->compensate($order, $reservations, $inventoryUrl, $timeout);

                return;
            }
        }

        // All items reserved – advance saga state
        $this->orderRepository->update($order->id, [
            'saga_status'            => Order::SAGA_INVENTORY_RESERVED,
            'saga_compensation_data' => $reservations,
        ]);

        Log::info('HandleInventoryReservation: all items reserved', [
            'order_id' => $order->id,
        ]);
    }

    public function failed(OrderCreated $event, Throwable $exception): void
    {
        Log::error('HandleInventoryReservation job permanently failed', [
            'order_id' => $event->order->id,
            'error'    => $exception->getMessage(),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>> $reservations
     */
    private function compensate(Order $order, array $reservations, string $inventoryUrl, int $timeout): void
    {
        foreach ($reservations as $reservation) {
            try {
                Http::timeout($timeout)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->post("{$inventoryUrl}/api/v1/inventory/product/{$reservation['product_id']}/release", [
                        'quantity'       => $reservation['quantity'],
                        'reference_type' => 'order',
                        'reference_id'   => (string) $order->id,
                        'performed_by'   => 'order-service:HandleInventoryReservation:compensate',
                    ]);
            } catch (Throwable $e) {
                Log::critical('Failed to release inventory during compensation', [
                    'order_id'   => $order->id,
                    'product_id' => $reservation['product_id'],
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        $this->orderRepository->update($order->id, [
            'status'       => Order::STATUS_CANCELLED,
            'saga_status'  => Order::SAGA_COMPENSATED,
            'cancelled_at' => now(),
        ]);
    }
}
