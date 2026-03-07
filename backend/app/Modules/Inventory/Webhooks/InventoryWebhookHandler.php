<?php

namespace App\Modules\Inventory\Webhooks;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use App\Modules\Tenant\Models\Tenant;

class InventoryWebhookHandler
{
    public function __construct(
        private readonly Client $client = new Client(['timeout' => 5]),
    ) {}

    public function dispatch(int $tenantId, string $event, array $payload): void
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return;
        }

        $webhookUrl = $tenant->settings['webhook_url'] ?? null;

        if (!$webhookUrl) {
            return;
        }

        try {
            $this->client->post($webhookUrl, [
                'json' => [
                    'event'     => $event,
                    'tenant_id' => $tenantId,
                    'payload'   => $payload,
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (GuzzleException $e) {
            Log::warning('Inventory webhook delivery failed', [
                'tenant_id'   => $tenantId,
                'event'       => $event,
                'webhook_url' => $webhookUrl,
                'error'       => $e->getMessage(),
            ]);
        }
    }
}
