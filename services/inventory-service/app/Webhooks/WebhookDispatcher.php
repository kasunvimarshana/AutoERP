<?php

namespace App\Webhooks;

use App\Models\WebhookSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookDispatcher
{
    private int $timeout;
    private int $retryAttempts;

    public function __construct()
    {
        $this->timeout       = (int) config('services.webhooks.timeout', 10);
        $this->retryAttempts = (int) config('services.webhooks.retry_attempts', 3);
    }

    /**
     * Dispatch a webhook event to all active subscribers for the given tenant and event.
     */
    public function dispatch(int $tenantId, string $eventName, array $data): void
    {
        if (! class_exists(WebhookSubscription::class)) {
            return;
        }

        $subscribers = WebhookSubscription::where('tenant_id', $tenantId)
            ->where('event', $eventName)
            ->where('is_active', true)
            ->get();

        foreach ($subscribers as $subscriber) {
            $this->sendWithRetry($subscriber->url, $tenantId, $eventName, $data);
        }
    }

    private function sendWithRetry(string $url, int $tenantId, string $eventName, array $data): void
    {
        $payload = [
            'event'          => $eventName,
            'tenant_id'      => $tenantId,
            'data'           => $data,
            'timestamp'      => now()->toIso8601String(),
            'service_source' => 'inventory-service',
        ];

        $attempt = 0;

        while ($attempt < $this->retryAttempts) {
            $attempt++;

            try {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Content-Type'  => 'application/json',
                        'X-Event-Name'  => $eventName,
                        'X-Tenant-ID'   => $tenantId,
                    ])
                    ->post($url, $payload);

                if ($response->successful()) {
                    Log::info('Webhook dispatched successfully', [
                        'url'    => $url,
                        'event'  => $eventName,
                        'attempt'=> $attempt,
                    ]);

                    return;
                }

                Log::warning('Webhook delivery failed', [
                    'url'    => $url,
                    'event'  => $eventName,
                    'status' => $response->status(),
                    'attempt'=> $attempt,
                ]);
            } catch (\Throwable $e) {
                Log::error('Webhook dispatch exception', [
                    'url'     => $url,
                    'event'   => $eventName,
                    'attempt' => $attempt,
                    'error'   => $e->getMessage(),
                ]);
            }

            // Exponential back-off between retries
            if ($attempt < $this->retryAttempts) {
                sleep((int) pow(2, $attempt));
            }
        }

        Log::error('Webhook permanently failed after all retries', [
            'url'   => $url,
            'event' => $eventName,
        ]);
    }
}
