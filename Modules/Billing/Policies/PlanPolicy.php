<?php

declare(strict_types=1);

namespace Modules\Billing\Policies;

use Modules\Auth\Models\User;
use Modules\Billing\Models\Plan;

class PlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('billing.plans.view');
    }

    public function view(User $user, Plan $plan): bool
    {
        return $user->hasPermission('billing.plans.view')
            && $user->tenant_id === $plan->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('billing.plans.create');
    }

    public function update(User $user, Plan $plan): bool
    {
        return $user->hasPermission('billing.plans.update')
            && $user->tenant_id === $plan->tenant_id;
    }

    public function delete(User $user, Plan $plan): bool
    {
        return $user->hasPermission('billing.plans.delete')
            && $user->tenant_id === $plan->tenant_id;
    }
}
