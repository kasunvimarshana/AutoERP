<?php

namespace App\Listeners;

use App\Models\Inventory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HandleProductDeleted implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'inventory';

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * Handle a product.deleted event payload from the message broker.
     * Soft-deletes all inventory records for the product across all warehouses.
     */
    public function handle(object $event): void
    {
        $payload = $this->extractPayload($event);

        if (! $payload) {
            return;
        }

        $tenantId  = $payload['tenant_id']  ?? null;
        $productId = $payload['product_id'] ?? $payload['id'] ?? null;

        if (! $tenantId || ! $productId) {
            Log::warning('HandleProductDeleted: missing tenant_id or product_id', ['payload' => $payload]);

            return;
        }

        try {
            $deleted = Inventory::where('tenant_id', $tenantId)
                                ->where('product_id', $productId)
                                ->delete();

            Log::info('HandleProductDeleted: inventory soft-deleted', [
                'tenant_id'  => $tenantId,
                'product_id' => $productId,
                'count'      => $deleted,
            ]);
        } catch (\Throwable $e) {
            Log::error('HandleProductDeleted failed', [
                'tenant_id'  => $tenantId,
                'product_id' => $productId,
                'error'      => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }

    private function extractPayload(object $event): ?array
    {
        if (property_exists($event, 'payload')) {
            return (array) $event->payload;
        }

        if (property_exists($event, 'data')) {
            return (array) $event->data;
        }

        return null;
    }
}
