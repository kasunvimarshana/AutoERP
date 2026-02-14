<?php

namespace App\Modules\Fleet\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Fleet\Models\MaintenanceRecord;
use Illuminate\Database\Eloquent\Collection;

class MaintenanceRecordRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return MaintenanceRecord::class;
    }

    /**
     * Get records by vehicle
     */
    public function getByVehicle(int $vehicleId): Collection
    {
        return $this->model->where('vehicle_id', $vehicleId)
            ->orderBy('maintenance_date', 'desc')
            ->get();
    }

    /**
     * Get records by type
     */
    public function getByType(string $type): Collection
    {
        return $this->model->where('maintenance_type', $type)
            ->orderBy('maintenance_date', 'desc')
            ->get();
    }

    /**
     * Get records by date range
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model->whereBetween('maintenance_date', [$startDate, $endDate])
            ->orderBy('maintenance_date', 'desc')
            ->get();
    }

    /**
     * Get upcoming maintenance
     */
    public function getUpcoming(): Collection
    {
        return $this->model->where('status', 'scheduled')
            ->where('maintenance_date', '>=', now())
            ->orderBy('maintenance_date', 'asc')
            ->get();
    }

    /**
     * Get maintenance cost by vehicle
     */
    public function getTotalCostByVehicle(int $vehicleId): float
    {
        return $this->model->where('vehicle_id', $vehicleId)
            ->where('status', 'completed')
            ->sum('cost');
    }
}
