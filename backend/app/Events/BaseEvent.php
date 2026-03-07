<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class BaseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public readonly string $eventId;
    public readonly string $occurredAt;
    public readonly ?int   $tenantId;

    public function __construct()
    {
        $this->eventId    = \Illuminate\Support\Str::uuid()->toString();
        $this->occurredAt = now()->toIso8601String();
        $this->tenantId   = app()->bound('current_tenant') ? app('current_tenant')->id : null;
    }

    /**
     * Return the event type identifier used for routing / message broker topics.
     */
    abstract public function getEventType(): string;

    /**
     * Serialize event to array for message broker payload.
     */
    public function toPayload(): array
    {
        return [
            'event_id'    => $this->eventId,
            'event_type'  => $this->getEventType(),
            'occurred_at' => $this->occurredAt,
            'tenant_id'   => $this->tenantId,
            'data'        => $this->toData(),
        ];
    }

    /**
     * Override to provide event-specific data.
     */
    protected function toData(): array
    {
        return [];
    }
}
