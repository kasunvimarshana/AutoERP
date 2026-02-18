<?php

declare(strict_types=1);

namespace Modules\Core\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base Domain Event
 *
 * All domain events should extend this class
 * Domain events represent something significant that happened in the domain
 */
abstract class DomainEvent
{
    use Dispatchable, SerializesModels;

    /**
     * The time the event occurred
     */
    protected \DateTimeImmutable $occurredAt;

    /**
     * Create a new domain event instance
     */
    public function __construct()
    {
        $this->occurredAt = new \DateTimeImmutable;
    }

    /**
     * Get the time when event occurred
     */
    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /**
     * Get event name for logging/debugging
     */
    public function eventName(): string
    {
        return class_basename($this);
    }

    /**
     * Get event data for logging
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;
}
