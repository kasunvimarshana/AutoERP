<?php

namespace App\Listeners;

use App\Repositories\Interfaces\InventoryRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * Dispatched by ConsumeRabbitMQMessages when a product.deleted message arrives.
 * Soft-deletes all inventory records associated with the product.
 */
class HandleProductDeletedEvent
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventoryRepository,
    ) {}

    public function handle(array $payload): void
    {
        $tenantId  = (int) ($payload['tenant_id'] ?? 0);
        $productId = (int) ($payload['data']['id'] ?? 0);

        if (! $tenantId || ! $productId) {
            Log::warning('HandleProductDeletedEvent: missing tenant_id or product_id', $payload);

            return;
        }

        $deleted = $this->inventoryRepository->deleteByProduct($productId, $tenantId);

        Log::info('Soft-deleted inventory items for deleted product', [
            'tenant_id'  => $tenantId,
            'product_id' => $productId,
            'deleted'    => $deleted,
        ]);
    }
}
