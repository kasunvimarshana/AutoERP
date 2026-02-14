<?php

namespace App\Modules\Procurement\Repositories;

use App\Core\BaseRepository;
use App\Modules\Procurement\Models\Supplier;

class SupplierRepository extends BaseRepository
{
    public function __construct(Supplier $model)
    {
        parent::__construct($model);
    }

    public function findByName(string $name): ?Supplier
    {
        return $this->findBy('name', $name);
    }

    public function findByEmail(string $email): ?Supplier
    {
        return $this->findBy('email', $email);
    }

    public function getByTenant(int $tenantId)
    {
        return $this->model->where('tenant_id', $tenantId)->get();
    }

    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->get();
    }

    public function getByCountry(string $country)
    {
        return $this->model->where('country', $country)->get();
    }

    public function searchByName(string $query)
    {
        return $this->model->where('name', 'like', "%{$query}%")->get();
    }
}
