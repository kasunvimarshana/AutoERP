<?php

declare(strict_types=1);

namespace Modules\Reporting\Policies;

use Modules\Auth\Models\User;
use Modules\Reporting\Models\Dashboard;

class DashboardPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Dashboard $dashboard): bool
    {
        return $user->tenant_id === $dashboard->tenant_id
            && ($dashboard->is_shared || $user->id === $dashboard->user_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('dashboards.create');
    }

    public function update(User $user, Dashboard $dashboard): bool
    {
        return $user->id === $dashboard->user_id
            && $user->tenant_id === $dashboard->tenant_id;
    }

    public function delete(User $user, Dashboard $dashboard): bool
    {
        return $user->id === $dashboard->user_id
            && $user->tenant_id === $dashboard->tenant_id;
    }

    public function restore(User $user, Dashboard $dashboard): bool
    {
        return $user->id === $dashboard->user_id
            && $user->tenant_id === $dashboard->tenant_id;
    }

    public function forceDelete(User $user, Dashboard $dashboard): bool
    {
        return $user->hasPermission('dashboards.force_delete')
            && $user->tenant_id === $dashboard->tenant_id;
    }
}
