<?php

namespace App\Modules\Order\Services;

use App\Modules\Order\DTOs\OrderDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductGatewayService
{
    /**
     * Cross-Service Relational Data Fetching: Retrieve a product synchronously.
     * Passes internal Keycloak-issued Client Credentials or user-delegated token.
     */
    public function getProductDetails(int $productId, string $jwtToken)
    {
        // Calling product-service internal network URL (e.g. Docker alias)
        $url = "http://product-service:8000/api/products/{$productId}";

        try {
            $response = Http::withToken($jwtToken)->get($url);

            if ($response->successful()) {
                return $response->json('data');
            }
            throw new \Exception("Product Service returned {$response->status()}");
        } catch (\Exception $e) {
            Log::error("Cross-Service Communication Failed: " . $e->getMessage());
            throw new \Exception("Could not verify product details for ID: {$productId}");
        }
    }
}
