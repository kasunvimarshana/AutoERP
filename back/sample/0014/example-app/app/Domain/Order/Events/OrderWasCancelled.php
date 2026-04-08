<?php

namespace App\Domain\Order\Events;

use App\Domain\Order\ValueObjects\OrderId;
use App\Domain\Shared\Events\DomainEvent;

final class OrderWasCancelled extends DomainEvent
{
    public function __construct(
        public readonly OrderId $orderId,
        public readonly string  $reason,
    ) {
        parent::__construct();
    }

    public function toArray(): array
    {
        return [
            'event_id'    => $this->eventId,
            'event_name'  => $this->eventName(),
            'order_id'    => $this->orderId->value(),
            'reason'      => $this->reason,
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
