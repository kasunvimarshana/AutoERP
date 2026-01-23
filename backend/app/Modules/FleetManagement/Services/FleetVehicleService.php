<?php

namespace App\Modules\FleetManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\FleetManagement\Repositories\FleetVehicleRepository;
use Illuminate\Database\Eloquent\Model;

class FleetVehicleService extends BaseService
{
    public function __construct(FleetVehicleRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Get vehicles by fleet
     */
    public function getByFleet(int $fleetId)
    {
        return $this->repository->getByFleet($fleetId);
    }

    /**
     * Get active vehicles in fleet
     */
    public function getActiveByFleet(int $fleetId)
    {
        return $this->repository->getActiveByFleet($fleetId);
    }

    /**
     * Update vehicle status
     */
    public function updateStatus(int $fleetVehicleId, string $status): Model
    {
        return $this->update($fleetVehicleId, ['status' => $status]);
    }

    /**
     * Mark as primary vehicle
     */
    public function markAsPrimary(int $fleetVehicleId): Model
    {
        $fleetVehicle = $this->repository->findOrFail($fleetVehicleId);
        
        // Unmark other vehicles in the same fleet
        $this->repository->unmarkPrimaryInFleet($fleetVehicle->fleet_id);
        
        return $this->update($fleetVehicleId, ['is_primary' => true]);
    }

    /**
     * Update assignment
     */
    public function updateAssignment(int $fleetVehicleId, array $assignmentData): Model
    {
        return $this->update($fleetVehicleId, [
            'assigned_driver' => $assignmentData['driver'] ?? null,
            'assigned_department' => $assignmentData['department'] ?? null,
            'assigned_at' => now()
        ]);
    }

    /**
     * Record mileage
     */
    public function recordMileage(int $fleetVehicleId, int $mileage): Model
    {
        return $this->update($fleetVehicleId, [
            'current_mileage' => $mileage,
            'last_mileage_update' => now()
        ]);
    }
}
