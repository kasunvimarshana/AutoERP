<?php

namespace App\Modules\FleetManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\FleetManagement\Models\Fleet;

class FleetRepository extends BaseRepository
{
    public function __construct(Fleet $model)
    {
        parent::__construct($model);
    }

    /**
     * Search fleets by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('fleet_code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (!empty($criteria['customer_id'])) {
            $query->where('customer_id', $criteria['customer_id']);
        }

        if (!empty($criteria['is_active'])) {
            $query->where('is_active', $criteria['is_active']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->with(['customer', 'vehicles'])
            ->withCount('vehicles')
            ->orderBy('name')
            ->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Find fleet by fleet code
     */
    public function findByFleetCode(string $fleetCode): ?Fleet
    {
        return $this->model->where('fleet_code', $fleetCode)->first();
    }

    /**
     * Get active fleets
     */
    public function getActive()
    {
        return $this->model->where('is_active', true)->with(['customer', 'vehicles'])->get();
    }

    /**
     * Get fleets by status
     */
    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->with(['customer', 'vehicles'])->get();
    }

    /**
     * Get fleets for customer
     */
    public function getForCustomer(int $customerId)
    {
        return $this->model->where('customer_id', $customerId)
            ->with(['vehicles'])
            ->withCount('vehicles')
            ->get();
    }

    /**
     * Get fleets with vehicles
     */
    public function getWithVehicles()
    {
        return $this->model->has('vehicles')
            ->with(['customer', 'vehicles'])
            ->withCount('vehicles')
            ->get();
    }

    /**
     * Get fleet with vehicles
     */
    public function getFleetWithVehicles(int $id)
    {
        return $this->model->with(['customer', 'vehicles'])->findOrFail($id);
    }
}
