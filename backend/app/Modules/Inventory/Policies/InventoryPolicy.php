<?php

namespace App\Modules\Inventory\Policies;

use App\Models\User;
use App\Modules\Inventory\Models\Inventory;
use Illuminate\Auth\Access\HandlesAuthorization;

class InventoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('inventory.view');
    }

    public function view(User $user, Inventory $inventory): bool
    {
        return $user->hasPermissionTo('inventory.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('inventory.create');
    }

    public function update(User $user, Inventory $inventory): bool
    {
        return $user->hasPermissionTo('inventory.update');
    }

    public function delete(User $user, Inventory $inventory): bool
    {
        return $user->hasPermissionTo('inventory.delete');
    }

    public function adjust(User $user): bool
    {
        return $user->hasPermissionTo('inventory.adjust');
    }

    public function transfer(User $user): bool
    {
        return $user->hasPermissionTo('inventory.transfer');
    }
}
