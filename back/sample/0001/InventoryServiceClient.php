<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * InventoryServiceClient
 *
 * Thin HTTP adapter for the Inventory Service.
 *
 * Key design decisions
 * ────────────────────
 * • Every mutating request carries an Idempotency-Key header so that
 *   network retries cannot accidentally double-reserve / double-release
 *   stock.
 * • A simple linear retry (3 attempts, 200 ms pause) is applied only to
 *   connection/timeout errors, not to 4xx client errors.
 * • All exceptions are re-thrown so the caller (OrderController) can
 *   decide whether to compensate.
 */
class InventoryServiceClient
{
    private string $baseUrl;
    private int    $timeoutSeconds;
    private int    $retries;

    public function __construct()
    {
        $this->baseUrl        = config('services.inventory.url', 'http://inventory-service');
        $this->timeoutSeconds = (int) config('services.inventory.timeout', 5);
        $this->retries        = (int) config('services.inventory.retries', 3);
    }

    // ------------------------------------------------------------------ //
    //  Public API
    // ------------------------------------------------------------------ //

    /**
     * Reserve stock for an order.
     *
     * POST /api/inventory/reservations
     *
     * @param  int   $orderId
     * @param  array $items  [['product_id' => int, 'quantity' => int], ...]
     * @return array         ['reservation_id' => string, 'items' => array]
     * @throws RequestException|\RuntimeException
     */
    public function reserve(int $orderId, array $items): array
    {
        $idempotencyKey = "reserve-order-{$orderId}";

        Log::info("[InventoryClient] Reserving stock.", compact('orderId', 'items'));

        $response = $this->client()
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->retry($this->retries, 200, fn ($e) => $this->isRetryable($e))
            ->post("{$this->baseUrl}/api/inventory/reservations", [
                'order_id' => $orderId,
                'items'    => $items,
            ]);

        $response->throw(); // throws RequestException on 4xx/5xx

        return $response->json();
    }

    /**
     * Adjust an existing reservation (used on order update).
     *
     * PATCH /api/inventory/reservations/{reservationId}
     *
     * @throws RequestException|\RuntimeException
     */
    public function adjustReservation(string $reservationId, array $newItems): array
    {
        $idempotencyKey = "adjust-{$reservationId}-" . md5(serialize($newItems));

        Log::info("[InventoryClient] Adjusting reservation {$reservationId}.");

        $response = $this->client()
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->retry($this->retries, 200, fn ($e) => $this->isRetryable($e))
            ->patch("{$this->baseUrl}/api/inventory/reservations/{$reservationId}", [
                'items' => $newItems,
            ]);

        $response->throw();

        return $response->json();
    }

    /**
     * Cancel / release a reservation (used on order delete or rollback).
     *
     * DELETE /api/inventory/reservations/{reservationId}
     *
     * @throws RequestException|\RuntimeException
     */
    public function cancelReservation(string $reservationId): void
    {
        $idempotencyKey = "cancel-{$reservationId}";

        Log::info("[InventoryClient] Cancelling reservation {$reservationId}.");

        $response = $this->client()
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->retry($this->retries, 200, fn ($e) => $this->isRetryable($e))
            ->delete("{$this->baseUrl}/api/inventory/reservations/{$reservationId}");

        // 404 is fine — reservation may already be gone (idempotent delete)
        if ($response->status() !== 404) {
            $response->throw();
        }
    }

    // ------------------------------------------------------------------ //
    //  Internals
    // ------------------------------------------------------------------ //

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout($this->timeoutSeconds)
            ->acceptJson()
            ->withHeaders([
                'X-Service-Name' => 'order-service',
            ]);
    }

    /**
     * Only retry on connection/timeout errors, not on 4xx client errors.
     */
    private function isRetryable(\Throwable $e): bool
    {
        if ($e instanceof RequestException) {
            return $e->response->status() >= 500;
        }
        // ConnectException, TimeoutException, etc.
        return true;
    }
}
