<?php

namespace App\Webhooks;

use App\DTOs\WebhookPayloadDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookDispatcher
{
    private const MAX_RETRIES = 3;
    private const BASE_DELAY_SECONDS = 1;

    public function __construct(private readonly WebhookPayload $webhookPayload)
    {
    }

    /**
     * Fan-out a webhook event to multiple endpoints with retry + exponential backoff.
     */
    public function dispatch(string $event, array $payload, array $endpoints, string $tenantId = ''): void
    {
        $dto  = $this->webhookPayload->build($event, $payload, $tenantId);
        $body = $dto->toArray();
        $signature = $this->webhookPayload->sign($body);

        foreach ($endpoints as $endpoint) {
            $this->sendWithRetry($endpoint, $body, $signature, $event);
        }
    }

    private function sendWithRetry(string $endpoint, array $body, string $signature, string $event): void
    {
        $attempt = 0;

        while ($attempt < self::MAX_RETRIES) {
            try {
                $response = Http::withHeaders([
                    'Content-Type'        => 'application/json',
                    'Accept'              => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event'     => $event,
                ])
                    ->timeout(10)
                    ->post($endpoint, $body);

                if ($response->successful()) {
                    Log::info('WebhookDispatcher: delivered', [
                        'endpoint' => $endpoint,
                        'event'    => $event,
                        'attempt'  => $attempt + 1,
                        'status'   => $response->status(),
                    ]);
                    return;
                }

                Log::warning('WebhookDispatcher: non-2xx response', [
                    'endpoint' => $endpoint,
                    'event'    => $event,
                    'attempt'  => $attempt + 1,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('WebhookDispatcher: request exception', [
                    'endpoint' => $endpoint,
                    'event'    => $event,
                    'attempt'  => $attempt + 1,
                    'error'    => $e->getMessage(),
                ]);
            }

            $attempt++;

            if ($attempt < self::MAX_RETRIES) {
                $delaySecs = self::BASE_DELAY_SECONDS * (2 ** ($attempt - 1)); // 1s, 2s, 4s
                sleep($delaySecs);
            }
        }

        Log::error('WebhookDispatcher: all retries exhausted', [
            'endpoint' => $endpoint,
            'event'    => $event,
        ]);
    }
}
