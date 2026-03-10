<?php

declare(strict_types=1);

namespace App\Infrastructure\Webhook;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * WebhookDispatcher
 *
 * Dispatches outgoing webhook notifications to tenant-registered endpoints
 * whenever domain events occur.  Implements:
 *  - Retry with exponential backoff (up to 3 attempts)
 *  - HMAC-SHA256 signature for payload verification
 *  - Configurable timeouts and headers
 */
class WebhookDispatcher
{
    private const MAX_RETRIES        = 3;
    private const INITIAL_RETRY_WAIT = 1; // seconds

    private Client $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout'         => 10,
            'connect_timeout' => 5,
        ]);
    }

    /**
     * Dispatch a webhook event to the given endpoint.
     *
     * @param  string               $url        Tenant-registered endpoint
     * @param  string               $eventType  e.g. "order.completed"
     * @param  array<string, mixed> $payload    Event payload
     * @param  string               $secret     HMAC signing secret
     * @param  array<string, string> $headers   Additional headers
     * @return bool                             True if delivered successfully
     */
    public function dispatch(
        string $url,
        string $eventType,
        array  $payload,
        string $secret,
        array  $headers = []
    ): bool {
        $body      = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $signature = $this->sign($body, $secret);
        $attempt   = 0;

        while ($attempt < self::MAX_RETRIES) {
            try {
                $response = $this->httpClient->post($url, [
                    'body'    => $body,
                    'headers' => array_merge($headers, [
                        'Content-Type'           => 'application/json',
                        'X-Webhook-Event'        => $eventType,
                        'X-Webhook-Signature'    => 'sha256=' . $signature,
                        'X-Webhook-Delivery-ID'  => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                        'X-Webhook-Timestamp'    => (string) time(),
                    ]),
                ]);

                Log::info("Webhook delivered [{$eventType}] to [{$url}] (HTTP {$response->getStatusCode()})");

                return true;

            } catch (RequestException $e) {
                $attempt++;

                $delay = self::INITIAL_RETRY_WAIT * (2 ** ($attempt - 1)); // exponential backoff

                Log::warning("Webhook delivery attempt {$attempt}/" . self::MAX_RETRIES . " failed for [{$url}]: "
                    . $e->getMessage() . ". Retrying in {$delay}s.");

                if ($attempt < self::MAX_RETRIES) {
                    sleep($delay);
                }
            }
        }

        Log::error("Webhook delivery FAILED after " . self::MAX_RETRIES . " attempts for [{$url}] event [{$eventType}]");

        return false;
    }

    /**
     * Generate an HMAC-SHA256 signature for payload verification.
     *
     * @param  string $payload
     * @param  string $secret
     * @return string
     */
    private function sign(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Verify an incoming webhook signature (for inbound webhooks).
     *
     * @param  string $payload
     * @param  string $signature  The X-Webhook-Signature header value (sha256=...)
     * @param  string $secret
     * @return bool
     */
    public function verify(string $payload, string $signature, string $secret): bool
    {
        $expected = 'sha256=' . $this->sign($payload, $secret);

        return hash_equals($expected, $signature);
    }
}
