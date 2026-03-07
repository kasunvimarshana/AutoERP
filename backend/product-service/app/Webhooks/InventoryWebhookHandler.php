<?php

namespace App\Webhooks;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InventoryWebhookHandler
{
    public function handle(array $payload): void
    {
        Log::info('InventoryWebhookHandler: received', ['payload' => $payload]);

        $productId = $payload['product_id'] ?? null;

        if ($productId) {
            // Invalidate any cached inventory data for this product
            Cache::forget("product_inventory_{$productId}");
        }
    }
}
