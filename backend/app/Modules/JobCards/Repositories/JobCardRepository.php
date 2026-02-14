<?php

namespace App\Modules\JobCards\Repositories;

use App\Core\BaseRepository;
use App\Modules\JobCards\Models\JobCard;

class JobCardRepository extends BaseRepository
{
    public function __construct(JobCard $model)
    {
        parent::__construct($model);
    }

    public function findByJobCardNumber(string $jobCardNumber): ?JobCard
    {
        return $this->findBy('job_card_number', $jobCardNumber);
    }

    public function getByTenant(int $tenantId)
    {
        return $this->model->where('tenant_id', $tenantId)->get();
    }

    public function getByCustomer(int $customerId)
    {
        return $this->model->where('customer_id', $customerId)->get();
    }

    public function getByVehicle(int $vehicleId)
    {
        return $this->model->where('vehicle_id', $vehicleId)->get();
    }

    public function getByTechnician(int $technicianId)
    {
        return $this->model->where('technician_id', $technicianId)->get();
    }

    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->get();
    }

    public function getByDateRange(string $startDate, string $endDate)
    {
        return $this->model
            ->whereBetween('opened_date', [$startDate, $endDate])
            ->get();
    }

    public function getTotalCostByTenant(int $tenantId, ?string $startDate = null, ?string $endDate = null)
    {
        $query = $this->model
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed');

        if ($startDate && $endDate) {
            $query->whereBetween('opened_date', [$startDate, $endDate]);
        }

        return $query->sum('actual_cost');
    }
}
