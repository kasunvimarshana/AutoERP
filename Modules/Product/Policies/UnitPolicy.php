<?php

declare(strict_types=1);

namespace Modules\Product\Policies;

use Modules\Auth\Models\User;
use Modules\Product\Models\Unit;

/**
 * Unit Policy
 */
class UnitPolicy
{
    /**
     * Determine if the user can view any units.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('units.view');
    }

    /**
     * Determine if the user can view the unit.
     */
    public function view(User $user, Unit $unit): bool
    {
        return $user->hasPermission('units.view')
            && $unit->tenant_id === $user->currentTenant()->id;
    }

    /**
     * Determine if the user can create units.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('units.create');
    }

    /**
     * Determine if the user can update the unit.
     */
    public function update(User $user, Unit $unit): bool
    {
        return $user->hasPermission('units.update')
            && $unit->tenant_id === $user->currentTenant()->id;
    }

    /**
     * Determine if the user can delete the unit.
     */
    public function delete(User $user, Unit $unit): bool
    {
        return $user->hasPermission('units.delete')
            && $unit->tenant_id === $user->currentTenant()->id;
    }
}
