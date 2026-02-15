<?php

declare(strict_types=1);

namespace App\Core\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base Event Class
 * 
 * All application events should extend this class
 */
abstract class BaseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public readonly int $tenantId;
    public readonly ?\DateTimeInterface $occurredAt;
    public readonly ?int $userId;
    public readonly ?array $metadata;

    /**
     * Create a new event instance
     *
     * @param int $tenantId
     * @param int|null $userId
     * @param array|null $metadata
     */
    public function __construct(
        int $tenantId,
        ?int $userId = null,
        ?array $metadata = null
    ) {
        $this->tenantId = $tenantId;
        $this->userId = $userId;
        $this->metadata = $metadata;
        $this->occurredAt = new \DateTime();
    }

    /**
     * Get event name for logging and tracking
     *
     * @return string
     */
    public function getEventName(): string
    {
        return class_basename($this);
    }

    /**
     * Get event payload for logging
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'event' => $this->getEventName(),
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'occurred_at' => $this->occurredAt?->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Determine if this event should be queued
     *
     * @return bool
     */
    public function shouldQueue(): bool
    {
        return true;
    }
}
