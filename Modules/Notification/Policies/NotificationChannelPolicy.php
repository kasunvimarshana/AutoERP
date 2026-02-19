<?php

declare(strict_types=1);

namespace Modules\Notification\Policies;

use Modules\Auth\Models\User;
use Modules\Notification\Models\NotificationChannel;

class NotificationChannelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('notification_channels.view');
    }

    public function view(User $user, NotificationChannel $channel): bool
    {
        return $user->hasPermission('notification_channels.view')
            && $user->tenant_id === $channel->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('notification_channels.create');
    }

    public function update(User $user, NotificationChannel $channel): bool
    {
        return $user->hasPermission('notification_channels.update')
            && $user->tenant_id === $channel->tenant_id;
    }

    public function delete(User $user, NotificationChannel $channel): bool
    {
        return $user->hasPermission('notification_channels.delete')
            && $user->tenant_id === $channel->tenant_id;
    }

    public function restore(User $user, NotificationChannel $channel): bool
    {
        return $user->hasPermission('notification_channels.delete')
            && $user->tenant_id === $channel->tenant_id;
    }

    public function forceDelete(User $user, NotificationChannel $channel): bool
    {
        return $user->hasPermission('notification_channels.force_delete')
            && $user->tenant_id === $channel->tenant_id;
    }
}
