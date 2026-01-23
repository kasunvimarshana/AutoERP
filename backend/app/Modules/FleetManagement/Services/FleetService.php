<?php

namespace App\Modules\FleetManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\FleetManagement\Events\FleetCreated;
use App\Modules\FleetManagement\Repositories\FleetRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FleetService extends BaseService
{
    public function __construct(FleetRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * After fleet creation hook
     */
    protected function afterCreate(Model $fleet, array $data): void
    {
        event(new FleetCreated($fleet));
    }

    /**
     * Add vehicle to fleet
     */
    public function addVehicle(int $fleetId, int $vehicleId): Model
    {
        try {
            DB::beginTransaction();

            app(\App\Modules\FleetManagement\Repositories\FleetVehicleRepository::class)->create([
                'fleet_id' => $fleetId,
                'vehicle_id' => $vehicleId,
                'added_at' => now(),
                'status' => 'active'
            ]);

            $fleet = $this->repository->findOrFail($fleetId);
            $fleet->total_vehicles = $fleet->vehicles->count();
            $fleet->save();

            DB::commit();

            return $fleet;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove vehicle from fleet
     */
    public function removeVehicle(int $fleetId, int $vehicleId): Model
    {
        try {
            DB::beginTransaction();

            $fleetVehicle = app(\App\Modules\FleetManagement\Repositories\FleetVehicleRepository::class)
                ->findByFleetAndVehicle($fleetId, $vehicleId);

            if ($fleetVehicle) {
                $fleetVehicle->status = 'removed';
                $fleetVehicle->removed_at = now();
                $fleetVehicle->save();
            }

            $fleet = $this->repository->findOrFail($fleetId);
            $fleet->total_vehicles = $fleet->vehicles()->where('status', 'active')->count();
            $fleet->save();

            DB::commit();

            return $fleet;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get active fleets
     */
    public function getActive()
    {
        return $this->repository->getActive();
    }

    /**
     * Get fleets by customer
     */
    public function getByCustomer(int $customerId)
    {
        return $this->repository->getByCustomer($customerId);
    }

    /**
     * Calculate fleet statistics
     */
    public function getStatistics(int $fleetId): array
    {
        $fleet = $this->repository->findOrFail($fleetId);
        $vehicles = $fleet->vehicles()->where('status', 'active')->get();

        return [
            'total_vehicles' => $vehicles->count(),
            'active_vehicles' => $vehicles->where('status', 'active')->count(),
            'maintenance_due' => $vehicles->where('maintenance_status', 'due')->count(),
            'average_age' => $vehicles->avg('vehicle_age'),
            'total_mileage' => $vehicles->sum('odometer_reading')
        ];
    }

    /**
     * Get vehicles needing maintenance
     */
    public function getVehiclesNeedingMaintenance(int $fleetId)
    {
        return $this->repository->getVehiclesNeedingMaintenance($fleetId);
    }

    /**
     * Update fleet status
     */
    public function updateStatus(int $fleetId, string $status): Model
    {
        return $this->update($fleetId, ['status' => $status]);
    }
}
