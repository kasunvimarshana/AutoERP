<?php

namespace App\Domain\Events;

class StockAdjusted
{
    public string $eventType = 'inventory.stock.adjusted';

    public function __construct(
        public readonly string $productId,
        public readonly string $warehouseId,
        public readonly float $oldQty,
        public readonly float $newQty,
        public readonly string $movementType,
        public readonly string $tenantId,
        public readonly ?string $referenceId   = null,
        public readonly ?string $referenceType = null,
        public readonly \DateTimeInterface $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function getNetChange(): float
    {
        return $this->newQty - $this->oldQty;
    }

    public function toArray(): array
    {
        return [
            'event_type'    => $this->eventType,
            'product_id'    => $this->productId,
            'warehouse_id'  => $this->warehouseId,
            'old_qty'       => $this->oldQty,
            'new_qty'       => $this->newQty,
            'net_change'    => $this->getNetChange(),
            'movement_type' => $this->movementType,
            'tenant_id'     => $this->tenantId,
            'reference_id'  => $this->referenceId,
            'reference_type'=> $this->referenceType,
            'occurred_at'   => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
