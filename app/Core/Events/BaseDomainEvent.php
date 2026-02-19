<?php

declare(strict_types=1);

namespace App\Core\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base Domain Event
 *
 * All domain events should extend this class.
 * Domain events represent something that happened in the business domain.
 *
 * Events are dispatched AFTER database transactions commit to ensure
 * listeners only process events for successfully persisted state changes.
 */
abstract class BaseDomainEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Timestamp when the event occurred
     */
    public readonly string $occurredAt;

    /**
     * User who triggered the event (if applicable)
     */
    public readonly ?int $userId;

    /**
     * Tenant context (if multi-tenant)
     */
    public readonly ?string $tenantId;

    /**
     * Create a new event instance
     */
    public function __construct()
    {
        $this->occurredAt = now()->toIso8601String();
        $this->userId = auth()->id();
        $this->tenantId = tenant('id');
    }

    /**
     * Get event name for logging
     */
    public function getEventName(): string
    {
        return class_basename($this);
    }

    /**
     * Get event payload for logging
     *
     * @return array<string, mixed>
     */
    abstract public function getEventPayload(): array;

    /**
     * Whether this event should be queued
     */
    public function shouldQueue(): bool
    {
        return true;
    }
}
