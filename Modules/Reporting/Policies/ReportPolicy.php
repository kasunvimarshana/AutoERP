<?php

declare(strict_types=1);

namespace Modules\Reporting\Policies;

use Modules\Auth\Models\User;
use Modules\Reporting\Models\Report;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Report $report): bool
    {
        return $user->tenant_id === $report->tenant_id
            && ($report->is_shared || $user->id === $report->user_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('reports.create');
    }

    public function update(User $user, Report $report): bool
    {
        return $user->id === $report->user_id
            && $user->tenant_id === $report->tenant_id;
    }

    public function delete(User $user, Report $report): bool
    {
        return $user->id === $report->user_id
            && $user->tenant_id === $report->tenant_id;
    }

    public function restore(User $user, Report $report): bool
    {
        return $user->id === $report->user_id
            && $user->tenant_id === $report->tenant_id;
    }

    public function forceDelete(User $user, Report $report): bool
    {
        return $user->hasPermission('reports.force_delete')
            && $user->tenant_id === $report->tenant_id;
    }
}
