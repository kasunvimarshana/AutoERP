<?php

namespace App\Modules\CRM\Repositories;

use App\Core\BaseRepository;
use App\Modules\CRM\Models\Customer;

class CustomerRepository extends BaseRepository
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?Customer
    {
        return $this->findBy('email', $email);
    }

    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->get();
    }

    public function getByTenant(int $tenantId)
    {
        return $this->model->where('tenant_id', $tenantId)->get();
    }
}
