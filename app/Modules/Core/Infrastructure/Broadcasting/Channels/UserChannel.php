<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Broadcasting\Channels;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Authorization logic for the private user channel.
 *
 * Channel pattern: "private-user.{userId}"
 *
 * A user may only subscribe to their own private user channel.
 */
final class UserChannel
{
    /**
     * Authorize the incoming channel subscription.
     *
     * @param  int|string  $userId  Wildcard extracted from the channel name
     */
    public function join(Authenticatable $user, int|string $userId): bool
    {
        return (int) $user->getAuthIdentifier() === (int) $userId;
    }
}
