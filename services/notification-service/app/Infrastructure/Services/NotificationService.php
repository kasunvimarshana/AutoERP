<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Domain\Contracts\NotificationServiceInterface;
use App\Domain\Entities\Notification;
use App\Domain\Entities\WebhookDelivery;
use App\Domain\Entities\WebhookSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Notification Service Implementation
 *
 * Handles event-driven notifications via email, webhooks, and other channels.
 */
class NotificationService implements NotificationServiceInterface
{
    public function send(string $event, array $payload, array $options = []): bool
    {
        $channel = $options['channel'] ?? 'webhook';
        $tenantId = $payload['tenant_id'] ?? null;

        $notification = Notification::create([
            'tenant_id' => $tenantId,
            'user_id' => $payload['user_id'] ?? null,
            'type' => $channel,
            'channel' => $channel,
            'event' => $event,
            'status' => 'pending',
            'payload' => $payload,
        ]);

        try {
            $success = match ($channel) {
                'webhook' => $this->dispatchWebhook($event, $tenantId, $payload) > 0,
                'email' => $this->sendEmail($event, $payload, $options),
                default => false,
            };

            $notification->update([
                'status' => $success ? 'sent' : 'failed',
                'sent_at' => now(),
            ]);

            return $success;
        } catch (\Exception $e) {
            $notification->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            report($e);
            return false;
        }
    }

    public function dispatchWebhook(string $event, int|string $tenantId, array $payload): int
    {
        $subscriptions = WebhookSubscription::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        $dispatched = 0;

        foreach ($subscriptions as $subscription) {
            if (!$subscription->handlesEvent($event)) {
                continue;
            }

            $delivery = WebhookDelivery::create([
                'webhook_subscription_id' => $subscription->id,
                'event' => $event,
                'payload' => $payload,
                'status' => 'pending',
                'attempts' => 0,
            ]);

            $this->deliverWebhook($subscription, $delivery, $payload, $event);
            $dispatched++;
        }

        return $dispatched;
    }

    public function registerWebhook(int|string $tenantId, array $data): WebhookSubscription
    {
        return WebhookSubscription::create([
            'tenant_id' => $tenantId,
            'url' => $data['url'],
            'events' => $data['events'] ?? ['*'],
            'secret' => $data['secret'] ?? null,
            'is_active' => true,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public function handleEvent(string $event, array $payload): void
    {
        Log::info("Notification service handling event: {$event}", $payload);

        $tenantId = $payload['tenant_id'] ?? null;

        if ($tenantId) {
            $this->dispatchWebhook($event, $tenantId, $payload);
        }

        // Route specific events to email
        if (in_array($event, ['order.created', 'order.cancelled'], true)) {
            $this->sendEmail($event, $payload, []);
        }
    }

    private function deliverWebhook(
        WebhookSubscription $subscription,
        WebhookDelivery $delivery,
        array $payload,
        string $event
    ): void {
        $attempts = 0;
        $maxAttempts = 3;

        while ($attempts < $maxAttempts) {
            $attempts++;

            try {
                $signature = $this->signPayload($payload, $subscription->secret ?? '');

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Event' => $event,
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Timestamp' => (string) time(),
                ])
                ->timeout(10)
                ->post($subscription->url, $payload);

                $delivery->update([
                    'response_code' => $response->status(),
                    'response_body' => substr($response->body(), 0, 500),
                    'attempts' => $attempts,
                    'status' => $response->successful() ? 'delivered' : 'failed',
                    'delivered_at' => $response->successful() ? now() : null,
                ]);

                if ($response->successful()) {
                    return;
                }
            } catch (\Exception $e) {
                $delivery->update([
                    'attempts' => $attempts,
                    'status' => 'failed',
                    'response_body' => $e->getMessage(),
                ]);
            }

            // Exponential backoff: 1s, 2s, 4s
            if ($attempts < $maxAttempts) {
                sleep((int) pow(2, $attempts - 1));
            }
        }
    }

    private function sendEmail(string $event, array $payload, array $options): bool
    {
        // Stub: In production, use Laravel Mail with blade templates
        Log::info("Email notification for event: {$event}", [
            'recipient' => $payload['user_id'] ?? 'unknown',
        ]);
        return true;
    }

    private function signPayload(array $payload, string $secret): string
    {
        return 'sha256=' . hash_hmac('sha256', (string) json_encode($payload), $secret);
    }
}
