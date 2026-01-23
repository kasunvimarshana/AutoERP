<?php

namespace App\Modules\CustomerManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\CustomerManagement\Models\Vehicle;

class VehicleRepository extends BaseRepository
{
    public function __construct(Vehicle $model)
    {
        parent::__construct($model);
    }

    /**
     * Search vehicles by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('vin', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%")
                    ->orWhere('make', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (!empty($criteria['customer_id'])) {
            $query->where('current_customer_id', $criteria['customer_id']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        if (!empty($criteria['service_due'])) {
            $query->serviceDue();
        }

        return $query->with('currentCustomer')->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Find vehicle by VIN
     */
    public function findByVin(string $vin): ?Vehicle
    {
        return $this->model->where('vin', $vin)->first();
    }

    /**
     * Find vehicle by registration number
     */
    public function findByRegistration(string $registration): ?Vehicle
    {
        return $this->model->where('registration_number', $registration)->first();
    }

    /**
     * Get vehicles due for service
     */
    public function getServiceDue()
    {
        return $this->model->serviceDue()->with('currentCustomer')->get();
    }

    /**
     * Update mileage and check service schedule
     */
    public function updateMileage(int $id, float $mileage): Vehicle
    {
        $vehicle = $this->findOrFail($id);
        $vehicle->current_mileage = $mileage;

        // Auto-calculate next service if needed
        if ($mileage >= $vehicle->next_service_mileage) {
            $vehicle->next_service_mileage = $vehicle->calculateNextServiceMileage();
        }

        $vehicle->save();
        return $vehicle->fresh();
    }
}
