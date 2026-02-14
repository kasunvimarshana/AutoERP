<?php

namespace App\Modules\Fleet\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Fleet\Models\MaintenanceRecord;

class MaintenanceRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('maintenance_records.view');
    }

    public function view(User $user, MaintenanceRecord $maintenanceRecord): bool
    {
        return $user->can('maintenance_records.view') && $user->tenant_id === $maintenanceRecord->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('maintenance_records.create');
    }

    public function update(User $user, MaintenanceRecord $maintenanceRecord): bool
    {
        return $user->can('maintenance_records.update') && $user->tenant_id === $maintenanceRecord->tenant_id;
    }

    public function delete(User $user, MaintenanceRecord $maintenanceRecord): bool
    {
        return $user->can('maintenance_records.delete') && $user->tenant_id === $maintenanceRecord->tenant_id;
    }

    public function restore(User $user, MaintenanceRecord $maintenanceRecord): bool
    {
        return $user->can('maintenance_records.delete') && $user->tenant_id === $maintenanceRecord->tenant_id;
    }

    public function forceDelete(User $user, MaintenanceRecord $maintenanceRecord): bool
    {
        return $user->can('maintenance_records.delete') && $user->tenant_id === $maintenanceRecord->tenant_id;
    }
}
