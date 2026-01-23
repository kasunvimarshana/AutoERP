<?php

namespace App\Modules\AppointmentManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\AppointmentManagement\Models\ServiceBay;

class ServiceBayRepository extends BaseRepository
{
    public function __construct(ServiceBay $model)
    {
        parent::__construct($model);
    }

    /**
     * Search service bays by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('bay_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (!empty($criteria['bay_type'])) {
            $query->where('bay_type', $criteria['bay_type']);
        }

        if (!empty($criteria['is_active'])) {
            $query->where('is_active', $criteria['is_active']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Find service bay by bay number
     */
    public function findByBayNumber(string $bayNumber): ?ServiceBay
    {
        return $this->model->where('bay_number', $bayNumber)->first();
    }

    /**
     * Get available service bays
     */
    public function getAvailable()
    {
        return $this->model->available()->get();
    }

    /**
     * Get service bays by type
     */
    public function getByType(string $type)
    {
        return $this->model->byType($type)->get();
    }

    /**
     * Get active service bays
     */
    public function getActive()
    {
        return $this->model->active()->get();
    }

    /**
     * Get service bays for tenant
     */
    public function getForTenant(int $tenantId)
    {
        return $this->model->forTenant($tenantId)->get();
    }
}
