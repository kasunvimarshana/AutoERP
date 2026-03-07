<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserDeleted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param array<string, mixed> $userData Snapshot of the deleted user's data
     */
    public function __construct(
        public readonly int $userId,
        public readonly array $userData = []
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'event'     => 'user.deleted',
            'user_id'   => $this->userId,
            'user'      => $this->userData,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
