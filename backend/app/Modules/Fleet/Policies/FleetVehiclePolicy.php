<?php

namespace App\Modules\Fleet\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Fleet\Models\FleetVehicle;

class FleetVehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('fleet_vehicles.view');
    }

    public function view(User $user, FleetVehicle $fleetVehicle): bool
    {
        return $user->can('fleet_vehicles.view') && $user->tenant_id === $fleetVehicle->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('fleet_vehicles.create');
    }

    public function update(User $user, FleetVehicle $fleetVehicle): bool
    {
        return $user->can('fleet_vehicles.update') && $user->tenant_id === $fleetVehicle->tenant_id;
    }

    public function delete(User $user, FleetVehicle $fleetVehicle): bool
    {
        return $user->can('fleet_vehicles.delete') && $user->tenant_id === $fleetVehicle->tenant_id;
    }

    public function restore(User $user, FleetVehicle $fleetVehicle): bool
    {
        return $user->can('fleet_vehicles.delete') && $user->tenant_id === $fleetVehicle->tenant_id;
    }

    public function forceDelete(User $user, FleetVehicle $fleetVehicle): bool
    {
        return $user->can('fleet_vehicles.delete') && $user->tenant_id === $fleetVehicle->tenant_id;
    }
}
