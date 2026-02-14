<?php

namespace App\Modules\Customer\Services;

use App\Base\BaseService;
use App\Modules\Customer\Repositories\CustomerRepository;
use App\Modules\Customer\DTOs\CustomerDTO;
use App\Modules\Customer\Events\CustomerCreated;
use App\Modules\Customer\Events\CustomerUpdated;
use App\Modules\Customer\Events\CustomerDeleted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class CustomerService extends BaseService
{
    public function __construct(CustomerRepository $repository)
    {
        parent::__construct($repository);
    }

    public function createCustomer(CustomerDTO $dto): Model
    {
        return $this->executeInTransaction(function () use ($dto) {
            $data = $dto->toArray();
            
            $customer = $this->repository->create($data);
            
            Event::dispatch(new CustomerCreated($customer));
            
            return $customer;
        });
    }

    public function updateCustomer(string $id, CustomerDTO $dto): bool
    {
        return $this->executeInTransaction(function () use ($id, $dto) {
            $customer = $this->repository->find($id);
            
            if (!$customer) {
                return false;
            }
            
            $data = $dto->toArray();
            
            $result = $this->repository->update($id, $data);
            
            if ($result) {
                $customer->refresh();
                Event::dispatch(new CustomerUpdated($customer));
            }
            
            return $result;
        });
    }

    public function deleteCustomer(string $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            $customer = $this->repository->find($id);
            
            if (!$customer) {
                return false;
            }
            
            $result = $this->repository->delete($id);
            
            if ($result) {
                Event::dispatch(new CustomerDeleted($customer));
            }
            
            return $result;
        });
    }

    public function getCustomerHistory(string $id): array
    {
        $customer = $this->repository->find($id, ['*'], ['orders', 'invoices', 'vehicles']);
        
        if (!$customer) {
            return [];
        }
        
        return [
            'customer' => $customer,
            'orders' => $customer->orders,
            'invoices' => $customer->invoices,
            'vehicles' => $customer->vehicles,
        ];
    }

    public function getCustomerVehicles(string $id)
    {
        $customer = $this->repository->find($id, ['*'], ['vehicles.branch']);
        
        if (!$customer) {
            return null;
        }
        
        return $customer->vehicles;
    }

    public function findByCode(string $code): ?Model
    {
        return $this->repository->findByCode($code);
    }

    public function findByEmail(string $email): ?Model
    {
        return $this->repository->findByEmail($email);
    }

    public function findByTenant(string $tenantId)
    {
        return $this->repository->findByTenant($tenantId);
    }

    public function findByBranch(string $branchId)
    {
        return $this->repository->findByBranch($branchId);
    }

    public function getActiveCustomers()
    {
        return $this->repository->getActive();
    }

    public function searchCustomers(string $query)
    {
        return $this->repository->searchCustomers($query);
    }
}
