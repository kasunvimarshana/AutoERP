<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Broadcasting\Channels;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Authorization logic for the private tenant channel.
 *
 * Channel pattern: "private-tenant.{tenantId}"
 *
 * A user may join a tenant's channel only when the user's own tenant_id
 * matches the requested tenant identifier.
 */
final class TenantChannel
{
    /**
     * Authorize the incoming channel subscription.
     *
     * @param  Authenticatable  $user  The authenticated user
     * @param  int|string  $tenantId  Wildcard extracted from the channel name
     */
    public function join(Authenticatable $user, int|string $tenantId): bool
    {
        /** @var object{tenant_id?: int|string|null} $user */
        return isset($user->tenant_id) && (int) $user->tenant_id === (int) $tenantId;
    }
}
