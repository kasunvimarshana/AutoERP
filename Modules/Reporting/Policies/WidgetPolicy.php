<?php

declare(strict_types=1);

namespace Modules\Reporting\Policies;

use Modules\Auth\Models\User;
use Modules\Reporting\Models\DashboardWidget;

class WidgetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DashboardWidget $widget): bool
    {
        return $user->tenant_id === $widget->tenant_id
            && ($widget->dashboard->is_shared || $user->id === $widget->dashboard->user_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('widgets.create');
    }

    public function update(User $user, DashboardWidget $widget): bool
    {
        return $user->id === $widget->dashboard->user_id
            && $user->tenant_id === $widget->tenant_id;
    }

    public function delete(User $user, DashboardWidget $widget): bool
    {
        return $user->id === $widget->dashboard->user_id
            && $user->tenant_id === $widget->tenant_id;
    }

    public function restore(User $user, DashboardWidget $widget): bool
    {
        return $user->id === $widget->dashboard->user_id
            && $user->tenant_id === $widget->tenant_id;
    }

    public function forceDelete(User $user, DashboardWidget $widget): bool
    {
        return $user->hasPermission('widgets.force_delete')
            && $user->tenant_id === $widget->tenant_id;
    }
}
