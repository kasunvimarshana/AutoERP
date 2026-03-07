<?php

declare(strict_types=1);

namespace App\Saga\Steps;

use App\Contracts\SagaStepInterface;
use App\Exceptions\SagaStepException;
use App\Saga\SagaContext;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Saga Step 1: Reserve inventory for each order item.
 *
 * Forward:     POST /api/v1/inventory/reserve  → Inventory Service
 * Compensate:  POST /api/v1/inventory/release  → Inventory Service (undo)
 *
 * Context reads:
 *   - order_id, tenant_id, items (array of {product_id, quantity})
 *
 * Context writes:
 *   - inventory_reserved (bool)
 */
final class ReserveInventoryStep implements SagaStepInterface
{
    private readonly Client $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'base_uri' => config('services.inventory.url'),
            'timeout'  => 10.0,
        ]);
    }

    public function getName(): string
    {
        return 'ReserveInventory';
    }

    public function execute(SagaContext $context): void
    {
        $items    = $context->get('items', []);
        $tenantId = $context->get('tenant_id');
        $orderId  = $context->get('order_id');

        foreach ($items as $item) {
            try {
                $response = $this->httpClient->post('/api/v1/inventory/reserve', [
                    'json'    => [
                        'product_id' => $item['product_id'],
                        'quantity'   => $item['quantity'],
                        'order_id'   => $orderId,
                    ],
                    'headers' => [
                        'X-Tenant-ID'   => $tenantId,
                        'Accept'        => 'application/json',
                        'X-Internal-Service' => 'order-service',
                    ],
                ]);

                $body = json_decode($response->getBody()->getContents(), true);

                if (!($body['success'] ?? false)) {
                    throw new SagaStepException(
                        "Inventory reservation failed for product {$item['product_id']}: " .
                        ($body['message'] ?? 'Unknown error')
                    );
                }
            } catch (GuzzleException $e) {
                throw new SagaStepException(
                    "Failed to contact inventory service: {$e->getMessage()}",
                    previous: $e
                );
            }
        }

        $context->set('inventory_reserved', true);
        Log::info("[ReserveInventoryStep] All items reserved", ['order_id' => $orderId]);
    }

    public function compensate(SagaContext $context): void
    {
        // Only release if we actually reserved
        if (!$context->get('inventory_reserved')) {
            return;
        }

        $items    = $context->get('items', []);
        $tenantId = $context->get('tenant_id');
        $orderId  = $context->get('order_id');

        foreach ($items as $item) {
            try {
                $this->httpClient->post('/api/v1/inventory/release', [
                    'json'    => [
                        'product_id' => $item['product_id'],
                        'quantity'   => $item['quantity'],
                        'order_id'   => $orderId,
                    ],
                    'headers' => [
                        'X-Tenant-ID'        => $tenantId,
                        'Accept'             => 'application/json',
                        'X-Internal-Service' => 'order-service',
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::error("[ReserveInventoryStep] Compensation failed for product {$item['product_id']}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("[ReserveInventoryStep] Compensation completed (stock released)", ['order_id' => $orderId]);
    }
}
