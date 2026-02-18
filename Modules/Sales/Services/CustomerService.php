<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Services\BaseService;
use Modules\Sales\Entities\Customer;
use Modules\Sales\Repositories\Contracts\CustomerRepositoryInterface;

/**
 * Customer Service
 *
 * Handles business logic for customer management operations.
 */
class CustomerService extends BaseService
{
    /**
     * CustomerService constructor.
     */
    public function __construct(
        protected CustomerRepositoryInterface $customerRepository
    ) {}

    /**
     * Get paginated customers.
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->customerRepository->paginate($perPage);
    }

    /**
     * Create a new customer.
     */
    public function create(array $data): Customer
    {
        return $this->executeInTransaction(function () use ($data) {
            // Generate customer number if not provided
            if (empty($data['customer_number'])) {
                $data['customer_number'] = $this->generateCustomerNumber();
            }

            $customer = $this->customerRepository->create($data);

            $this->logInfo('Customer created', [
                'customer_id' => $customer->id,
                'customer_number' => $customer->customer_number,
            ]);

            return $customer;
        });
    }

    /**
     * Update an existing customer.
     */
    public function update(int $id, array $data): Customer
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $customer = $this->customerRepository->update($id, $data);

            $this->logInfo('Customer updated', [
                'customer_id' => $customer->id,
            ]);

            return $customer;
        });
    }

    /**
     * Delete a customer.
     */
    public function delete(int $id): bool
    {
        $result = $this->customerRepository->delete($id);

        if ($result) {
            $this->logInfo('Customer deleted', [
                'customer_id' => $id,
            ]);
        }

        return $result;
    }

    /**
     * Find customer by ID.
     */
    public function findById(int $id): ?Customer
    {
        return $this->customerRepository->findById($id);
    }

    /**
     * Find customer by email.
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->customerRepository->findByEmail($email);
    }

    /**
     * Find customer by customer number.
     */
    public function findByCustomerNumber(string $customerNumber): ?Customer
    {
        return $this->customerRepository->findByCustomerNumber($customerNumber);
    }

    /**
     * Get active customers.
     */
    public function getActiveCustomers(): Collection
    {
        return $this->customerRepository->getActiveCustomers();
    }

    /**
     * Search customers.
     */
    public function search(string $query): Collection
    {
        return $this->customerRepository->search($query);
    }

    /**
     * Generate a unique customer number.
     */
    protected function generateCustomerNumber(): string
    {
        $prefix = 'CUST';
        $year = date('Y');

        // Get the last customer number for this year
        $lastCustomer = $this->customerRepository
            ->getModel()
            ->where('customer_number', 'LIKE', "{$prefix}-{$year}-%")
            ->orderBy('customer_number', 'desc')
            ->first();

        if ($lastCustomer) {
            // Extract the sequence number and increment
            $parts = explode('-', $lastCustomer->customer_number);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s-%05d', $prefix, $year, $sequence);
    }
}
