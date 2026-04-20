<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Broadcasting\Channels;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Authorization logic for the private organization-unit channel.
 *
 * Channel pattern: "private-org.{orgUnitId}"
 *
 * A user may join an org-unit channel when the user's own
 * organization_unit_id matches the requested identifier.
 */
final class OrgUnitChannel
{
    /**
     * Authorize the incoming channel subscription.
     *
     * @param  int|string  $orgUnitId  Wildcard extracted from the channel name
     */
    public function join(Authenticatable $user, int|string $orgUnitId): bool
    {
        /** @var object{organization_unit_id?: int|string|null} $user */
        return isset($user->organization_unit_id)
            && (int) $user->organization_unit_id === (int) $orgUnitId;
    }
}
