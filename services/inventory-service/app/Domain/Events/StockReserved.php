<?php

namespace App\Domain\Events;

class StockReserved
{
    public string $eventType = 'inventory.stock.reserved';

    public function __construct(
        public readonly string $productId,
        public readonly string $warehouseId,
        public readonly int $quantity,
        public readonly ?string $referenceId,
        public readonly ?string $referenceType,
        public readonly string $tenantId,
        public readonly ?string $reservationId = null,
        public readonly ?\DateTimeInterface $expiresAt   = null,
        public readonly \DateTimeInterface  $occurredAt  = new \DateTimeImmutable(),
    ) {
    }

    public function toArray(): array
    {
        return [
            'event_type'     => $this->eventType,
            'product_id'     => $this->productId,
            'warehouse_id'   => $this->warehouseId,
            'quantity'       => $this->quantity,
            'reference_id'   => $this->referenceId,
            'reference_type' => $this->referenceType,
            'tenant_id'      => $this->tenantId,
            'reservation_id' => $this->reservationId,
            'expires_at'     => $this->expiresAt?->format(\DateTimeInterface::ATOM),
            'occurred_at'    => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
