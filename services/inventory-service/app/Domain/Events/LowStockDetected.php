<?php

namespace App\Domain\Events;

class LowStockDetected
{
    public string $eventType = 'inventory.stock.low_stock_detected';

    public function __construct(
        public readonly string $productId,
        public readonly string $warehouseId,
        public readonly float $quantity,
        public readonly int $reorderPoint,
        public readonly string $tenantId,
        public readonly \DateTimeInterface $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function toArray(): array
    {
        return [
            'event_type'   => $this->eventType,
            'product_id'   => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'quantity'     => $this->quantity,
            'reorder_point'=> $this->reorderPoint,
            'shortage'     => max(0, $this->reorderPoint - $this->quantity),
            'tenant_id'    => $this->tenantId,
            'occurred_at'  => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
