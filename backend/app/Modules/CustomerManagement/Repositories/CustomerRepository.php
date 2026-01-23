<?php

namespace App\Modules\CustomerManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\CustomerManagement\Models\Customer;

class CustomerRepository extends BaseRepository
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    /**
     * Search customers by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('customer_code', 'like', "%{$search}%");
            });
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (!empty($criteria['customer_type'])) {
            $query->where('customer_type', $criteria['customer_type']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->with('vehicles')->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Find customer by email
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Find customer by customer code
     */
    public function findByCode(string $code): ?Customer
    {
        return $this->model->where('customer_code', $code)->first();
    }

    /**
     * Get customers with upcoming service vehicles
     */
    public function getWithUpcomingServices()
    {
        return $this->model->whereHas('vehicles', function ($query) {
            $query->serviceDue();
        })->with(['vehicles' => function ($query) {
            $query->serviceDue();
        }])->get();
    }
}
