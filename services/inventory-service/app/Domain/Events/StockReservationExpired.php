<?php

namespace App\Domain\Events;

class StockReservationExpired
{
    public string $eventType = 'inventory.stock.reservation_expired';

    public function __construct(
        public readonly string $reservationId,
        public readonly string $productId,
        public readonly string $warehouseId,
        public readonly int $quantity,
        public readonly ?string $referenceId,
        public readonly ?string $referenceType,
        public readonly string $tenantId,
        public readonly \DateTimeInterface $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function toArray(): array
    {
        return [
            'event_type'     => $this->eventType,
            'reservation_id' => $this->reservationId,
            'product_id'     => $this->productId,
            'warehouse_id'   => $this->warehouseId,
            'quantity'       => $this->quantity,
            'reference_id'   => $this->referenceId,
            'reference_type' => $this->referenceType,
            'tenant_id'      => $this->tenantId,
            'occurred_at'    => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
