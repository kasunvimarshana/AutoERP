<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * We capture the essential fields before the model is removed from the DB.
     */
    public readonly int|string $userId;
    public readonly string     $userEmail;
    public readonly int|string|null $tenantId;

    public function __construct(User $user)
    {
        $this->userId    = $user->id;
        $this->userEmail = $user->email;
        $this->tenantId  = $user->tenant_id;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->tenantId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id'   => $this->userId,
            'email'     => $this->userEmail,
            'tenant_id' => $this->tenantId,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
