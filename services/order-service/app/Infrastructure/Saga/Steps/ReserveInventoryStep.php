<?php

declare(strict_types=1);

namespace App\Infrastructure\Saga\Steps;

use App\Domain\Order\Saga\SagaStepInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * ReserveInventoryStep
 *
 * Step 2 of the Order Saga.
 * Calls the Inventory Service to reserve stock for all ordered items.
 * Compensation: releases the reservation.
 */
class ReserveInventoryStep implements SagaStepInterface
{
    private Client $httpClient;

    public function __construct(
        private readonly string $inventoryServiceUrl,
    ) {
        $this->httpClient = new Client([
            'base_uri' => $this->inventoryServiceUrl,
            'timeout'  => 10,
        ]);
    }

    public function name(): string
    {
        return 'reserve_inventory';
    }

    public function execute(array &$context): void
    {
        $response = $this->httpClient->post('/api/inventory/reserve', [
            'json'    => [
                'order_id'  => $context['order_id'],
                'tenant_id' => $context['tenant_id'],
                'items'     => $context['items'],
            ],
            'headers' => [
                'X-Tenant-ID'    => $context['tenant_id'],
                'X-Saga-ID'      => $context['saga_id'] ?? $context['order_id'],
                'Authorization'  => 'Bearer ' . ($context['service_token'] ?? ''),
                'Accept'         => 'application/json',
            ],
        ]);

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $context['reservation_id'] = $body['data']['reservation_id'] ?? null;

        Log::info("ReserveInventoryStep: Reservation [{$context['reservation_id']}] created.");
    }

    public function compensate(array &$context): void
    {
        if (empty($context['reservation_id'])) {
            return;
        }

        try {
            $this->httpClient->delete("/api/inventory/reserve/{$context['reservation_id']}", [
                'headers' => [
                    'X-Tenant-ID'   => $context['tenant_id'],
                    'X-Saga-ID'     => $context['saga_id'] ?? $context['order_id'],
                    'Authorization' => 'Bearer ' . ($context['service_token'] ?? ''),
                    'Accept'        => 'application/json',
                ],
            ]);

            Log::info("ReserveInventoryStep compensation: Reservation [{$context['reservation_id']}] released.");
        } catch (RequestException $e) {
            Log::error("Failed to release inventory reservation: " . $e->getMessage());
        }
    }
}
