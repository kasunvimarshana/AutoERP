<?php

namespace App\Webhooks;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookDispatcher
{
    private const MAX_RETRIES    = 3;
    private const RETRY_DELAY_MS = 500;
    private const TIMEOUT_S      = 10;

    // -------------------------------------------------------------------------
    // Dispatch
    // -------------------------------------------------------------------------

    /**
     * Send a webhook to a single URL, with HMAC signing and retry logic.
     */
    public function dispatch(string $url, WebhookPayload $payload, ?string $secret = null): bool
    {
        $body      = $payload->toJson();
        $signature = $secret ? $payload->sign($secret) : null;

        $headers = [
            'Content-Type'       => 'application/json',
            'X-Webhook-ID'       => $payload->webhookId,
            'X-Webhook-Event'    => $payload->event,
            'X-Webhook-Timestamp' => $payload->timestamp,
        ];

        if ($signature !== null) {
            $headers['X-Webhook-Signature'] = 'sha256=' . $signature;
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(self::TIMEOUT_S)
                ->retry(self::MAX_RETRIES, self::RETRY_DELAY_MS, function (\Exception $e, $request) {
                    // Only retry on connection errors or 5xx responses
                    return $e instanceof \Illuminate\Http\Client\ConnectionException
                        || ($e instanceof \Illuminate\Http\Client\RequestException
                            && $e->response->serverError());
                })
                ->send('POST', $url, ['body' => $body]);

            if ($response->successful()) {
                Log::info('Webhook delivered', [
                    'webhook_id' => $payload->webhookId,
                    'event'      => $payload->event,
                    'url'        => $url,
                    'status'     => $response->status(),
                ]);

                return true;
            }

            Log::warning('Webhook non-success response', [
                'webhook_id' => $payload->webhookId,
                'event'      => $payload->event,
                'url'        => $url,
                'status'     => $response->status(),
                'body'       => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('Webhook dispatch failed', [
                'webhook_id' => $payload->webhookId,
                'event'      => $payload->event,
                'url'        => $url,
                'error'      => $e->getMessage(),
            ]);

            return false;
        }
    }

    // -------------------------------------------------------------------------
    // Fan-out to multiple URLs
    // -------------------------------------------------------------------------

    /**
     * Deliver the same payload to multiple webhook URLs.
     *
     * @param  array<string>  $urls
     * @return array<string, bool>  Map of URL => success
     */
    public function dispatchToMany(array $urls, WebhookPayload $payload, ?string $secret = null): array
    {
        $results = [];

        foreach ($urls as $url) {
            $results[$url] = $this->dispatch($url, $payload, $secret);
        }

        return $results;
    }

    // -------------------------------------------------------------------------
    // Convenience factory: build payload and dispatch in one call
    // -------------------------------------------------------------------------

    public function send(
        string          $url,
        string          $event,
        array           $data,
        ?string         $secret   = null,
        int|string|null $tenantId = null,
    ): bool {
        $payload = WebhookPayload::create($event, $data, $tenantId);

        return $this->dispatch($url, $payload, $secret);
    }
}
