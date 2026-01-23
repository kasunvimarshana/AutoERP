<?php

namespace App\Modules\FleetManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\FleetManagement\Models\FleetVehicle;

class FleetVehicleRepository extends BaseRepository
{
    public function __construct(FleetVehicle $model)
    {
        parent::__construct($model);
    }

    /**
     * Search fleet vehicles by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('vehicle_number', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%")
                    ->orWhere('make', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (!empty($criteria['fleet_id'])) {
            $query->where('fleet_id', $criteria['fleet_id']);
        }

        if (!empty($criteria['vehicle_id'])) {
            $query->where('vehicle_id', $criteria['vehicle_id']);
        }

        if (!empty($criteria['is_active'])) {
            $query->where('is_active', $criteria['is_active']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->with(['fleet', 'vehicle'])
            ->orderBy('vehicle_number')
            ->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Get vehicles by status
     */
    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->with(['fleet', 'vehicle'])->get();
    }

    /**
     * Get vehicles for fleet
     */
    public function getForFleet(int $fleetId)
    {
        return $this->model->where('fleet_id', $fleetId)
            ->with(['vehicle'])
            ->orderBy('vehicle_number')
            ->get();
    }

    /**
     * Get active vehicles
     */
    public function getActive()
    {
        return $this->model->where('is_active', true)->with(['fleet', 'vehicle'])->get();
    }

    /**
     * Get active vehicles for fleet
     */
    public function getActiveForFleet(int $fleetId)
    {
        return $this->model->where('fleet_id', $fleetId)
            ->where('is_active', true)
            ->with(['vehicle'])
            ->get();
    }

    /**
     * Get vehicles due for service
     */
    public function getDueForService()
    {
        return $this->model->where('next_service_date', '<=', now()->addDays(30))
            ->where('is_active', true)
            ->with(['fleet', 'vehicle'])
            ->get();
    }

    /**
     * Get vehicles by registration
     */
    public function findByRegistration(string $registration): ?FleetVehicle
    {
        return $this->model->where('registration_number', $registration)->first();
    }
}
