<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Domain\Entities\User;

class UserUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly User $user) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('tenant.' . $this->user->tenantId)];
    }

    public function broadcastAs(): string
    {
        return 'UserUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'userId'   => $this->user->id,
            'tenantId' => $this->user->tenantId,
        ];
    }
}
