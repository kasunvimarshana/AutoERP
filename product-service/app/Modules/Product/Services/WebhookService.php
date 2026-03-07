<?php

namespace App\Modules\Product\Services;

use App\Modules\Product\DTOs\WebhookPayloadDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Dispatch a webhook to configured endpoints using structured DTO payload.
     * In a production app, the HTTP call would be queued.
     * 
     * @param string $eventType e.g., 'product.created'
     * @param array $domainData The DTO array or Model attributes
     */
    public function dispatch(string $eventType, array $domainData): void
    {
        $payloadDTO = WebhookPayloadDTO::create($eventType, $domainData);
        $payload = $payloadDTO->toArray();

        // Simulate fetching configured endpoints from DB/Redis
        $endpoints = config('webhooks.subscribers', ['http://localhost:8001/api/webhooks']);

        foreach ($endpoints as $url) {
            try {
                // In a true environment, calculate HMAC signature for security payload.
                $signature = hash_hmac('sha256', json_encode($payload), config('webhooks.secret', 'secret_key'));

                $response = Http::withHeaders([
                    'X-Webhook-Signature' => $signature,
                    'Content-Type' => 'application/json'
                ])->post($url, $payload);

                if ($response->successful()) {
                    Log::info("Webhook delivered successfully to {$url} for {$eventType}", ['event_id' => $payloadDTO->eventId]);
                } else {
                    Log::error("Webhook delivery failed to {$url} for {$eventType}", [
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                }

            } catch (\Exception $e) {
                Log::error("Webhook exception to {$url} for {$eventType}", ['error' => $e->getMessage()]);
            }
        }
    }
}
