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
        $this->http = new Client([
            'base_uri' => config('services.inventory_service.url'),
            'timeout' => 5,
        ]);
    }

    public function getInventoryForProduct(int $productId, int $tenantId): array
    {
        try {
            $response = $this->http->get('/api/v1/inventory', [
                'query' => ['product_id' => $productId],
                'headers' => [
                    'X-Tenant-ID' => $tenantId,
                    'X-Service-Secret' => config('services.auth_service.shared_secret'),
                    'Accept' => 'application/json',
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true)['data'] ?? [];
        } catch (RequestException $e) {
            Log::warning('InventoryServiceClient: Failed to fetch inventory', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
