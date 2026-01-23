<?php

namespace App\Modules\JobCardManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\JobCardManagement\Models\JobCard;

class JobCardRepository extends BaseRepository
{
    public function __construct(JobCard $model)
    {
        parent::__construct($model);
    }

    /**
     * Search job cards by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('job_card_number', 'like', "%{$search}%")
                    ->orWhere('customer_complaint', 'like', "%{$search}%")
                    ->orWhere('diagnosis', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (!empty($criteria['priority'])) {
            $query->where('priority', $criteria['priority']);
        }

        if (!empty($criteria['customer_id'])) {
            $query->where('customer_id', $criteria['customer_id']);
        }

        if (!empty($criteria['vehicle_id'])) {
            $query->where('vehicle_id', $criteria['vehicle_id']);
        }

        if (!empty($criteria['assigned_to'])) {
            $query->where('assigned_to', $criteria['assigned_to']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->with(['customer', 'vehicle', 'tasks'])
            ->orderBy('created_at', 'desc')
            ->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Find job card by job card number
     */
    public function findByJobCardNumber(string $jobCardNumber): ?JobCard
    {
        return $this->model->where('job_card_number', $jobCardNumber)->first();
    }

    /**
     * Get active job cards
     */
    public function getActive()
    {
        return $this->model->active()->with(['customer', 'vehicle', 'tasks'])->get();
    }

    /**
     * Get job cards by status
     */
    public function getByStatus(string $status)
    {
        return $this->model->byStatus($status)->with(['customer', 'vehicle'])->get();
    }

    /**
     * Get job cards by priority
     */
    public function getByPriority(string $priority)
    {
        return $this->model->byPriority($priority)->with(['customer', 'vehicle'])->get();
    }

    /**
     * Get high priority job cards
     */
    public function getHighPriority()
    {
        return $this->model->highPriority()->with(['customer', 'vehicle'])->get();
    }

    /**
     * Get job cards assigned to user
     */
    public function getAssignedTo(int $userId)
    {
        return $this->model->assignedTo($userId)->with(['customer', 'vehicle', 'tasks'])->get();
    }

    /**
     * Get job cards for customer
     */
    public function getForCustomer(int $customerId)
    {
        return $this->model->where('customer_id', $customerId)
            ->with(['vehicle', 'tasks'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get job cards for vehicle
     */
    public function getForVehicle(int $vehicleId)
    {
        return $this->model->where('vehicle_id', $vehicleId)
            ->with(['customer', 'tasks'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get job cards with pending tasks
     */
    public function getWithPendingTasks()
    {
        return $this->model->whereHas('tasks', function ($query) {
            $query->where('status', '!=', 'completed');
        })->with(['customer', 'vehicle', 'tasks'])->get();
    }
}
