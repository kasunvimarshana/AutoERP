<?php

namespace App\Modules\Fleet\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Fleet\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;

class VehicleRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return Vehicle::class;
    }

    /**
     * Get active vehicles
     */
    public function getActive(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Get vehicles by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Get vehicles by type
     */
    public function getByType(string $type): Collection
    {
        return $this->model->where('vehicle_type', $type)->get();
    }

    /**
     * Get vehicles by branch
     */
    public function getByBranch(int $branchId): Collection
    {
        return $this->model->where('branch_id', $branchId)->get();
    }

    /**
     * Get vehicles requiring maintenance
     */
    public function getRequiringMaintenance(): Collection
    {
        return $this->model->where('next_maintenance_date', '<=', now())
            ->orWhereColumn('current_mileage', '>=', 'next_maintenance_mileage')
            ->get();
    }
}
