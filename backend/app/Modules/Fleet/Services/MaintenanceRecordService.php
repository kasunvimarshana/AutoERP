<?php

namespace App\Modules\Fleet\Services;

use App\Core\BaseService;
use App\Modules\Fleet\Repositories\MaintenanceRecordRepository;
use App\Modules\Fleet\Repositories\FleetVehicleRepository;

class MaintenanceRecordService extends BaseService
{
    protected MaintenanceRecordRepository $repository;
    protected FleetVehicleRepository $vehicleRepository;

    public function __construct(
        MaintenanceRecordRepository $repository,
        FleetVehicleRepository $vehicleRepository
    ) {
        $this->repository = $repository;
        $this->vehicleRepository = $vehicleRepository;
    }

    public function createMaintenanceRecord(array $data)
    {
        return $this->transaction(function () use ($data) {
            $record = $this->repository->create($data);
            
            $vehicle = $this->vehicleRepository->find($data['fleet_vehicle_id']);
            if ($vehicle) {
                $vehicleUpdateData = [
                    'last_service_date' => $data['service_date'],
                    'mileage' => $data['mileage_at_service'] ?? $vehicle->mileage,
                ];
                
                if (isset($data['next_service_date'])) {
                    $vehicleUpdateData['next_service_due'] = $data['next_service_date'];
                }
                
                $this->vehicleRepository->update($vehicle->id, $vehicleUpdateData);
            }
            
            $this->logActivity('Maintenance Record created', ['record_id' => $record->id]);
            return $record;
        });
    }

    public function updateMaintenanceRecord(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            $result = $this->repository->update($id, $data);
            if ($result) {
                $this->logActivity('Maintenance Record updated', ['record_id' => $id]);
            }
            return $result;
        });
    }

    public function getMaintenanceRecord(int $id)
    {
        return $this->repository->with(['fleetVehicle', 'tenant'])->find($id);
    }

    public function getAllMaintenanceRecords()
    {
        return $this->repository->with(['fleetVehicle', 'tenant'])->all();
    }

    public function deleteMaintenanceRecord(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $result = $this->repository->delete($id);
            if ($result) {
                $this->logActivity('Maintenance Record deleted', ['record_id' => $id]);
            }
            return $result;
        });
    }

    public function getByFleetVehicle(int $fleetVehicleId)
    {
        return $this->repository->getByFleetVehicle($fleetVehicleId);
    }

    public function getByMaintenanceType(string $maintenanceType)
    {
        return $this->repository->getByMaintenanceType($maintenanceType);
    }

    public function getByStatus(string $status)
    {
        return $this->repository->getByStatus($status);
    }

    public function getByDateRange(string $startDate, string $endDate)
    {
        return $this->repository->getByDateRange($startDate, $endDate);
    }

    public function getTotalCost(int $tenantId, ?string $startDate = null, ?string $endDate = null)
    {
        return $this->repository->getTotalCost($tenantId, $startDate, $endDate);
    }

    public function getCostByVehicle(int $fleetVehicleId, ?string $startDate = null, ?string $endDate = null)
    {
        return $this->repository->getCostByVehicle($fleetVehicleId, $startDate, $endDate);
    }

    public function getCostByMaintenanceType(int $tenantId, ?string $startDate = null, ?string $endDate = null)
    {
        return $this->repository->getCostByMaintenanceType($tenantId, $startDate, $endDate);
    }

    public function getUpcomingMaintenance(?string $date = null)
    {
        return $this->repository->getUpcomingMaintenance($date);
    }

    public function getRecentMaintenance(int $fleetVehicleId, int $limit = 10)
    {
        return $this->repository->getRecentMaintenance($fleetVehicleId, $limit);
    }

    public function completeMaintenanceRecord(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $result = $this->repository->update($id, ['status' => 'completed']);
            if ($result) {
                $this->logActivity('Maintenance Record completed', ['record_id' => $id]);
            }
            return $result;
        });
    }

    public function cancelMaintenanceRecord(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $result = $this->repository->update($id, ['status' => 'cancelled']);
            if ($result) {
                $this->logActivity('Maintenance Record cancelled', ['record_id' => $id]);
            }
            return $result;
        });
    }
}
