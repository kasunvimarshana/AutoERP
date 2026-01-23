<?php

namespace App\Modules\JobCardManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\JobCardManagement\Models\DigitalInspection;

class DigitalInspectionRepository extends BaseRepository
{
    public function __construct(DigitalInspection $model)
    {
        parent::__construct($model);
    }

    /**
     * Search digital inspections by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('inspection_number', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (!empty($criteria['job_card_id'])) {
            $query->where('job_card_id', $criteria['job_card_id']);
        }

        if (!empty($criteria['vehicle_id'])) {
            $query->where('vehicle_id', $criteria['vehicle_id']);
        }

        if (!empty($criteria['inspected_by'])) {
            $query->where('inspected_by', $criteria['inspected_by']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->with(['jobCard', 'vehicle', 'inspector'])
            ->orderBy('created_at', 'desc')
            ->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Find inspection by inspection number
     */
    public function findByInspectionNumber(string $inspectionNumber): ?DigitalInspection
    {
        return $this->model->where('inspection_number', $inspectionNumber)->first();
    }

    /**
     * Get inspections by status
     */
    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->with(['jobCard', 'vehicle'])->get();
    }

    /**
     * Get inspections for job card
     */
    public function getForJobCard(int $jobCardId)
    {
        return $this->model->where('job_card_id', $jobCardId)->with(['vehicle', 'inspector'])->get();
    }

    /**
     * Get inspections for vehicle
     */
    public function getForVehicle(int $vehicleId)
    {
        return $this->model->where('vehicle_id', $vehicleId)
            ->with(['jobCard', 'inspector'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get completed inspections
     */
    public function getCompleted()
    {
        return $this->model->where('status', 'completed')->with(['jobCard', 'vehicle'])->get();
    }

    /**
     * Get inspections by inspector
     */
    public function getByInspector(int $userId)
    {
        return $this->model->where('inspected_by', $userId)->with(['jobCard', 'vehicle'])->get();
    }
}
