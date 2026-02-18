<?php

declare(strict_types=1);

namespace Modules\Core\Domain;

use Modules\Core\Events\DomainEvent;

/**
 * Aggregate Root
 *
 * An aggregate root is an entity that acts as the entry point to an aggregate
 * All domain events should be raised through aggregate roots
 */
abstract class AggregateRoot extends Entity
{
    /**
     * Domain events raised by this aggregate
     *
     * @var array<DomainEvent>
     */
    private array $domainEvents = [];

    /**
     * Raise a domain event
     */
    protected function raise(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * Get all raised domain events
     *
     * @return array<DomainEvent>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    /**
     * Check if aggregate has pending events
     */
    public function hasDomainEvents(): bool
    {
        return count($this->domainEvents) > 0;
    }

    /**
     * Clear all domain events
     */
    public function clearDomainEvents(): void
    {
        $this->domainEvents = [];
    }
}
