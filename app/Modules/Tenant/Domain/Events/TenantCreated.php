<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Tenant\Domain\Entities\Tenant;

class TenantCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Tenant $tenant) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('tenant.' . $this->tenant->id)];
    }

    public function broadcastAs(): string
    {
        return 'TenantCreated';
    }

    public function broadcastWith(): array
    {
        return ['tenantId' => $this->tenant->id];
    }
}
