<?php

namespace App\Webhooks;

use App\DTOs\WebhookPayloadDTO;
use App\Models\WebhookSubscription;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class WebhookDispatcher
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout'         => config('services.webhook.timeout', 10),
            'connect_timeout' => 5,
        ]);
    }

    /**
     * Dispatch a webhook event to all active subscribers for the given tenant and event type.
     */
    public function dispatch(string $event, string $tenantId, array $data): void
    {
        $payload = WebhookPayloadDTO::make($event, $tenantId, $data);
        $secret  = config('services.webhook.secret', '');

        $subscriptions = WebhookSubscription::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where(function ($q) use ($event) {
                $q->whereJsonContains('events', $event)
                  ->orWhereJsonContains('events', '*');
            })
            ->get();

        foreach ($subscriptions as $subscription) {
            $this->deliverToSubscriber($subscription, $payload, $secret);
        }
    }

    private function deliverToSubscriber(
        WebhookSubscription $subscription,
        WebhookPayloadDTO $payload,
        string $secret
    ): void {
        $body      = $payload->toJson();
        $sigSecret = $subscription->secret ?? $secret;
        $signature = hash_hmac('sha256', $body, $sigSecret);

        try {
            $this->client->post($subscription->url, [
                'body'    => $body,
                'headers' => [
                    'Content-Type'          => 'application/json',
                    'X-Webhook-Event'       => $payload->event,
                    'X-Webhook-ID'          => $payload->webhookId,
                    'X-Webhook-Timestamp'   => $payload->timestamp,
                    'X-Webhook-Signature'   => "sha256={$signature}",
                ],
            ]);

            Log::info('Webhook delivered', [
                'event'       => $payload->event,
                'url'         => $subscription->url,
                'webhook_id'  => $payload->webhookId,
            ]);
        } catch (RequestException $e) {
            Log::error('Webhook delivery failed', [
                'event'      => $payload->event,
                'url'        => $subscription->url,
                'status'     => $e->getResponse()?->getStatusCode(),
                'error'      => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Webhook delivery error', [
                'event' => $payload->event,
                'url'   => $subscription->url,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
