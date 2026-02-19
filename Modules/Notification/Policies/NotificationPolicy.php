<?php

declare(strict_types=1);

namespace Modules\Notification\Policies;

use Modules\Auth\Models\User;
use Modules\Notification\Models\Notification;

class NotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Notification $notification): bool
    {
        return $user->id === $notification->user_id
            && $user->tenant_id === $notification->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('notifications.create');
    }

    public function update(User $user, Notification $notification): bool
    {
        return $user->id === $notification->user_id
            && $user->tenant_id === $notification->tenant_id;
    }

    public function delete(User $user, Notification $notification): bool
    {
        return $user->id === $notification->user_id
            && $user->tenant_id === $notification->tenant_id;
    }

    public function restore(User $user, Notification $notification): bool
    {
        return $user->id === $notification->user_id
            && $user->tenant_id === $notification->tenant_id;
    }

    public function forceDelete(User $user, Notification $notification): bool
    {
        return $user->hasPermission('notifications.force_delete')
            && $user->tenant_id === $notification->tenant_id;
    }
}
