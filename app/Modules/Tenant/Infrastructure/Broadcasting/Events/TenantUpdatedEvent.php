<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Broadcasting\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TenantUpdatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly int $tenantId) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('tenant.'.$this->tenantId)];
    }

    public function broadcastAs(): string
    {
        return 'tenant.updated';
    }

    public function broadcastWith(): array
    {
        return ['tenant_id' => $this->tenantId];
    }
}
