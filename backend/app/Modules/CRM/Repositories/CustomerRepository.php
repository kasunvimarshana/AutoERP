<?php

namespace App\Modules\CRM\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\CRM\Models\Customer;

class CustomerRepository extends BaseRepository
{
    public function __construct(Customer $model)
    {
        $this->model = $model;
    }

    /**
     * Get all active customers.
     */
    public function getActive()
    {
        return $this->model->active()->get();
    }

    /**
     * Search customers by code, name, email, or phone.
     */
    public function search(string $query)
    {
        return $this->model->where(function ($q) use ($query) {
            $q->where('customer_code', 'like', "%{$query}%")
              ->orWhere('first_name', 'like', "%{$query}%")
              ->orWhere('last_name', 'like', "%{$query}%")
              ->orWhere('company_name', 'like', "%{$query}%")
              ->orWhere('email', 'like', "%{$query}%")
              ->orWhere('phone', 'like', "%{$query}%");
        })->get();
    }

    /**
     * Find customer by code.
     */
    public function findByCode(string $code)
    {
        return $this->model->where('customer_code', $code)->first();
    }

    /**
     * Get customers by type.
     */
    public function getByType(string $type)
    {
        return $this->model->active()->ofType($type)->get();
    }

    /**
     * Get customers with contacts.
     */
    public function getWithContacts()
    {
        return $this->model->with('contacts')->get();
    }
}
