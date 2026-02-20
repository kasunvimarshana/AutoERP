<?php

namespace App\Events;

use App\Contracts\Events\DomainEventInterface;
use App\Models\StockMovement;
use DateTimeImmutable;

final class StockAdjusted implements DomainEventInterface
{
    private DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly StockMovement $movement
    ) {
        $this->occurredAt = new DateTimeImmutable;
    }

    public function getAggregateId(): string
    {
        return $this->movement->id;
    }

    public function getAggregateType(): string
    {
        return StockMovement::class;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function toArray(): array
    {
        return [
            'movement_id' => $this->movement->id,
            'product_id' => $this->movement->product_id,
            'warehouse_id' => $this->movement->warehouse_id,
            'movement_type' => $this->movement->movement_type,
            'quantity' => $this->movement->quantity,
            'tenant_id' => $this->movement->tenant_id,
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
