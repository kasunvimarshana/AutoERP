<?php

namespace App\Modules\Fleet\Services;

use App\Core\Services\BaseService;
use App\Modules\Fleet\Repositories\VehicleRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VehicleService extends BaseService
{
    /**
     * VehicleService constructor
     */
    public function __construct(VehicleRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Update vehicle mileage
     */
    public function updateMileage(int $vehicleId, int $mileage, ?string $notes = null): bool
    {
        DB::beginTransaction();

        try {
            $vehicle = $this->repository->findOrFail($vehicleId);

            if ($mileage < $vehicle->current_mileage) {
                throw new \Exception('New mileage cannot be less than current mileage');
            }

            $data = [
                'current_mileage' => $mileage,
                'last_mileage_update' => now(),
            ];

            if ($notes) {
                $data['mileage_notes'] = $notes;
            }

            $result = $this->repository->update($vehicleId, $data);
            DB::commit();

            Log::info("Vehicle {$vehicleId} mileage updated to {$mileage}");

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating vehicle mileage: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get active vehicles
     */
    public function getActive()
    {
        try {
            return $this->repository->getActive();
        } catch (\Exception $e) {
            Log::error('Error fetching active vehicles: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get vehicles by status
     */
    public function getByStatus(string $status)
    {
        try {
            return $this->repository->getByStatus($status);
        } catch (\Exception $e) {
            Log::error("Error fetching vehicles by status {$status}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get vehicles requiring maintenance
     */
    public function getRequiringMaintenance()
    {
        try {
            return $this->repository->getRequiringMaintenance();
        } catch (\Exception $e) {
            Log::error('Error fetching vehicles requiring maintenance: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get vehicle utilization report
     */
    public function getUtilizationReport(int $vehicleId): array
    {
        try {
            $vehicle = $this->repository->findOrFail($vehicleId);

            $totalMileage = $vehicle->current_mileage ?? 0;
            $acquisitionDate = $vehicle->acquisition_date ?? $vehicle->created_at;
            $daysInService = now()->diffInDays($acquisitionDate);
            $averageDailyMileage = $daysInService > 0 ? $totalMileage / $daysInService : 0;

            return [
                'vehicle_id' => $vehicleId,
                'vehicle_name' => $vehicle->name,
                'registration_number' => $vehicle->registration_number,
                'total_mileage' => $totalMileage,
                'days_in_service' => $daysInService,
                'average_daily_mileage' => round($averageDailyMileage, 2),
                'status' => $vehicle->status,
            ];
        } catch (\Exception $e) {
            Log::error("Error fetching utilization report for vehicle {$vehicleId}: ".$e->getMessage());
            throw $e;
        }
    }
}
