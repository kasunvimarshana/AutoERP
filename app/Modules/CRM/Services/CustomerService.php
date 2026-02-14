<?php

namespace App\Modules\CRM\Services;

use App\Core\Services\BaseService;
use App\Modules\CRM\Models\Customer;
use App\Modules\CRM\Repositories\CustomerRepository;

/**
 * Customer Service
 *
 * Handles business logic for customer operations.
 */
class CustomerService extends BaseService
{
    public function __construct(CustomerRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new customer.
     */
    public function createCustomer(array $data): Customer
    {
        return $this->transaction(function () use ($data) {
            // Generate customer code if not provided
            if (! isset($data['customer_code'])) {
                $data['customer_code'] = $this->generateCustomerCode();
            }

            // Set defaults
            $data['status'] = $data['status'] ?? 'active';
            $data['payment_terms_days'] = $data['payment_terms_days'] ?? 30;
            $data['credit_limit'] = $data['credit_limit'] ?? 0;

            $customer = $this->repository->create($data);

            // Create primary contact if provided
            if (isset($data['contacts']) && is_array($data['contacts'])) {
                foreach ($data['contacts'] as $index => $contactData) {
                    $contactData['is_primary'] = $index === 0;
                    $customer->contacts()->create($contactData);
                }
            }

            return $customer->fresh(['contacts']);
        });
    }

    /**
     * Generate unique customer code.
     */
    protected function generateCustomerCode(): string
    {
        $prefix = 'CUST';
        $number = str_pad(
            (Customer::max('id') ?? 0) + 1,
            6,
            '0',
            STR_PAD_LEFT
        );

        return $prefix.$number;
    }

    /**
     * Search customers.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchCustomers(string $query)
    {
        return $this->repository->search($query);
    }

    /**
     * Get customer with complete profile.
     */
    public function getCustomerProfile(int $id): ?Customer
    {
        $customer = $this->repository->find($id);

        if ($customer) {
            $customer->load(['contacts', 'vehicles.serviceHistory']);
        }

        return $customer;
    }
}
