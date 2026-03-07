<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Models\Order;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Releases reserved inventory when an order is cancelled.
 *
 * Works as an async safety-net in addition to the synchronous release
 * attempted inside OrderSagaService::cancelOrderSaga().
 */
class HandleInventoryRelease implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'order-events';

    public int $tries = 5;

    public int $backoff = 15;

    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository
    ) {}

    public function handle(OrderCancelled $event): void
    {
        $order = $event->order->fresh(['items']);

        if ($order === null) {
            Log::warning('HandleInventoryRelease: order not found', [
                'order_id' => $event->order->id,
            ]);
            return;
        }

        // Only release if there is compensation data from the saga
        $compensationData = $order->saga_compensation_data ?? [];

        if (empty($compensationData)) {
            Log::info('HandleInventoryRelease: no compensation data – nothing to release', [
                'order_id' => $order->id,
            ]);
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
                        'performed_by'   => 'order-service:HandleInventoryRelease',
                    ]);

                if ($response->failed()) {
                    Log::error('HandleInventoryRelease: HTTP call failed', [
                        'order_id'   => $order->id,
                        'product_id' => $productId,
                        'status'     => $response->status(),
                        'body'       => $response->body(),
                    ]);
                } else {
                    Log::info('HandleInventoryRelease: stock released', [
                        'order_id'   => $order->id,
                        'product_id' => $productId,
                        'quantity'   => $quantity,
                    ]);
                }
            } catch (Throwable $e) {
                Log::critical('HandleInventoryRelease: exception – manual intervention required', [
                    'order_id'   => $order->id,
                    'product_id' => $productId,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }

    public function failed(OrderCancelled $event, Throwable $exception): void
    {
        Log::critical('HandleInventoryRelease job permanently failed – manual intervention required', [
            'order_id' => $event->order->id,
            'error'    => $exception->getMessage(),
        ]);
    }
}
