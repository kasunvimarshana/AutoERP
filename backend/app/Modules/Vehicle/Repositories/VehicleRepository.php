<?php

namespace App\Modules\Vehicle\Repositories;

use App\Base\BaseRepository;
use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;

class VehicleRepository extends BaseRepository
{
    public function __construct(Vehicle $model)
    {
        parent::__construct($model);
    }

    public function findByVin(string $vin): ?Vehicle
    {
        return $this->model->where('vin', $vin)->first();
    }

    public function findByRegistration(string $registration): ?Vehicle
    {
        return $this->model->where('registration_number', $registration)->first();
    }

    public function findByCustomer(string $customerId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('customer_id', $customerId)
            ->get();
    }

    public function findByTenant(string $tenantId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('tenant_id', $tenantId)
            ->get();
    }

    public function findByBranch(string $branchId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('branch_id', $branchId)
            ->get();
    }

    public function getDueForService(array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->whereNotNull('next_service_date')
            ->whereDate('next_service_date', '<=', now()->addDays(7))
            ->get();
    }
}
