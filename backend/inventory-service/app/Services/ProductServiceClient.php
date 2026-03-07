<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class ProductServiceClient
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => config('services.product_service.url'),
            'timeout' => 5,
        ]);
    }

    public function searchByName(string $name): array
    {
        try {
            $response = $this->http->get('/api/v1/products', [
                'query' => ['search' => $name, 'per_page' => 100],
                'headers' => [
                    'X-Tenant-ID' => app('current_tenant_id'),
                    'X-Service-Secret' => config('services.auth_service.shared_secret'),
                    'Accept' => 'application/json',
                ],
            ]);
            $data = json_decode($response->getBody()->getContents(), true);

            return $data['data'] ?? [];
        } catch (RequestException $e) {
            Log::warning('ProductServiceClient: searchByName failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function getProductById(int $productId): ?array
    {
        try {
            $response = $this->http->get("/api/v1/products/{$productId}", [
                'headers' => [
                    'X-Tenant-ID' => app('current_tenant_id'),
                    'X-Service-Secret' => config('services.auth_service.shared_secret'),
                    'Accept' => 'application/json',
                ],
            ]);
            $data = json_decode($response->getBody()->getContents(), true);

            return $data['data'] ?? null;
        } catch (RequestException $e) {
            Log::warning('ProductServiceClient: getProductById failed', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
