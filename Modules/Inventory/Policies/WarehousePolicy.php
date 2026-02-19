<?php

declare(strict_types=1);

namespace Modules\Inventory\Policies;

use Modules\Auth\Models\User;
use Modules\Inventory\Models\Warehouse;

class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('warehouses.view');
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->hasPermission('warehouses.view')
            && $user->tenant_id === $warehouse->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('warehouses.create');
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->hasPermission('warehouses.update')
            && $user->tenant_id === $warehouse->tenant_id;
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->hasPermission('warehouses.delete')
            && $user->tenant_id === $warehouse->tenant_id;
    }

    public function restore(User $user, Warehouse $warehouse): bool
    {
        return $user->hasPermission('warehouses.delete')
            && $user->tenant_id === $warehouse->tenant_id;
    }

    public function forceDelete(User $user, Warehouse $warehouse): bool
    {
        return $user->hasPermission('warehouses.force_delete')
            && $user->tenant_id === $warehouse->tenant_id;
    }

    public function activate(User $user, Warehouse $warehouse): bool
    {
        return $user->hasPermission('warehouses.activate')
            && $user->tenant_id === $warehouse->tenant_id;
    }

    public function deactivate(User $user, Warehouse $warehouse): bool
    {
        return $user->hasPermission('warehouses.deactivate')
            && $user->tenant_id === $warehouse->tenant_id;
    }
}
