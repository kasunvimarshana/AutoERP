<?php

namespace App\Modules\CRM\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\CRM\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

/**
 * Customer Repository
 * 
 * Handles data access operations for customers
 */
class CustomerRepository extends BaseRepository
{
    /**
     * Specify the model class name
     *
     * @return string
     */
    protected function model(): string
    {
        return Customer::class;
    }

    /**
     * Find customer by code
     *
     * @param string $code
     * @return Customer|null
     */
    public function findByCode(string $code): ?Customer
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * Find customer by email
     *
     * @param string $email
     * @return Customer|null
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Search customers by multiple fields
     *
     * @param string $search
     * @return Collection
     */
    public function searchCustomers(string $search): Collection
    {
        return $this->model
            ->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            })
            ->get();
    }
}
