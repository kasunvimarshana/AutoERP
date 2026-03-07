<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class InventoryServiceClient
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client(['base_uri' => config('services.inventory_service.url'), 'timeout' => 10]);
    }

    private function headers(int $tenantId): array
    {
        return [
            'X-Service-Secret' => config('services.auth_service.shared_secret'),
            'X-Tenant-ID' => $tenantId,
            'Accept' => 'application/json',
        ];
    }

    public function reserveInventory(int $productId, int $warehouseId, int $quantity, int $tenantId): array
    {
        try {
            $response = $this->http->post('/api/v1/inventory/reserve', [
                'json' => ['product_id' => $productId, 'warehouse_id' => $warehouseId, 'quantity' => $quantity],
                'headers' => $this->headers($tenantId),
            ]);

            return ['success' => true, 'data' => json_decode($response->getBody()->getContents(), true)];
        } catch (RequestException $e) {
            $message = 'Inventory reservation failed';
            if ($e->hasResponse()) {
                $body = json_decode($e->getResponse()->getBody()->getContents(), true);
                $message = $body['message'] ?? $message;
            }

            return ['success' => false, 'message' => $message];
        }
    }

    public function releaseInventory(int $productId, int $warehouseId, int $quantity, int $tenantId): array
    {
        try {
            $response = $this->http->post('/api/v1/inventory/release', [
                'json' => ['product_id' => $productId, 'warehouse_id' => $warehouseId, 'quantity' => $quantity],
                'headers' => $this->headers($tenantId),
            ]);

            return ['success' => true, 'data' => json_decode($response->getBody()->getContents(), true)];
        } catch (RequestException $e) {
            Log::error('releaseInventory failed', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function confirmInventory(int $productId, int $warehouseId, int $quantity, int $tenantId): array
    {
        try {
            $response = $this->http->post('/api/v1/inventory/confirm', [
                'json' => ['product_id' => $productId, 'warehouse_id' => $warehouseId, 'quantity' => $quantity],
                'headers' => $this->headers($tenantId),
            ]);

            return ['success' => true, 'data' => json_decode($response->getBody()->getContents(), true)];
        } catch (RequestException $e) {
            Log::error('confirmInventory failed', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
