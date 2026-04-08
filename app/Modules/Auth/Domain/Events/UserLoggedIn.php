<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class UserLoggedIn implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly int $tenantId,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("tenant.{$this->tenantId}")];
    }

    public function broadcastAs(): string
    {
        return 'UserLoggedIn';
    }

    public function broadcastWith(): array
    {
        return [
            'userId'   => $this->userId,
            'tenantId' => $this->tenantId,
        ];
    }
}
