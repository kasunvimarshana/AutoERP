<?php

namespace App\Modules\Vehicle\Policies;

use App\Models\User;
use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Auth\Access\HandlesAuthorization;

class VehiclePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('vehicles.view');
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermissionTo('vehicles.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('vehicles.create');
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermissionTo('vehicles.update');
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermissionTo('vehicles.delete');
    }

    public function restore(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermissionTo('vehicles.restore');
    }
}
