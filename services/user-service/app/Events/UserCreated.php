<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserCreated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly User $user) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.created';
    }

    public function broadcastWith(): array
    {
        return [
            'event'     => 'user.created',
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
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
