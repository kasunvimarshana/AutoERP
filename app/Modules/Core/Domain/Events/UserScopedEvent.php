<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Abstract base for events that broadcast on the private user channel.
 *
 * Extend this class for events that are scoped to a single authenticated user
 * rather than a tenant, e.g. login / logout / session events.
 *
 * Channel pattern: "private-user.{userId}" — authorization is enforced
 * by UserChannel via the route registered in routes/channels.php.
 */
abstract class UserScopedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    /**
     * Broadcast on the authenticated user's private channel.
     *
     * @return PrivateChannel[]
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.'.$this->userId)];
    }

    /**
     * Derive the broadcast event name from the concrete class short name.
     */
    public function broadcastAs(): string
    {
        $parts = explode('\\', static::class);

        return end($parts);
    }

    /**
     * Base broadcast payload containing the user identifier.
     *
     * Subclasses should call parent::broadcastWith() and merge their own
     * domain-specific fields to ensure a consistent envelope.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return ['userId' => $this->userId];
    }
}
