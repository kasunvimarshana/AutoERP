<?php

namespace App\Modules\Inventory\Listeners;

use App\Modules\Inventory\DTOs\InventoryDTO;
use App\Modules\Inventory\Repositories\Interfaces\InventoryRepositoryInterface;
use Illuminate\Support\Facades\Log;

class HandleProductCreatedEvent
{
    public function __construct(private InventoryRepositoryInterface $inventoryRepository) {}

    public function handle(array $eventData): void
    {
        try {
            $productData = $eventData['data'] ?? [];
            $productId = $productData['id'] ?? null;
            $productSku = $productData['sku'] ?? null;

            if (!$productId || !$productSku) {
                Log::warning('Invalid ProductCreated event data', $eventData);
                return;
            }

            $existing = $this->inventoryRepository->findByProductId($productId);
            if ($existing) {
                Log::info("Inventory already exists for product {$productId}");
                return;
            }

            $dto = InventoryDTO::fromArray([
                'product_id' => $productId,
                'product_sku' => $productSku,
                'product_name' => $productData['name'] ?? 'Unknown',
                'quantity' => 0,
                'status' => 'out_of_stock',
            ]);

            $this->inventoryRepository->create($dto);
            Log::info("Created inventory item for product {$productId}");
        } catch (\Exception $e) {
            Log::error('Failed to handle ProductCreated event: ' . $e->getMessage());
        }
    }
}
