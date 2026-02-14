<?php

namespace App\Modules\CRM\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\CRM\Models\Vehicle;

/**
 * Vehicle Policy
 * 
 * Authorization policies for vehicle management operations
 * 
 * @package App\Modules\CRM\Policies
 */
class VehiclePolicy
{
    /**
     * Determine whether the user can view any vehicles
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('vehicles.view');
    }

    /**
     * Determine whether the user can view the vehicle
     *
     * @param User $user
     * @param Vehicle $vehicle
     * @return bool
     */
    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicles.view') && $user->tenant_id === $vehicle->tenant_id;
    }

    /**
     * Determine whether the user can create vehicles
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('vehicles.create');
    }

    /**
     * Determine whether the user can update the vehicle
     *
     * @param User $user
     * @param Vehicle $vehicle
     * @return bool
     */
    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicles.update') && $user->tenant_id === $vehicle->tenant_id;
    }

    /**
     * Determine whether the user can delete the vehicle
     *
     * @param User $user
     * @param Vehicle $vehicle
     * @return bool
     */
    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicles.delete') && $user->tenant_id === $vehicle->tenant_id;
    }

    /**
     * Determine whether the user can restore the vehicle
     *
     * @param User $user
     * @param Vehicle $vehicle
     * @return bool
     */
    public function restore(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicles.delete') && $user->tenant_id === $vehicle->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the vehicle
     *
     * @param User $user
     * @param Vehicle $vehicle
     * @return bool
     */
    public function forceDelete(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicles.delete') && $user->tenant_id === $vehicle->tenant_id;
    }
}
