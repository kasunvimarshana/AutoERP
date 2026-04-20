<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Broadcasting\Channels;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Authorization logic for the presence organization-unit channel.
 *
 * Channel pattern: "presence-org.{orgUnitId}"
 *
 * Presence channels expose the list of currently subscribed users to all
 * members.  The callback must return either false (deny) or an array of
 * user metadata to include in the presence member list.
 */
final class PresenceOrgUnitChannel
{
    /**
     * Authorize the incoming presence-channel subscription.
     *
     * Returns user metadata visible to other channel members, or false to
     * reject the subscription.
     *
     * @param  int|string  $orgUnitId  Wildcard extracted from the channel name
     * @return array<string, mixed>|false
     */
    public function join(Authenticatable $user, int|string $orgUnitId): array|false
    {
        /** @var object{organization_unit_id?: int|string|null, name?: string} $user */
        if (
            ! isset($user->organization_unit_id)
            || (int) $user->organization_unit_id !== (int) $orgUnitId
        ) {
            return false;
        }

        return [
            'id' => $user->getAuthIdentifier(),
            'name' => property_exists($user, 'name') ? $user->name : '',
        ];
    }
}
