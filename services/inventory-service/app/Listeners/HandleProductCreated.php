<?php

namespace App\Listeners;

use App\Models\Inventory;
use App\Models\Warehouse;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HandleProductCreated implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'inventory';

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * Handle a product.created event payload from the message broker.
     * Creates an initial zero-quantity inventory record in the default warehouse.
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
            Log::warning('HandleProductCreated: missing tenant_id or product_id', ['payload' => $payload]);

            return;
        }

        try {
            // Resolve the default warehouse for this tenant
            $warehouse = Warehouse::where('tenant_id', $tenantId)
                                  ->where('is_default', true)
                                  ->where('is_active', true)
                                  ->first();

            if (! $warehouse) {
                Log::info('HandleProductCreated: no default warehouse for tenant, skipping', [
                    'tenant_id'  => $tenantId,
                    'product_id' => $productId,
                ]);

                return;
            }

            // Avoid duplicates via firstOrCreate
            Inventory::firstOrCreate(
                [
                    'tenant_id'    => $tenantId,
                    'product_id'   => $productId,
                    'warehouse_id' => $warehouse->id,
                ],
                [
                    'quantity'          => 0,
                    'reserved_quantity' => 0,
                    'unit_cost'         => $payload['unit_cost'] ?? 0,
                ]
            );

            Log::info('HandleProductCreated: initial inventory created', [
                'tenant_id'    => $tenantId,
                'product_id'   => $productId,
                'warehouse_id' => $warehouse->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('HandleProductCreated failed', [
                'tenant_id'  => $tenantId,
                'product_id' => $productId,
                'error'      => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }

    private function extractPayload(object $event): ?array
    {
        // Support both event objects with a payload property and raw arrays
        if (property_exists($event, 'payload')) {
            return (array) $event->payload;
        }

        if (property_exists($event, 'data')) {
            return (array) $event->data;
        }

        return null;
    }
}
