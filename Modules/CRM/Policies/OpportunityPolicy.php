<?php

declare(strict_types=1);

namespace Modules\CRM\Policies;

use Modules\Auth\Models\User;
use Modules\CRM\Models\Opportunity;

class OpportunityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('opportunities.view');
    }

    public function view(User $user, Opportunity $opportunity): bool
    {
        return $user->hasPermission('opportunities.view')
            && $user->tenant_id === $opportunity->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('opportunities.create');
    }

    public function update(User $user, Opportunity $opportunity): bool
    {
        return $user->hasPermission('opportunities.update')
            && $user->tenant_id === $opportunity->tenant_id;
    }

    public function delete(User $user, Opportunity $opportunity): bool
    {
        return $user->hasPermission('opportunities.delete')
            && $user->tenant_id === $opportunity->tenant_id;
    }

    public function advance(User $user, Opportunity $opportunity): bool
    {
        return $user->hasPermission('opportunities.update')
            && $user->tenant_id === $opportunity->tenant_id
            && ! $opportunity->isClosed();
    }

    public function close(User $user, Opportunity $opportunity): bool
    {
        return $user->hasPermission('opportunities.close')
            && $user->tenant_id === $opportunity->tenant_id
            && ! $opportunity->isClosed();
    }
}
