<?php

namespace App\Modules\Fleet\Repositories;

use App\Core\BaseRepository;
use App\Modules\Fleet\Models\FleetVehicle;

class FleetVehicleRepository extends BaseRepository
{
    public function __construct(FleetVehicle $model)
    {
        parent::__construct($model);
    }

    public function findByVehicleNumber(string $vehicleNumber): ?FleetVehicle
    {
        return $this->findBy('vehicle_number', $vehicleNumber);
    }

    public function findByVin(string $vin): ?FleetVehicle
    {
        return $this->findBy('vin', $vin);
    }

    public function findByLicensePlate(string $licensePlate): ?FleetVehicle
    {
        return $this->findBy('license_plate', $licensePlate);
    }

    public function getByTenant(int $tenantId)
    {
        return $this->model->where('tenant_id', $tenantId)->get();
    }

    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->get();
    }

    public function getByFuelType(string $fuelType)
    {
        return $this->model->where('fuel_type', $fuelType)->get();
    }

    public function getByMake(string $make)
    {
        return $this->model->where('make', $make)->get();
    }

    public function getByYear(int $year)
    {
        return $this->model->where('year', $year)->get();
    }

    public function getServiceDue(?string $date = null)
    {
        $date = $date ?? now()->format('Y-m-d');
        return $this->model
            ->where('next_service_due', '<=', $date)
            ->where('status', 'active')
            ->get();
    }

    public function getByTenantAndStatus(int $tenantId, string $status)
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('status', $status)
            ->get();
    }

    public function getTotalMileage(int $tenantId)
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->sum('mileage');
    }

    public function getVehicleCount(int $tenantId, ?string $status = null)
    {
        $query = $this->model->where('tenant_id', $tenantId);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        return $query->count();
    }
}
