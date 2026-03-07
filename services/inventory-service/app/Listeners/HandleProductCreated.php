<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Repositories\Interfaces\InventoryRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

class HandleProductCreated
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventoryRepository
    ) {}

    /**
     * Create a default inventory record when a new product is created.
     *
     * Expected payload keys: product_id, product (with sku, name, etc.)
     *
     * @param  array<string, mixed> $payload
     */
    public function handle(array $payload): void
    {
        try {
            $productId = $payload['product_id'] ?? null;

            if ($productId === null) {
                Log::warning('HandleProductCreated: missing product_id in payload', $payload);
                return;
            }

            // Idempotency check – avoid duplicate records
            $existing = $this->inventoryRepository->findFirstByProductId((int) $productId);

            if ($existing !== null) {
                Log::info('HandleProductCreated: inventory already exists for product', [
                    'product_id'   => $productId,
                    'inventory_id' => $existing->id,
                ]);
                return;
            }

            $inventory = $this->inventoryRepository->create([
                'product_id'         => (int) $productId,
                'quantity'           => 0,
                'reserved_quantity'  => 0,
                'warehouse_location' => null,
                'reorder_level'      => 10,
                'reorder_quantity'   => 50,
                'unit_cost'          => 0.0,
                'status'             => 'active',
                'last_counted_at'    => now(),
            ]);

            Log::info('HandleProductCreated: inventory record created', [
                'product_id'   => $productId,
                'inventory_id' => $inventory->id,
            ]);
        } catch (Throwable $e) {
            Log::error('HandleProductCreated: failed to create inventory record', [
                'payload' => $payload,
                'error'   => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
