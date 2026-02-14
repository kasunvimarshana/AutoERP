<?php

namespace App\Modules\Fleet\Repositories;

use App\Core\BaseRepository;
use App\Modules\Fleet\Models\MaintenanceRecord;

class MaintenanceRecordRepository extends BaseRepository
{
    public function __construct(MaintenanceRecord $model)
    {
        parent::__construct($model);
    }

    public function getByTenant(int $tenantId)
    {
        return $this->model->where('tenant_id', $tenantId)->get();
    }

    public function getByFleetVehicle(int $fleetVehicleId)
    {
        return $this->model->where('fleet_vehicle_id', $fleetVehicleId)->get();
    }

    public function getByMaintenanceType(string $maintenanceType)
    {
        return $this->model->where('maintenance_type', $maintenanceType)->get();
    }

    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->get();
    }

    public function getByDateRange(string $startDate, string $endDate)
    {
        return $this->model
            ->whereBetween('service_date', [$startDate, $endDate])
            ->get();
    }

    public function getTotalCost(int $tenantId, ?string $startDate = null, ?string $endDate = null)
    {
        $query = $this->model->where('tenant_id', $tenantId);

        if ($startDate && $endDate) {
            $query->whereBetween('service_date', [$startDate, $endDate]);
        }

        return $query->sum('cost');
    }

    public function getCostByVehicle(int $fleetVehicleId, ?string $startDate = null, ?string $endDate = null)
    {
        $query = $this->model->where('fleet_vehicle_id', $fleetVehicleId);

        if ($startDate && $endDate) {
            $query->whereBetween('service_date', [$startDate, $endDate]);
        }

        return $query->sum('cost');
    }

    public function getCostByMaintenanceType(int $tenantId, ?string $startDate = null, ?string $endDate = null)
    {
        $query = $this->model->where('tenant_id', $tenantId);

        if ($startDate && $endDate) {
            $query->whereBetween('service_date', [$startDate, $endDate]);
        }

        return $query
            ->selectRaw('maintenance_type, SUM(cost) as total_cost')
            ->groupBy('maintenance_type')
            ->get();
    }

    public function getUpcomingMaintenance(?string $date = null)
    {
        $date = $date ?? now()->format('Y-m-d');
        return $this->model
            ->where('next_service_date', '<=', $date)
            ->where('status', 'completed')
            ->get();
    }

    public function getRecentMaintenance(int $fleetVehicleId, int $limit = 10)
    {
        return $this->model
            ->where('fleet_vehicle_id', $fleetVehicleId)
            ->orderBy('service_date', 'desc')
            ->limit($limit)
            ->get();
    }
}
