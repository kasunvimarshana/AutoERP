<?php

namespace App\Modules\CRM\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\CRM\Models\Customer;

/**
 * Customer Repository
 *
 * Handles data access for customer operations.
 */
class CustomerRepository extends BaseRepository
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    /**
     * Find customer by customer code.
     */
    public function findByCode(string $code): ?Customer
    {
        return $this->model->where('customer_code', $code)->first();
    }

    /**
     * Find customer by email.
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Get customers with vehicles.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCustomersWithVehicles()
    {
        return $this->model->has('vehicles')->with('vehicles')->get();
    }

    /**
     * Search customers by name or email.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search(string $query)
    {
        return $this->model
            ->where('first_name', 'like', "%{$query}%")
            ->orWhere('last_name', 'like', "%{$query}%")
            ->orWhere('company_name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->get();
    }
}
