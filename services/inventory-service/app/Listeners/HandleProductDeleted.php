<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Repositories\Interfaces\InventoryRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

class HandleProductDeleted
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventoryRepository
    ) {}

    /**
     * Soft-delete all inventory records when a product is deleted.
     *
     * Expected payload keys: product_id
     *
     * @param  array<string, mixed> $payload
     */
    public function handle(array $payload): void
    {
        try {
            $productId = $payload['product_id'] ?? null;

            if ($productId === null) {
                Log::warning('HandleProductDeleted: missing product_id in payload', $payload);
                return;
            }

            $count = $this->inventoryRepository->deleteByProductId((int) $productId);

            Log::info('HandleProductDeleted: inventory records soft-deleted', [
                'product_id' => $productId,
                'count'      => $count,
            ]);
        } catch (Throwable $e) {
            Log::error('HandleProductDeleted: failed to delete inventory records', [
                'payload' => $payload,
                'error'   => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
