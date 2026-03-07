<?php

declare(strict_types=1);

namespace App\Infrastructure\Webhook;

use App\Infrastructure\Webhook\Contracts\WebhookServiceInterface;
use App\Models\Webhook;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Webhook service — registers endpoints, signs payloads, and delivers events.
 *
 * Payloads are signed with HMAC-SHA256 using the webhook's secret.
 * Delivery failures are logged and can be retried via queued jobs.
 */
class WebhookService implements WebhookServiceInterface
{
    public function __construct(
        private readonly Client $httpClient,
        private readonly Webhook $webhookModel,
    ) {}

    public function register(int|string $tenantId, string $url, array $events, array $options = []): Model
    {
        return $this->webhookModel->newQuery()->create([
            'tenant_id'    => $tenantId,
            'url'          => $url,
            'events'       => $events,
            'secret'       => $options['secret'] ?? bin2hex(random_bytes(32)),
            'is_active'    => $options['is_active'] ?? true,
            'max_retries'  => $options['max_retries'] ?? 3,
            'timeout'      => $options['timeout'] ?? 30,
            'custom_headers' => $options['headers'] ?? null,
            'metadata'     => $options['metadata'] ?? null,
        ]);
    }

    public function dispatch(string $event, array $payload, int|string $tenantId): void
    {
        $webhooks = $this->webhookModel->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereJsonContains('events', $event)
            ->get();

        foreach ($webhooks as $webhook) {
            try {
                $this->deliver($webhook->url, $webhook->secret, array_merge($payload, [
                    'event'     => $event,
                    'tenant_id' => $tenantId,
                    'timestamp' => now()->toIso8601String(),
                ]));

                $webhook->update([
                    'last_triggered_at'    => now(),
                    'consecutive_failures' => 0,
                ]);
            } catch (\Throwable $e) {
                $webhook->increment('consecutive_failures');
                Log::error("[WebhookService] Delivery failed for webhook #{$webhook->id}: {$e->getMessage()}");
            }
        }
    }

    public function deliver(string $url, string $secret, array $payload): array
    {
        $body      = json_encode($payload);
        $signature = 'sha256=' . hash_hmac('sha256', $body, $secret);
        $timestamp = now()->timestamp;

        try {
            $response = $this->httpClient->post($url, [
                'body'    => $body,
                'timeout' => 30,
                'headers' => [
                    'Content-Type'          => 'application/json',
                    'X-Webhook-Signature'   => $signature,
                    'X-Webhook-Timestamp'   => $timestamp,
                    'X-Webhook-Event'       => $payload['event'] ?? 'generic',
                    'User-Agent'            => 'InventoryWebhook/1.0',
                ],
            ]);

            return [
                'status_code'   => $response->getStatusCode(),
                'response_body' => (string) $response->getBody(),
                'delivered_at'  => now()->toIso8601String(),
            ];
        } catch (RequestException $e) {
            $statusCode = $e->getResponse()?->getStatusCode() ?? 0;

            throw new \RuntimeException(
                "Webhook delivery to {$url} failed with HTTP {$statusCode}: {$e->getMessage()}"
            );
        }
    }

    public function getForTenant(int|string $tenantId): Collection
    {
        return $this->webhookModel->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function delete(int|string $webhookId): bool
    {
        return (bool) $this->webhookModel->newQuery()
            ->where('id', $webhookId)
            ->delete();
    }

    /**
     * Verify an incoming webhook signature.
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }
}
