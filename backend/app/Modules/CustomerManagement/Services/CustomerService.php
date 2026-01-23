<?php

namespace App\Modules\CustomerManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\CustomerManagement\Events\CustomerCreated;
use App\Modules\CustomerManagement\Models\Customer;
use App\Modules\CustomerManagement\Repositories\CustomerRepository;
use Illuminate\Database\Eloquent\Model;

class CustomerService extends BaseService
{
    public function __construct(CustomerRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Search customers with filters
     */
    public function search(array $criteria)
    {
        return $this->repository->search($criteria);
    }

    /**
     * Find customer by email
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->repository->findByEmail($email);
    }

    /**
     * Find customer by code
     */
    public function findByCode(string $code): ?Customer
    {
        return $this->repository->findByCode($code);
    }

    /**
     * Get customers with upcoming service vehicles
     */
    public function getWithUpcomingServices()
    {
        return $this->repository->getWithUpcomingServices();
    }

    /**
     * After customer creation hook
     */
    protected function afterCreate(Model $customer, array $data): void
    {
        // Dispatch event for customer creation
        event(new CustomerCreated($customer));
    }

    /**
     * Update customer lifetime value
     */
    public function updateLifetimeValue(int $customerId, float $amount): void
    {
        $customer = $this->repository->findOrFail($customerId);
        $customer->lifetime_value += $amount;
        $customer->total_services += 1;
        $customer->last_service_date = now();
        $customer->save();
    }
}
