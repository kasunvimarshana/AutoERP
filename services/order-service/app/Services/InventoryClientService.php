<?php
namespace App\Services;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
class InventoryClientService {
    private Client $client;
    public function __construct() {
        $this->client = new Client(['base_uri' => config('services.inventory.url'), 'timeout' => 10.0, 'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json']]);
    }
    public function reserveStock(string $tenantId, string $productId, int $quantity, string $orderId): bool {
        try { $r = $this->client->post('/api/v1/inventory/reserve', ['headers' => ['X-Tenant-ID' => $tenantId], 'json' => ['product_id' => $productId, 'quantity' => $quantity, 'order_id' => $orderId, 'tenant_id' => $tenantId]]); return in_array($r->getStatusCode(), [200, 201]); } catch (RequestException $e) { Log::error('Failed to reserve stock', ['product_id' => $productId, 'error' => $e->getMessage()]); return false; }
    }
    public function releaseStock(string $tenantId, string $productId, int $quantity, string $orderId): bool {
        try { $r = $this->client->post('/api/v1/inventory/release', ['headers' => ['X-Tenant-ID' => $tenantId], 'json' => ['product_id' => $productId, 'quantity' => $quantity, 'order_id' => $orderId, 'tenant_id' => $tenantId]]); return $r->getStatusCode() === 200; } catch (RequestException $e) { Log::error('Failed to release stock', ['product_id' => $productId, 'error' => $e->getMessage()]); return false; }
    }
    public function checkStock(string $tenantId, string $productId): array {
        try { $r = $this->client->get("/api/v1/inventory/{$productId}", ['headers' => ['X-Tenant-ID' => $tenantId], 'query' => ['tenant_id' => $tenantId]]); return json_decode((string) $r->getBody(), true) ?? []; } catch (RequestException $e) { Log::error('Failed to check stock', ['product_id' => $productId, 'error' => $e->getMessage()]); return []; }
    }
    public function healthCheck(): bool {
        try { $r = $this->client->get('/health', ['timeout' => 3]); return $r->getStatusCode() === 200; } catch (\Throwable $e) { Log::warning('Inventory service health check failed', ['error' => $e->getMessage()]); return false; }
    }
}
