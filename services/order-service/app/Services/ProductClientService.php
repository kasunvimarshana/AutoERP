<?php
namespace App\Services;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
class ProductClientService {
    private Client $client;
    public function __construct() {
        $this->client = new Client(['base_uri' => config('services.product.url'), 'timeout' => 10.0, 'headers' => ['Accept' => 'application/json']]);
    }
    public function getProduct(string $tenantId, string $productId): ?array {
        try { $r = $this->client->get("/api/v1/products/{$productId}", ['headers' => ['X-Tenant-ID' => $tenantId], 'query' => ['tenant_id' => $tenantId]]); return json_decode((string) $r->getBody(), true); } catch (RequestException $e) { Log::error('Failed to get product', ['product_id' => $productId, 'error' => $e->getMessage()]); return null; }
    }
    public function validateProducts(string $tenantId, array $productIds): array {
        $result = [];
        foreach ($productIds as $id) { $p = $this->getProduct($tenantId, $id); if ($p) $result[$id] = $p; }
        return $result;
    }
    public function getProductPrice(string $tenantId, string $productId): ?float {
        $p = $this->getProduct($tenantId, $productId); return ($p && isset($p['price'])) ? (float)$p['price'] : null;
    }
    public function healthCheck(): bool {
        try { $r = $this->client->get('/health', ['timeout' => 3]); return $r->getStatusCode() === 200; } catch (\Throwable $e) { Log::warning('Product service health check failed', ['error' => $e->getMessage()]); return false; }
    }
}
