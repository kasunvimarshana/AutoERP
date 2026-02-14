<?php

namespace App\Modules\Customer\Repositories;

use App\Base\BaseRepository;
use App\Modules\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

class CustomerRepository extends BaseRepository
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    public function findByCode(string $code): ?Customer
    {
        return $this->model->where('code', $code)->first();
    }

    public function findByEmail(string $email): ?Customer
    {
        return $this->model->where('email', $email)->first();
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

    public function getActive(array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('status', 'active')
            ->get();
    }

    public function searchCustomers(string $query, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
                  ->orWhere('mobile', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%");
            })
            ->get();
    }
}
