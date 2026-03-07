<?php

namespace App\Webhooks;

use App\Models\WebhookSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookDispatcher
{
    public function dispatch(string $event, string $tenantId, array $data): void
    {
        $subscriptions = WebhookSubscription::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = [
            'id'        => (string) \Illuminate\Support\Str::uuid(),
            'event'     => $event,
            'tenant_id' => $tenantId,
            'data'      => $data,
            'timestamp' => now()->toIso8601String(),
        ];

        foreach ($subscriptions as $subscription) {
            if (! $subscription->isSubscribedTo($event)) {
                continue;
            }

            $this->send($subscription, $payload);
        }
    }

    private function send(WebhookSubscription $subscription, array $payload): void
    {
        $maxRetries = (int) config('app.webhook_max_retries', 3);
        $timeout    = (int) config('app.webhook_timeout', 10);

        $body      = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = $this->sign($body, $subscription->secret);

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::timeout($timeout)
                    ->withHeaders([
                        'Content-Type'        => 'application/json',
                        'X-Webhook-Signature' => $signature,
                        'X-Webhook-Event'     => $payload['event'],
                        'X-Webhook-ID'        => $payload['id'],
                    ])
                    ->post($subscription->url, $payload);

                if ($response->successful()) {
                    Log::info('Webhook delivered', [
                        'subscription_id' => $subscription->id,
                        'event'           => $payload['event'],
                        'url'             => $subscription->url,
                        'status'          => $response->status(),
                    ]);

                    return;
                }

                Log::warning('Webhook delivery non-2xx', [
                    'subscription_id' => $subscription->id,
                    'attempt'         => $attempt,
                    'status'          => $response->status(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Webhook delivery exception', [
                    'subscription_id' => $subscription->id,
                    'attempt'         => $attempt,
                    'error'           => $e->getMessage(),
                ]);
            }

            if ($attempt < $maxRetries) {
                sleep(2 ** $attempt); // exponential back-off: 2, 4 seconds
            }
        }

        Log::error('Webhook delivery failed after all retries', [
            'subscription_id' => $subscription->id,
            'event'           => $payload['event'],
        ]);
    }

    private function sign(string $body, ?string $secret): string
    {
        if (! $secret) {
            return '';
        }

        return 'sha256='.hash_hmac('sha256', $body, $secret);
    }
}
