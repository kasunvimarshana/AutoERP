<?php

namespace App\Services;

use App\Exceptions\ServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client for communicating with the Inventory Service.
 *
 * This class encapsulates all HTTP calls to the Inventory Service API.
 * It is used by the Order Service to manage inventory reservations as
 * part of the distributed transaction (Saga pattern).
 */
class InventoryServiceClient
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.inventory.base_url', 'http://inventory-service:8000'), '/');
        $this->timeout = (int) config('services.inventory.timeout', 10);
    }

    /**
     * Reserve inventory for an order.
     * This is the first step in the distributed transaction saga.
     *
     * @throws ServiceException when reservation fails
     */
    public function reserveInventory(string $sku, int $quantity, int $orderId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/api/inventories/reserve", [
                    'sku' => $sku,
                    'quantity' => $quantity,
                    'order_id' => $orderId,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            $error = $response->json('message', 'Unknown error from Inventory Service');
            Log::warning("Inventory reservation failed for order #{$orderId}", [
                'sku' => $sku,
                'quantity' => $quantity,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new ServiceException(
                "Inventory Service reservation failed: {$error}",
                $response->status()
            );
        } catch (ConnectionException $e) {
            Log::error("Cannot connect to Inventory Service for order #{$orderId}", [
                'error' => $e->getMessage(),
            ]);
            throw new ServiceException(
                'Inventory Service is unavailable. Please try again later.',
                503
            );
        }
    }

    /**
     * Release (compensating transaction) a previously reserved inventory.
     * Called when the order creation fails after inventory was reserved.
     * This is the Saga rollback / compensating transaction.
     *
     * @throws ServiceException when release fails
     */
    public function releaseInventory(string $sku, int $quantity, int $orderId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/api/inventories/release", [
                    'sku' => $sku,
                    'quantity' => $quantity,
                    'order_id' => $orderId,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("CRITICAL: Inventory release failed for order #{$orderId}. Manual intervention required.", [
                'sku' => $sku,
                'quantity' => $quantity,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new ServiceException(
                "Inventory Service release failed: " . $response->json('message', 'Unknown error'),
                $response->status()
            );
        } catch (ConnectionException $e) {
            Log::error("CRITICAL: Cannot connect to Inventory Service to release reservation for order #{$orderId}. Manual intervention required.", [
                'sku' => $sku,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
            ]);
            throw new ServiceException(
                'Inventory Service is unavailable during release. Manual intervention may be required.',
                503
            );
        }
    }

    /**
     * Fulfill inventory for a completed order (deduct stock).
     * Called when an order is confirmed/completed.
     *
     * @throws ServiceException when fulfillment fails
     */
    public function fulfillInventory(string $sku, int $quantity, int $orderId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/api/inventories/fulfill", [
                    'sku' => $sku,
                    'quantity' => $quantity,
                    'order_id' => $orderId,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new ServiceException(
                "Inventory Service fulfillment failed: " . $response->json('message', 'Unknown error'),
                $response->status()
            );
        } catch (ConnectionException $e) {
            throw new ServiceException(
                'Inventory Service is unavailable for fulfillment.',
                503
            );
        }
    }

    /**
     * Get inventory details for a given SKU from Inventory Service.
     *
     * @throws ServiceException when request fails
     */
    public function getInventoryBySku(string $sku): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/api/inventories", ['sku' => $sku]);

            if ($response->successful()) {
                $data = $response->json('data', []);
                return collect($data)->firstWhere('sku', $sku);
            }

            return null;
        } catch (ConnectionException $e) {
            Log::warning("Cannot fetch inventory details for SKU {$sku}", [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
