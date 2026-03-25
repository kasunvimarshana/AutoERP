<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Broadcasting\Channels;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Authorization logic for the presence tenant channel.
 *
 * Channel pattern: "presence-tenant.{tenantId}"
 *
 * Presence channels expose the list of currently subscribed users to all
 * members.  The callback must return either false (deny) or an array of
 * user metadata to include in the presence member list.
 */
final class PresenceTenantChannel
{
    /**
     * Authorize the incoming presence-channel subscription.
     *
     * Returns user metadata visible to other channel members, or false to
     * reject the subscription.
     *
     * @param  Authenticatable  $user
     * @param  int|string  $tenantId  Wildcard extracted from the channel name
     * @return array<string, mixed>|false
     */
    public function join(Authenticatable $user, int|string $tenantId): array|false
    {
        /** @var object{tenant_id?: int|string|null, name?: string} $user */
        if (! isset($user->tenant_id) || (int) $user->tenant_id !== (int) $tenantId) {
            return false;
        }

        return [
            'id'   => $user->getAuthIdentifier(),
            'name' => property_exists($user, 'name') ? $user->name : '',
        ];
    }
}
