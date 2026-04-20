<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class BaseEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $tenantId;

    public ?int $orgUnitId;

    public function __construct(int $tenantId, ?int $orgUnitId = null)
    {
        $this->tenantId = $tenantId;
        $this->orgUnitId = $orgUnitId;
    }

    /**
     * Broadcast on private tenant and (optionally) private org-unit channels.
     *
     * Using PrivateChannel ensures subscribers must be authenticated and
     * authorized via the channel authorization callbacks in routes/channels.php.
     *
     * @return PrivateChannel[]
     */
    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('tenant.'.$this->tenantId)];

        if ($this->orgUnitId !== null) {
            $channels[] = new PrivateChannel('org.'.$this->orgUnitId);
        }

        return $channels;
    }

    /**
     * Derive the broadcast event name from the concrete class name.
     *
     * For example, Modules\Tenant\Domain\Events\TenantCreated broadcasts as
     * "TenantCreated". Subclasses may override this to use a custom name.
     */
    public function broadcastAs(): string
    {
        $parts = explode('\\', static::class);

        return end($parts);
    }

    /**
     * Base broadcast payload containing tenant and org-unit context.
     *
     * Subclasses should call parent::broadcastWith() and merge their own
     * domain-specific fields to ensure a consistent envelope.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'tenantId' => $this->tenantId,
            'orgUnitId' => $this->orgUnitId,
        ];
    }
}
