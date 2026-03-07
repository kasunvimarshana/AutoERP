<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Webhooks\InventoryWebhookHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(
        private readonly InventoryWebhookHandler $handler,
    ) {}

    public function inventoryUpdated(Request $request): JsonResponse
    {
        // Validate webhook secret
        $secret = $request->header('X-Webhook-Secret');
        if ($secret !== config('services.auth_service.shared_secret')) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $this->handler->handle($request->all());

        return response()->json(['message' => 'Webhook processed.']);
    }
}
