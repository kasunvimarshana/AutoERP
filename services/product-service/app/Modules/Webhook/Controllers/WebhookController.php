<?php

namespace App\Modules\Webhook\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
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
        Log::info('Webhook received', ['event' => $payload['event'] ?? 'unknown', 'payload' => $payload]);

        $this->processWebhook($payload);

        return response()->json(['message' => 'Webhook processed successfully.', 'received_at' => now()->toISOString()]);
    }

    private function processWebhook(array $payload): void
    {
        $event = $payload['event'] ?? '';
        Log::info("Processing webhook event: {$event}", $payload);
    }
}
