<?php

namespace App\Events;

use App\Contracts\Events\DomainEventInterface;
use App\Models\Order;
use DateTimeImmutable;

final class OrderCreated implements DomainEventInterface
{
    private DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly Order $order
    ) {
        $this->occurredAt = new DateTimeImmutable;
    }

    public function getAggregateId(): string
    {
        return $this->order->id;
    }

    public function getAggregateType(): string
    {
        return Order::class;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'tenant_id' => $this->order->tenant_id,
            'status' => $this->order->status->value,
            'total' => $this->order->total,
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
