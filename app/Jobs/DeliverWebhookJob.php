<?php

namespace App\Jobs;

use App\Enums\WebhookDeliveryStatus;
use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeliverWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly string $deliveryId
    ) {}

    public function handle(): void
    {
        $delivery = WebhookDelivery::with('webhook')->find($this->deliveryId);

        if (! $delivery || ! $delivery->webhook || ! $delivery->webhook->is_active) {
            return;
        }

        $webhook = $delivery->webhook;
        $headers = array_merge(
            ['Content-Type' => 'application/json', 'X-Webhook-Event' => $delivery->event_name],
            $webhook->headers ?? []
        );

        if ($webhook->secret) {
            $signature = hash_hmac('sha256', json_encode($delivery->payload), $webhook->secret);
            $headers['X-Webhook-Signature'] = 'sha256='.$signature;
        }

        $delivery->increment('attempt_count');

        try {
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->post($webhook->url, $delivery->payload ?? []);

            if ($response->successful()) {
                $delivery->update([
                    'status' => WebhookDeliveryStatus::Success,
                    'response_status' => $response->status(),
                    'response_body' => null,
                    'delivered_at' => now(),
                ]);
            } else {
                $delivery->update([
                    'status' => WebhookDeliveryStatus::Failed,
                    'response_status' => $response->status(),
                    'response_body' => substr($response->body(), 0, 1000),
                ]);

                if ($this->attempts() < $this->tries) {
                    $this->release($this->backoff);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Webhook delivery failed', [
                'delivery_id' => $this->deliveryId,
                'error' => $e->getMessage(),
            ]);

            $delivery->update([
                'status' => WebhookDeliveryStatus::Failed,
                'response_body' => substr($e->getMessage(), 0, 1000),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff);
            }
        }
    }
}
