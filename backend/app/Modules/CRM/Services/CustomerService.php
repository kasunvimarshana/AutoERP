<?php

namespace App\Modules\CRM\Services;

use App\Modules\CRM\Repositories\CustomerRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CustomerService extends BaseService
{
    public function __construct(
        protected CustomerRepository $repository
    ) {}

    /**
     * Create a new customer
     */
    public function createCustomer(array $data): mixed
    {
        $this->validateCustomerData($data);

        return $this->repository->create($data);
    }

    /**
     * Update customer
     */
    public function updateCustomer(int $id, array $data): mixed
    {
        $this->validateCustomerData($data, $id);

        return $this->repository->update($id, $data);
    }

    /**
     * Delete customer
     */
    public function deleteCustomer(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Get customer by ID
     */
    public function getCustomer(int $id): mixed
    {
        return $this->repository->find($id);
    }

    /**
     * Get all customers with pagination
     */
    public function getAllCustomers(int $perPage = 15): mixed
    {
        return $this->repository->paginate($perPage);
    }

    /**
     * Search customers
     */
    public function searchCustomers(string $term, int $perPage = 15): mixed
    {
        return $this->repository->search($term, $perPage);
    }

    /**
     * Validate customer data
     */
    protected function validateCustomerData(array $data, ?int $customerId = null): void
    {
        $rules = [
            'name' => ($customerId ? 'sometimes|' : '').'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:customers,email'.($customerId ? ",{$customerId}" : ''),
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'customer_type' => ($customerId ? 'sometimes|' : '').'required|in:individual,business',
            'company_name' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'nullable|integer|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
