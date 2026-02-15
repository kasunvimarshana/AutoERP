<?php

declare(strict_types=1);

namespace App\Events\Inventory;

use App\Core\Events\BaseEvent;

/**
 * Stock Out Event
 * 
 * Dispatched when stock is issued/removed from inventory
 */
class StockOut extends BaseEvent
{
    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly string $productSku,
        public readonly float $quantity,
        public readonly int $locationId,
        public readonly ?string $batchNumber = null,
        public readonly ?string $serialNumber = null,
        public readonly ?string $reference = null,
        public readonly ?string $reason = null,
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
            'quantity' => $this->quantity,
            'location_id' => $this->locationId,
            'batch_number' => $this->batchNumber,
            'serial_number' => $this->serialNumber,
            'reference' => $this->reference,
            'reason' => $this->reason,
        ]);
    }
}
