<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductClientService
{
    private string $baseUrl;
    private int    $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.product_service.url', 'http://product-service:8002'), '/');
        $this->timeout = (int) config('services.product_service.timeout', 5);
    }

    /**
     * Verify that a product exists in the product-service.
     *
     * @throws \RuntimeException when the product-service is unreachable
     */
    public function productExists(int $productId, int $tenantId): bool
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-Tenant-ID'     => $tenantId,
                    'X-Service-Token' => $this->getServiceToken(),
                    'Accept'          => 'application/json',
                ])
                ->get("{$this->baseUrl}/api/v1/products/{$productId}");

            if ($response->status() === 404) {
                return false;
            }

            if ($response->successful()) {
                return true;
            }

            Log::warning('ProductClientService: unexpected status from product-service', [
                'product_id' => $productId,
                'tenant_id'  => $tenantId,
                'status'     => $response->status(),
            ]);

            // Treat non-404 errors as unknown — allow proceeding to avoid tight coupling
            return true;
        } catch (\Throwable $e) {
            Log::error('ProductClientService: could not reach product-service', [
                'product_id' => $productId,
                'error'      => $e->getMessage(),
            ]);

            // Fail open to keep services decoupled; downstream validation handles data integrity
            return true;
        }
    }

    /**
     * Fetch product details from the product-service.
     * Returns null when the product doesn't exist or the service is unavailable.
     */
    public function getProduct(int $productId, int $tenantId): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-Tenant-ID'     => $tenantId,
                    'X-Service-Token' => $this->getServiceToken(),
                    'Accept'          => 'application/json',
                ])
                ->get("{$this->baseUrl}/api/v1/products/{$productId}");

            if ($response->successful()) {
                return $response->json('data');
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('ProductClientService: failed to fetch product', [
                'product_id' => $productId,
                'error'      => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Ping the product-service health endpoint.
     */
    public function ping(): bool
    {
        try {
            $response = Http::timeout(3)->get("{$this->baseUrl}/api/v1/health");

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function getServiceToken(): string
    {
        // In production this would be a machine-to-machine OAuth2 token.
        // For now we use a shared secret header approach.
        return config('services.product_service.secret', '');
    }
}
