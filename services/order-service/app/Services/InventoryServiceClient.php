<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InventoryServiceClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('INVENTORY_SERVICE_URL', 'http://inventory-service:8004'), '/');
    }

    public function getInventoryByProductId(int $productId): ?array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/api/inventory/product/{$productId}");
            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? $data ?? null;
            }
            return null;
        } catch (\Exception $e) {
            Log::error("InventoryServiceClient::getInventoryByProductId failed", ['productId' => $productId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function reserveInventory(int $inventoryId, int $quantity, string $token): array
    {
        try {
            $response = Http::timeout(10)
                ->withToken($token)
                ->post("{$this->baseUrl}/api/inventory/{$inventoryId}/reserve", ['quantity' => $quantity]);

            return [
                'success' => $response->successful(),
                'data'    => $response->json(),
                'status'  => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error("InventoryServiceClient::reserveInventory failed", ['inventoryId' => $inventoryId, 'error' => $e->getMessage()]);
            return ['success' => false, 'data' => null, 'status' => 0];
        }
    }

    public function releaseInventory(int $inventoryId, int $quantity, string $token): array
    {
        try {
            $response = Http::timeout(10)
                ->withToken($token)
                ->post("{$this->baseUrl}/api/inventory/{$inventoryId}/release", ['quantity' => $quantity]);

            return [
                'success' => $response->successful(),
                'data'    => $response->json(),
                'status'  => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error("InventoryServiceClient::releaseInventory failed", ['inventoryId' => $inventoryId, 'error' => $e->getMessage()]);
            return ['success' => false, 'data' => null, 'status' => 0];
        }
    }
}
