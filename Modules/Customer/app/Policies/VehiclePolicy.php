<?php

declare(strict_types=1);

namespace Modules\Customer\Policies;

use App\Core\Policies\BasePolicy;
use App\Models\User;
use Modules\Customer\Models\Vehicle;

/**
 * Vehicle Authorization Policy
 *
 * Tenant-aware authorization for vehicle management operations
 * Implements both RBAC and ABAC patterns
 */
class VehiclePolicy extends BasePolicy
{
    /**
     * Determine if user can view any vehicles
     */
    public function viewAny(User $user): bool
    {
        return $user->can('vehicle.list') || $user->can('vehicle.read');
    }

    /**
     * Determine if user can view specific vehicle
     */
    public function view(User $user, Vehicle $vehicle): bool
    {
        if ($user->can('vehicle.read')) {
            return $this->isSameTenant($user, $vehicle);
        }

        return false;
    }

    /**
     * Determine if user can create vehicles
     */
    public function create(User $user): bool
    {
        return $user->can('vehicle.create');
    }

    /**
     * Determine if user can update specific vehicle
     */
    public function update(User $user, Vehicle $vehicle): bool
    {
        if ($user->can('vehicle.update')) {
            return $this->isSameTenant($user, $vehicle);
        }

        return false;
    }

    /**
     * Determine if user can delete specific vehicle
     */
    public function delete(User $user, Vehicle $vehicle): bool
    {
        if ($user->can('vehicle.delete')) {
            return $this->isSameTenant($user, $vehicle);
        }

        return false;
    }

    /**
     * Determine if user can search vehicles
     */
    public function search(User $user): bool
    {
        return $user->can('vehicle.list') || $user->can('vehicle.read');
    }

    /**
     * Determine if user can view vehicles due for service
     */
    public function viewDueForService(User $user): bool
    {
        return $user->can('vehicle.list') || $user->can('vehicle.read');
    }

    /**
     * Determine if user can view vehicles with expiring insurance
     */
    public function viewExpiringInsurance(User $user): bool
    {
        return $user->can('vehicle.list') || $user->can('vehicle.read');
    }

    /**
     * Determine if user can update vehicle mileage
     */
    public function updateMileage(User $user, Vehicle $vehicle): bool
    {
        if ($user->can('vehicle.update')) {
            return $this->isSameTenant($user, $vehicle);
        }

        return false;
    }

    /**
     * Determine if user can transfer vehicle ownership
     */
    public function transferOwnership(User $user, Vehicle $vehicle): bool
    {
        if ($user->can('vehicle.transfer') || $user->can('vehicle.update')) {
            return $this->isSameTenant($user, $vehicle);
        }

        return false;
    }

    /**
     * Determine if user can view vehicle statistics
     */
    public function viewStatistics(User $user, Vehicle $vehicle): bool
    {
        if ($user->can('vehicle.read') || $user->can('vehicle.list')) {
            return $this->isSameTenant($user, $vehicle);
        }

        return false;
    }

    /**
     * Perform before authorization checks
     */
    public function before(User $user, string $ability): ?bool
    {
        // Super admins can do everything
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return null;
    }
}
