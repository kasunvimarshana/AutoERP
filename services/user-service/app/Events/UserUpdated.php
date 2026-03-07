<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param array<string, mixed> $originalData
     */
    public function __construct(
        public readonly User $user,
        public readonly array $originalData = []
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'event'     => 'user.updated',
            'user_id'   => $this->user->id,
            'user'      => [
                'id'          => $this->user->id,
                'keycloak_id' => $this->user->keycloak_id,
                'email'       => $this->user->email,
                'first_name'  => $this->user->first_name,
                'last_name'   => $this->user->last_name,
                'username'    => $this->user->username,
                'roles'       => $this->user->roles ?? [],
                'is_active'   => $this->user->is_active,
                'department'  => $this->user->department,
            ],
            'changes'   => $this->user->getChanges(),
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
