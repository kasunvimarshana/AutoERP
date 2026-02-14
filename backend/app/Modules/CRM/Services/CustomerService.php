<?php

namespace App\Modules\CRM\Services;

use App\Core\Services\BaseService;
use App\Modules\CRM\Models\Customer;
use App\Modules\CRM\Repositories\CustomerRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerService extends BaseService
{
    protected CustomerRepository $repository;

    public function __construct(CustomerRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create a new customer.
     */
    public function createCustomer(array $data): Customer
    {
        return DB::transaction(function () use ($data) {
            // Auto-generate customer code if not provided
            if (empty($data['customer_code'])) {
                $data['customer_code'] = $this->generateCustomerCode();
            }

            return $this->repository->create($data);
        });
    }

    /**
     * Update an existing customer.
     */
    public function updateCustomer(int $id, array $data): Customer
    {
        return DB::transaction(function () use ($id, $data) {
            return $this->repository->update($id, $data);
        });
    }

    /**
     * Delete a customer.
     */
    public function deleteCustomer(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Search customers.
     */
    public function searchCustomers(string $query)
    {
        return $this->repository->search($query);
    }

    /**
     * Get customers by type.
     */
    public function getCustomersByType(string $type)
    {
        return Customer::active()->ofType($type)->get();
    }

    /**
     * Check credit limit.
     */
    public function checkCreditLimit(int $customerId, float $additionalAmount): bool
    {
        $customer = $this->repository->find($customerId);
        
        if (!$customer) {
            throw new \InvalidArgumentException('Customer not found');
        }

        return !$customer->hasCreditLimitExceeded($additionalAmount);
    }

    /**
     * Generate unique customer code.
     */
    protected function generateCustomerCode(): string
    {
        $prefix = 'CUST';
        $lastCustomer = Customer::where('customer_code', 'like', $prefix . '%')
            ->orderBy('customer_code', 'desc')
            ->first();

        if ($lastCustomer) {
            $lastNumber = (int) str_replace($prefix, '', $lastCustomer->customer_code);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }
}
