<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ProductServiceClient
{
    private string $baseUrl;
    private Client $client;

    public function __construct()
    {
        $this->baseUrl = env('PRODUCT_SERVICE_URL', 'http://product-service:8003');
        $this->client  = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 5.0,
        ]);
    }

    public function getProductsByFilters(array $filters): array
    {
        try {
            $response = $this->client->get('/api/products', [
                'query' => $filters,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            return $body['data']['products'] ?? [];
        } catch (GuzzleException $e) {
            return [];
        }
    }

    public function getProduct(int $id): ?array
    {
        try {
            $response = $this->client->get("/api/products/{$id}");
            $body     = json_decode($response->getBody()->getContents(), true);

            return $body['data']['product'] ?? null;
        } catch (GuzzleException $e) {
            return null;
        }
    }

    public function getProductsByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        try {
            $response = $this->client->get('/api/products', [
                'query' => ['ids' => implode(',', $ids)],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            return $body['data']['products'] ?? [];
        } catch (GuzzleException $e) {
            return [];
        }
    }
}
