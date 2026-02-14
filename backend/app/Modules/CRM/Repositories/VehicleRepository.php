<?php

namespace App\Modules\CRM\Repositories;

use App\Core\BaseRepository;
use App\Modules\CRM\Models\Vehicle;

class VehicleRepository extends BaseRepository
{
    public function __construct(Vehicle $model)
    {
        parent::__construct($model);
    }

    public function findByVin(string $vin): ?Vehicle
    {
        return $this->findBy('vin', $vin);
    }

    public function findByLicensePlate(string $licensePlate): ?Vehicle
    {
        return $this->findBy('license_plate', $licensePlate);
    }

    public function getByCustomer(int $customerId)
    {
        return $this->model->where('customer_id', $customerId)->get();
    }

    public function getByTenant(int $tenantId)
    {
        return $this->model->where('tenant_id', $tenantId)->get();
    }
}
