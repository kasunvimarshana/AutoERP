<?php

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// User-specific private channel
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Tenant-wide notifications (all users in the tenant)
Broadcast::channel('tenant.{tenantId}', function ($user, $tenantId) {
    return $user->tenant_id === (int) $tenantId;
});

// Tenant notifications (authenticated users in tenant)
Broadcast::channel('tenant.{tenantId}.notifications', function ($user, $tenantId) {
    return $user->tenant_id === (int) $tenantId;
});

// Inventory notifications (users with inventory permissions)
Broadcast::channel('tenant.{tenantId}.inventory', function ($user, $tenantId) {
    return $user->tenant_id === (int) $tenantId && 
           $user->can('inventory.view');
});

// Stock alerts (inventory managers and admins)
Broadcast::channel('tenant.{tenantId}.stock-alerts', function ($user, $tenantId) {
    return $user->tenant_id === (int) $tenantId && 
           ($user->hasRole(['admin', 'inventory_manager']) || $user->can('inventory.stock.view'));
});

