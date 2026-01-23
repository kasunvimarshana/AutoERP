<?php

namespace App\Modules\FleetManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\FleetManagement\Models\MaintenanceSchedule;

class MaintenanceScheduleRepository extends BaseRepository
{
    public function __construct(MaintenanceSchedule $model)
    {
        parent::__construct($model);
    }

    /**
     * Search maintenance schedules by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('schedule_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (!empty($criteria['schedule_type'])) {
            $query->where('schedule_type', $criteria['schedule_type']);
        }

        if (!empty($criteria['fleet_vehicle_id'])) {
            $query->where('fleet_vehicle_id', $criteria['fleet_vehicle_id']);
        }

        if (!empty($criteria['is_active'])) {
            $query->where('is_active', $criteria['is_active']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->with(['fleetVehicle'])
            ->orderBy('scheduled_date')
            ->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Get schedules by status
     */
    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->with(['fleetVehicle'])->get();
    }

    /**
     * Get schedules by type
     */
    public function getByType(string $type)
    {
        return $this->model->where('schedule_type', $type)->with(['fleetVehicle'])->get();
    }

    /**
     * Get schedules for fleet vehicle
     */
    public function getForFleetVehicle(int $fleetVehicleId)
    {
        return $this->model->where('fleet_vehicle_id', $fleetVehicleId)
            ->orderBy('scheduled_date')
            ->get();
    }

    /**
     * Get active schedules
     */
    public function getActive()
    {
        return $this->model->where('is_active', true)->with(['fleetVehicle'])->get();
    }

    /**
     * Get upcoming schedules
     */
    public function getUpcoming()
    {
        return $this->model->where('scheduled_date', '>=', now())
            ->where('status', '!=', 'completed')
            ->where('is_active', true)
            ->with(['fleetVehicle'])
            ->orderBy('scheduled_date')
            ->get();
    }

    /**
     * Get overdue schedules
     */
    public function getOverdue()
    {
        return $this->model->where('scheduled_date', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with(['fleetVehicle'])
            ->orderBy('scheduled_date')
            ->get();
    }

    /**
     * Get pending schedules
     */
    public function getPending()
    {
        return $this->model->where('status', 'pending')->with(['fleetVehicle'])->get();
    }

    /**
     * Get completed schedules
     */
    public function getCompleted()
    {
        return $this->model->where('status', 'completed')->with(['fleetVehicle'])->get();
    }

    /**
     * Get schedules by date range
     */
    public function getByDateRange($startDate, $endDate)
    {
        return $this->model->whereBetween('scheduled_date', [$startDate, $endDate])
            ->with(['fleetVehicle'])
            ->orderBy('scheduled_date')
            ->get();
    }
}
