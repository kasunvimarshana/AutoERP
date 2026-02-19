<?php

declare(strict_types=1);

namespace Modules\Notification\Policies;

use Modules\Auth\Models\User;
use Modules\Notification\Models\NotificationTemplate;

class NotificationTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('notification_templates.view');
    }

    public function view(User $user, NotificationTemplate $template): bool
    {
        return $user->hasPermission('notification_templates.view')
            && $user->tenant_id === $template->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('notification_templates.create');
    }

    public function update(User $user, NotificationTemplate $template): bool
    {
        return $user->hasPermission('notification_templates.update')
            && $user->tenant_id === $template->tenant_id
            && ! $template->is_system;
    }

    public function delete(User $user, NotificationTemplate $template): bool
    {
        return $user->hasPermission('notification_templates.delete')
            && $user->tenant_id === $template->tenant_id
            && ! $template->is_system;
    }

    public function restore(User $user, NotificationTemplate $template): bool
    {
        return $user->hasPermission('notification_templates.delete')
            && $user->tenant_id === $template->tenant_id
            && ! $template->is_system;
    }

    public function forceDelete(User $user, NotificationTemplate $template): bool
    {
        return $user->hasPermission('notification_templates.force_delete')
            && $user->tenant_id === $template->tenant_id
            && ! $template->is_system;
    }
}
