<?php

namespace App\Services;

use App\DTOs\InventoryDataDTO;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class InventoryClientService
{
    private Client $client;

    public function __construct(private readonly KeycloakService $keycloakService)
    {
        $this->client = new Client([
            'base_uri' => rtrim(config('services.inventory.url'), '/'),
            'timeout'  => config('services.inventory.timeout', 5),
        ]);
    }

    /**
     * Fetch inventory data for a product. Returns null on any connectivity issue.
     */
    public function getInventoryForProduct(int $productId, string $tenantId): ?InventoryDataDTO
    {
        try {
            $token    = $this->keycloakService->getServiceAccountToken();
            $response = $this->client->get("/api/v1/inventory/product/{$productId}", [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                    'X-Tenant-ID'   => $tenantId,
                    'Accept'        => 'application/json',
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            return InventoryDataDTO::fromArray($body['data'] ?? $body);
        } catch (ConnectException $e) {
            Log::warning('inventory-service unreachable', [
                'product_id' => $productId,
                'error'      => $e->getMessage(),
            ]);
            return null;
        } catch (RequestException $e) {
            Log::warning('inventory-service returned error', [
                'product_id' => $productId,
                'status'     => $e->getResponse()?->getStatusCode(),
                'error'      => $e->getMessage(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Unexpected error fetching inventory data', [
                'product_id' => $productId,
                'error'      => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create an inventory record for a newly created product (Saga Step 1).
     *
     * @throws \Throwable on failure (let SyncInventoryJob handle retries)
     */
    public function createInventoryRecord(int $productId, string $sku, string $tenantId): void
    {
        $token = $this->keycloakService->getServiceAccountToken();

        $response = $this->client->post('/api/v1/inventory', [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'X-Tenant-ID'   => $tenantId,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'product_id' => $productId,
                'sku'        => $sku,
                'tenant_id'  => $tenantId,
            ],
        ]);

        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException(
                'inventory-service returned '.$response->getStatusCode().' for createInventoryRecord'
            );
        }
    }

    /**
     * Notify inventory-service that a product has been deleted (compensating listener).
     *
     * @throws \Throwable on failure
     */
    public function markProductDeleted(int $productId, string $tenantId): void
    {
        $token = $this->keycloakService->getServiceAccountToken();

        $response = $this->client->delete("/api/v1/inventory/product/{$productId}", [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'X-Tenant-ID'   => $tenantId,
                'Accept'        => 'application/json',
            ],
        ]);

        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException(
                'inventory-service returned '.$response->getStatusCode().' for markProductDeleted'
            );
        }
    }

    /**
     * Health-check ping to inventory-service.
     */
    public function ping(): bool
    {
        try {
            $response = $this->client->get('/api/v1/health', ['timeout' => 3]);
            return $response->getStatusCode() === 200;
        } catch (\Throwable) {
            return false;
        }
    }
}
