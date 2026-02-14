<?php

namespace App\Modules\Fleet\Services;

use App\Core\BaseService;
use App\Modules\Fleet\Repositories\FleetVehicleRepository;

class FleetVehicleService extends BaseService
{
    protected FleetVehicleRepository $repository;

    public function __construct(FleetVehicleRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createFleetVehicle(array $data)
    {
        return $this->transaction(function () use ($data) {
            $vehicle = $this->repository->create($data);
            $this->logActivity('Fleet Vehicle created', ['vehicle_id' => $vehicle->id]);
            return $vehicle;
        });
    }

    public function updateFleetVehicle(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            $result = $this->repository->update($id, $data);
            if ($result) {
                $this->logActivity('Fleet Vehicle updated', ['vehicle_id' => $id]);
            }
            return $result;
        });
    }

    public function getFleetVehicle(int $id)
    {
        return $this->repository->with(['maintenanceRecords', 'tenant'])->find($id);
    }

    public function getAllFleetVehicles()
    {
        return $this->repository->with(['maintenanceRecords', 'tenant'])->all();
    }

    public function deleteFleetVehicle(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $result = $this->repository->delete($id);
            if ($result) {
                $this->logActivity('Fleet Vehicle deleted', ['vehicle_id' => $id]);
            }
            return $result;
        });
    }

    public function getByStatus(string $status)
    {
        return $this->repository->getByStatus($status);
    }

    public function getByFuelType(string $fuelType)
    {
        return $this->repository->getByFuelType($fuelType);
    }

    public function getByMake(string $make)
    {
        return $this->repository->getByMake($make);
    }

    public function getByYear(int $year)
    {
        return $this->repository->getByYear($year);
    }

    public function getServiceDue(?string $date = null)
    {
        return $this->repository->getServiceDue($date);
    }

    public function updateMileage(int $id, int $mileage): bool
    {
        return $this->transaction(function () use ($id, $mileage) {
            $result = $this->repository->update($id, ['mileage' => $mileage]);
            if ($result) {
                $this->logActivity('Vehicle mileage updated', [
                    'vehicle_id' => $id,
                    'mileage' => $mileage,
                ]);
            }
            return $result;
        });
    }

    public function updateStatus(int $id, string $status): bool
    {
        return $this->transaction(function () use ($id, $status) {
            $result = $this->repository->update($id, ['status' => $status]);
            if ($result) {
                $this->logActivity('Vehicle status updated', [
                    'vehicle_id' => $id,
                    'status' => $status,
                ]);
            }
            return $result;
        });
    }

    public function recordService(int $id, string $serviceDate, ?string $nextServiceDue = null): bool
    {
        return $this->transaction(function () use ($id, $serviceDate, $nextServiceDue) {
            $updateData = ['last_service_date' => $serviceDate];
            if ($nextServiceDue) {
                $updateData['next_service_due'] = $nextServiceDue;
            }
            
            $result = $this->repository->update($id, $updateData);
            if ($result) {
                $this->logActivity('Vehicle service recorded', [
                    'vehicle_id' => $id,
                    'service_date' => $serviceDate,
                    'next_service_due' => $nextServiceDue,
                ]);
            }
            return $result;
        });
    }

    public function getByTenantAndStatus(int $tenantId, string $status)
    {
        return $this->repository->getByTenantAndStatus($tenantId, $status);
    }

    public function getTotalMileage(int $tenantId)
    {
        return $this->repository->getTotalMileage($tenantId);
    }

    public function getVehicleCount(int $tenantId, ?string $status = null)
    {
        return $this->repository->getVehicleCount($tenantId, $status);
    }
}
