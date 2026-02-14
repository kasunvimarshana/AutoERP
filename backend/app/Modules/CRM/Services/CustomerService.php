<?php

namespace App\Modules\CRM\Services;

use App\Core\BaseService;
use App\Modules\CRM\Repositories\CustomerRepository;
use App\Modules\CRM\Events\CustomerCreated;
use App\Modules\CRM\Events\CustomerUpdated;

class CustomerService extends BaseService
{
    protected CustomerRepository $repository;

    public function __construct(CustomerRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createCustomer(array $data)
    {
        return $this->transaction(function () use ($data) {
            $customer = $this->repository->create($data);
            
            $this->logActivity('Customer created', ['customer_id' => $customer->id]);
            
            event(new CustomerCreated($customer));
            
            return $customer;
        });
    }

    public function updateCustomer(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            $result = $this->repository->update($id, $data);
            
            if ($result) {
                $customer = $this->repository->find($id);
                
                $this->logActivity('Customer updated', ['customer_id' => $id]);
                
                event(new CustomerUpdated($customer));
            }
            
            return $result;
        });
    }

    public function getCustomer(int $id)
    {
        return $this->repository->with(['contacts', 'vehicles', 'tenant'])->find($id);
    }

    public function getAllCustomers()
    {
        return $this->repository->with(['contacts', 'vehicles'])->all();
    }

    public function deleteCustomer(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $result = $this->repository->delete($id);
            
            if ($result) {
                $this->logActivity('Customer deleted', ['customer_id' => $id]);
            }
            
            return $result;
        });
    }
}
