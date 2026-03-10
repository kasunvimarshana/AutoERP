<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductServiceClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('PRODUCT_SERVICE_URL', 'http://product-service:8003'), '/');
    }

    public function getProduct(int $id): ?array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/api/products/{$id}");
            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? $data ?? null;
            }
            return null;
        } catch (\Exception $e) {
            Log::error("ProductServiceClient::getProduct failed", ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function getProductsByFilters(array $filters): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/api/products", $filters);
            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? $data ?? [];
            }
            return [];
        } catch (\Exception $e) {
            Log::error("ProductServiceClient::getProductsByFilters failed", ['filters' => $filters, 'error' => $e->getMessage()]);
            return [];
        }
    }

    public function getProductsByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/api/products", ['ids' => implode(',', $ids)]);
            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? $data ?? [];
            }
            return [];
        } catch (\Exception $e) {
            Log::error("ProductServiceClient::getProductsByIds failed", ['ids' => $ids, 'error' => $e->getMessage()]);
            return [];
        }
    }
}
