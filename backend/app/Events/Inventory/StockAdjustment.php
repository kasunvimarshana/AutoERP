<?php

declare(strict_types=1);

namespace App\Events\Inventory;

use App\Core\Events\BaseEvent;

/**
 * Stock Adjustment Event
 * 
 * Dispatched when stock quantity is adjusted (reconciliation, damage, etc.)
 */
class StockAdjustment extends BaseEvent
{
    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly string $productSku,
        public readonly float $oldQuantity,
        public readonly float $newQuantity,
        public readonly int $locationId,
        public readonly string $adjustmentType, // 'increase', 'decrease', 'recount'
        public readonly string $reason,
        public readonly ?string $batchNumber = null,
        public readonly ?string $reference = null,
        int $tenantId,
        ?int $userId = null,
        ?array $metadata = null
    ) {
        parent::__construct($tenantId, $userId, $metadata);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'product_sku' => $this->productSku,
            'old_quantity' => $this->oldQuantity,
            'new_quantity' => $this->newQuantity,
            'adjustment' => $this->newQuantity - $this->oldQuantity,
            'location_id' => $this->locationId,
            'adjustment_type' => $this->adjustmentType,
            'reason' => $this->reason,
            'batch_number' => $this->batchNumber,
            'reference' => $this->reference,
        ]);
    }
}
