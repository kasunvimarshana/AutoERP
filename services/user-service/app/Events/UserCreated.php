<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly User $user) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->user->tenant_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.created';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id'   => $this->user->id,
            'name'      => $this->user->name,
            'email'     => $this->user->email,
            'tenant_id' => $this->user->tenant_id,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
