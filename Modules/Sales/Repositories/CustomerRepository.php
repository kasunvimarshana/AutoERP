<?php

namespace Modules\Sales\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Sales\Entities\Customer;
use Modules\Sales\Repositories\Contracts\CustomerRepositoryInterface;

/**
 * Customer Repository Implementation
 *
 * Handles all customer data access operations.
 */
class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    /**
     * CustomerRepository constructor.
     */
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findByCustomerNumber(string $customerNumber): ?Customer
    {
        return $this->model->where('customer_number', $customerNumber)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveCustomers(): Collection
    {
        return $this->model->where('status', 'active')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query): Collection
    {
        return $this->model
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->orWhere('customer_number', 'LIKE', "%{$query}%")
            ->get();
    }
}
