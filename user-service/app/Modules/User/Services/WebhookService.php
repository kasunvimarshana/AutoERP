<?php

namespace App\Modules\User\Services;

use App\Modules\User\DTOs\WebhookPayloadDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Dispatch a webhook.
     */
    public function dispatch(string $eventType, array $domainData): void
    {
        $payloadDTO = WebhookPayloadDTO::create($eventType, $domainData);
        $payload = $payloadDTO->toArray();

        // Endpoints that care about user lifecycle
        $endpoints = config('webhooks.subscribers', ['http://localhost:8002/api/webhooks']);

        foreach ($endpoints as $url) {
            try {
                $signature = hash_hmac('sha256', json_encode($payload), config('webhooks.secret', 'secret_key'));

                $response = Http::withHeaders([
                    'X-Webhook-Signature' => $signature,
                    'Content-Type' => 'application/json'
                ])->post($url, $payload);

                if ($response->successful()) {
                    Log::info("Webhook delivered: {$url} for {$eventType}");
                } else {
                    Log::error("Webhook failed: {$url} for {$eventType}");
                }
            } catch (\Exception $e) {
                Log::error("Webhook exception: {$url} - {$e->getMessage()}");
            }
        }
    }
}
