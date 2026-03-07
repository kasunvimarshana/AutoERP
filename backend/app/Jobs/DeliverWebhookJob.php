<?php

namespace App\Jobs;

use App\Models\Webhook;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeliverWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;
    public int $timeout;

    public function __construct(
        private readonly Webhook $webhook,
        private readonly string  $event,
        private readonly array   $payload
    ) {
        $this->tries   = config('webhook.retry_attempts', 3);
        $this->timeout = config('webhook.timeout', 30);
    }

    public function handle(): void
    {
        $client    = new Client(['timeout' => $this->timeout, 'verify' => config('webhook.verify_ssl', true)]);
        $body      = json_encode($this->payload);
        $signature = 'sha256=' . hash_hmac('sha256', $body, $this->webhook->secret ?? '');

        $headers = [
            'Content-Type'     => 'application/json',
            'X-Webhook-Event'  => $this->event,
            'X-Webhook-ID'     => $this->webhook->id,
            'X-Webhook-Sig'    => $signature,
            'X-Delivery-ID'    => \Illuminate\Support\Str::uuid()->toString(),
            'X-Timestamp'      => now()->timestamp,
            'User-Agent'       => 'KV-SaaS-Webhook/1.0',
        ];

        try {
            $response = $client->post($this->webhook->url, [
                'headers' => $headers,
                'body'    => $body,
            ]);

            $statusCode = $response->getStatusCode();
            $this->webhook->recordSuccess($statusCode);

            Log::info("[DeliverWebhookJob] Delivered event '{$this->event}' to {$this->webhook->url}", [
                'status_code' => $statusCode,
                'webhook_id'  => $this->webhook->id,
            ]);
        } catch (RequestException $e) {
            $statusCode = $e->getResponse()?->getStatusCode() ?? 0;
            $this->webhook->recordFailure($statusCode);

            Log::warning("[DeliverWebhookJob] Failed to deliver event '{$this->event}' to {$this->webhook->url}", [
                'error'      => $e->getMessage(),
                'status'     => $statusCode,
                'webhook_id' => $this->webhook->id,
            ]);

            // Re-throw so the queue can retry
            throw $e;
        }
    }

    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(config('webhook.retry_delay', 60) * $this->tries);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("[DeliverWebhookJob] Permanently failed for webhook #{$this->webhook->id}: " . $exception->getMessage());
        $this->webhook->update(['status' => 'disabled']);
    }
}
