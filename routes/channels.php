<?php

use Illuminate\Support\Facades\Broadcast;
use Modules\Core\Infrastructure\Broadcasting\Channels\OrgUnitChannel;
use Modules\Core\Infrastructure\Broadcasting\Channels\PresenceOrgUnitChannel;
use Modules\Core\Infrastructure\Broadcasting\Channels\PresenceTenantChannel;
use Modules\Core\Infrastructure\Broadcasting\Channels\TenantChannel;
use Modules\Core\Infrastructure\Broadcasting\Channels\UserChannel;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
| NOTE: Laravel strips the "private-" / "presence-" prefix from the channel
| name before matching against registered patterns here. Register patterns
| without those prefixes — e.g. "tenant.{tenantId}", not
| "private-tenant.{tenantId}".
|
*/

// Private channel scoped to a specific authenticated user.
Broadcast::channel('user.{userId}', [UserChannel::class, 'join']);

// Private channel scoped to a single tenant.
Broadcast::channel('tenant.{tenantId}', [TenantChannel::class, 'join']);

// Private channel scoped to a single organization unit.
Broadcast::channel('org.{orgUnitId}', [OrgUnitChannel::class, 'join']);

// Presence channel scoped to a single tenant — exposes the member list.
Broadcast::channel('presence-tenant.{tenantId}', [PresenceTenantChannel::class, 'join']);

// Presence channel scoped to a single organization unit — exposes the member list.
Broadcast::channel('presence-org.{orgUnitId}', [PresenceOrgUnitChannel::class, 'join']);

// Legacy default user-model channel (kept for compatibility).
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->getAuthIdentifier() === (int) $id;
});
