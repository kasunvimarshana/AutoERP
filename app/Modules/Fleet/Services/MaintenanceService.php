<?php

namespace App\Modules\Fleet\Services;

use App\Core\Services\BaseService;
use App\Modules\Fleet\Repositories\MaintenanceRecordRepository;
use App\Modules\Fleet\Repositories\VehicleRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MaintenanceService extends BaseService
{
    protected VehicleRepository $vehicleRepository;

    /**
     * MaintenanceService constructor
     */
    public function __construct(
        MaintenanceRecordRepository $repository,
        VehicleRepository $vehicleRepository
    ) {
        $this->repository = $repository;
        $this->vehicleRepository = $vehicleRepository;
    }

    /**
     * Schedule next service
     */
    public function scheduleNextService(int $vehicleId, array $data): mixed
    {
        DB::beginTransaction();

        try {
            $vehicle = $this->vehicleRepository->findOrFail($vehicleId);

            $maintenanceData = [
                'vehicle_id' => $vehicleId,
                'maintenance_type' => $data['maintenance_type'],
                'maintenance_date' => $data['maintenance_date'],
                'scheduled_mileage' => $data['scheduled_mileage'] ?? null,
                'description' => $data['description'] ?? null,
                'estimated_cost' => $data['estimated_cost'] ?? null,
                'service_provider' => $data['service_provider'] ?? null,
                'status' => 'scheduled',
            ];

            $maintenance = $this->repository->create($maintenanceData);

            $vehicleUpdateData = [];
            if (isset($data['maintenance_date'])) {
                $vehicleUpdateData['next_maintenance_date'] = $data['maintenance_date'];
            }
            if (isset($data['scheduled_mileage'])) {
                $vehicleUpdateData['next_maintenance_mileage'] = $data['scheduled_mileage'];
            }

            if (! empty($vehicleUpdateData)) {
                $this->vehicleRepository->update($vehicleId, $vehicleUpdateData);
            }

            DB::commit();

            Log::info("Maintenance scheduled for vehicle {$vehicleId}");

            return $maintenance;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error scheduling maintenance: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Complete maintenance
     */
    public function completeMaintenance(int $maintenanceId, array $data): bool
    {
        DB::beginTransaction();

        try {
            $updateData = [
                'status' => 'completed',
                'completed_at' => now(),
                'cost' => $data['cost'] ?? null,
                'actual_mileage' => $data['actual_mileage'] ?? null,
                'completion_notes' => $data['completion_notes'] ?? null,
            ];

            $result = $this->repository->update($maintenanceId, $updateData);
            DB::commit();

            Log::info("Maintenance {$maintenanceId} marked as completed");

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error completing maintenance: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get maintenance history by vehicle
     */
    public function getByVehicle(int $vehicleId)
    {
        try {
            return $this->repository->getByVehicle($vehicleId);
        } catch (\Exception $e) {
            Log::error("Error fetching maintenance history for vehicle {$vehicleId}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get upcoming maintenance
     */
    public function getUpcoming()
    {
        try {
            return $this->repository->getUpcoming();
        } catch (\Exception $e) {
            Log::error('Error fetching upcoming maintenance: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get maintenance cost summary
     */
    public function getCostSummary(int $vehicleId): array
    {
        try {
            $totalCost = $this->repository->getTotalCostByVehicle($vehicleId);
            $maintenanceCount = $this->repository->getByVehicle($vehicleId)
                ->where('status', 'completed')
                ->count();

            $averageCost = $maintenanceCount > 0 ? $totalCost / $maintenanceCount : 0;

            return [
                'vehicle_id' => $vehicleId,
                'total_maintenance_cost' => $totalCost,
                'maintenance_count' => $maintenanceCount,
                'average_maintenance_cost' => round($averageCost, 2),
            ];
        } catch (\Exception $e) {
            Log::error("Error fetching maintenance cost summary for vehicle {$vehicleId}: ".$e->getMessage());
            throw $e;
        }
    }
}
