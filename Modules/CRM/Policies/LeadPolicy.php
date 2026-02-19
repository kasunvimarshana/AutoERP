<?php

declare(strict_types=1);

namespace Modules\CRM\Policies;

use Modules\Auth\Models\User;
use Modules\CRM\Models\Lead;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('leads.view');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->hasPermission('leads.view')
            && $user->tenant_id === $lead->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('leads.create');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->hasPermission('leads.update')
            && $user->tenant_id === $lead->tenant_id;
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->hasPermission('leads.delete')
            && $user->tenant_id === $lead->tenant_id;
    }

    public function convert(User $user, Lead $lead): bool
    {
        return $user->hasPermission('leads.convert')
            && $user->tenant_id === $lead->tenant_id
            && ! $lead->isConverted();
    }

    public function assign(User $user, Lead $lead): bool
    {
        return $user->hasPermission('leads.assign')
            && $user->tenant_id === $lead->tenant_id;
    }
}
