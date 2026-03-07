<?php

namespace App\Listeners;

use App\Jobs\ProcessProductCreatedJob;
use Illuminate\Support\Facades\Log;

/**
 * Dispatched by ConsumeRabbitMQMessages when a product.created message arrives.
 * Delegates to a queued job for ACID saga handling.
 */
class HandleProductCreatedEvent
{
    public function handle(array $payload): void
    {
        $tenantId  = (int) ($payload['tenant_id'] ?? 0);
        $productId = (int) ($payload['data']['id'] ?? 0);

        if (! $tenantId || ! $productId) {
            Log::warning('HandleProductCreatedEvent: missing tenant_id or product_id', $payload);

            return;
        }

        Log::info('Dispatching ProcessProductCreatedJob', [
            'tenant_id'  => $tenantId,
            'product_id' => $productId,
        ]);

        ProcessProductCreatedJob::dispatch(
            tenantId:  $tenantId,
            productId: $productId,
            payload:   $payload,
        );
    }
}
