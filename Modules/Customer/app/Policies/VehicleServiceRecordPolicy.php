<?php

declare(strict_types=1);

namespace Modules\Customer\Policies;

use App\Core\Policies\BasePolicy;
use App\Models\User;
use Modules\Customer\Models\VehicleServiceRecord;

/**
 * Vehicle Service Record Policy
 *
 * Handles authorization for vehicle service record operations
 * Implements RBAC (Role-Based Access Control)
 */
class VehicleServiceRecordPolicy extends BasePolicy
{
    /**
     * Determine if the user can view any service records
     */
    public function viewAny(User $user): bool
    {
        // Super admin can always view
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Check if user has permission
        return $user->can('service-record.list');
    }

    /**
     * Determine if the user can view the service record
     */
    public function view(User $user, VehicleServiceRecord $serviceRecord): bool
    {
        // Super admin can always view
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Check if user has permission
        return $user->can('service-record.read');
    }

    /**
     * Determine if the user can create service records
     */
    public function create(User $user): bool
    {
        // Super admin can always create
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Check if user has permission
        return $user->can('service-record.create');
    }

    /**
     * Determine if the user can update the service record
     */
    public function update(User $user, VehicleServiceRecord $serviceRecord): bool
    {
        // Super admin can always update
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Check if user has permission
        if (! $user->can('service-record.update')) {
            return false;
        }

        // Additional business logic: Can't update completed services older than 7 days
        if ($serviceRecord->status === 'completed' && $serviceRecord->service_date->diffInDays(now()) > 7) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the user can delete the service record
     */
    public function delete(User $user, VehicleServiceRecord $serviceRecord): bool
    {
        // Super admin can always delete
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Check if user has permission
        if (! $user->can('service-record.delete')) {
            return false;
        }

        // Additional business logic: Can't delete completed services
        if ($serviceRecord->status === 'completed') {
            return false;
        }

        return true;
    }

    /**
     * Determine if the user can complete a service record
     */
    public function complete(User $user, VehicleServiceRecord $serviceRecord): bool
    {
        // Super admin can always complete
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Check if user has permission
        if (! $user->can('service-record.complete')) {
            return false;
        }

        // Can only complete pending or in-progress services
        return in_array($serviceRecord->status, ['pending', 'in_progress']);
    }

    /**
     * Determine if the user can cancel a service record
     */
    public function cancel(User $user, VehicleServiceRecord $serviceRecord): bool
    {
        // Super admin can always cancel
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Check if user has permission
        if (! $user->can('service-record.cancel')) {
            return false;
        }

        // Can't cancel already completed or cancelled services
        return ! in_array($serviceRecord->status, ['completed', 'cancelled']);
    }
}
