<?php

declare(strict_types=1);

namespace KvSaas\Contracts\Events;

/**
 * DomainEvent
 *
 * Base value object for all domain events published to the message broker.
 * Events are immutable; create new instances instead of mutating.
 */
abstract class DomainEvent
{
    public readonly string $eventId;
    public readonly string $occurredAt;
    public readonly string $version;

    public function __construct(
        public readonly string     $aggregateId,
        public readonly string     $aggregateType,
        public readonly string|int $tenantId,
        string                     $version = '1.0',
    ) {
        $this->eventId    = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->occurredAt = now()->toIso8601String();
        $this->version    = $version;
    }

    /**
     * Return the event type/name (used as message routing key).
     *
     * @return string  e.g. "order.created", "inventory.reserved"
     */
    abstract public function eventType(): string;

    /**
     * Serialise the event payload to an array for the message broker.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event_id'       => $this->eventId,
            'event_type'     => $this->eventType(),
            'aggregate_id'   => $this->aggregateId,
            'aggregate_type' => $this->aggregateType,
            'tenant_id'      => $this->tenantId,
            'occurred_at'    => $this->occurredAt,
            'version'        => $this->version,
            'payload'        => $this->payload(),
        ];
    }

    /**
     * Domain-event–specific payload.
     *
     * @return array<string, mixed>
     */
    abstract protected function payload(): array;
}
