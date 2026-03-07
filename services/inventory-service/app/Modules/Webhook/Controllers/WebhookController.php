<?php

namespace App\Modules\Webhook\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Listeners\HandleProductCreatedEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(private HandleProductCreatedEvent $productCreatedHandler) {}

    public function receive(Request $request): JsonResponse
    {
        $signature = $request->header('X-Webhook-Signature');
        $secret = config('services.webhook.secret');

        if ($secret) {
            $expectedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);
            if (!hash_equals($expectedSignature, (string) $signature)) {
                return response()->json(['message' => 'Invalid webhook signature.'], 401);
            }
        }

        $payload = $request->all();
        $event = $payload['event'] ?? '';

        Log::info("Webhook received: {$event}", ['payload' => $payload]);

        if ($event === 'ProductCreated') {
            $this->productCreatedHandler->handle($payload);
        }

        return response()->json([
            'message' => 'Webhook processed successfully.',
            'event' => $event,
            'received_at' => now()->toISOString(),
        ]);
    }
}
