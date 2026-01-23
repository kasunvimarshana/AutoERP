<?php

namespace Modules\Customer\Services;

use App\Services\Base\BaseService;
use Modules\Customer\Models\Customer;
use Modules\Customer\Repositories\CustomerRepository;

// use Modules\Customer\Events\CustomerCreated;
// use Modules\Customer\Events\CustomerUpdated;
// use Modules\Customer\Events\CustomerDeleted;

/**
 * Customer Service
 *
 * Orchestrates business logic for customer operations.
 * Handles transactions, validation, and cross-module coordination.
 *
 * Service Layer Responsibilities:
 * - Validate business rules
 * - Manage database transactions
 * - Coordinate with other services
 * - Emit domain events
 * - Handle complex business workflows
 */
class CustomerService extends BaseService
{
    /**
     * Constructor
     */
    public function __construct(CustomerRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new customer.
     *
     * @throws \Throwable
     */
    public function createCustomer(array $data): Customer
    {
        return $this->transaction(function () use ($data) {
            // Validate unique constraints
            $this->validateUniqueCustomer($data);

            // Set tenant context
            $data['tenant_id'] = $data['tenant_id'] ?? $this->getCurrentTenantId();

            // Create customer
            $customer = $this->repository->create($data);

            // Log activity
            $this->logActivity('customer.created', [
                'customer_id' => $customer->id,
                'tenant_id' => $customer->tenant_id,
            ]);

            // Fire event
            // $this->fireEvent(new CustomerCreated($customer));

            return $customer;
        });
    }

    /**
     * Update an existing customer.
     *
     * @throws \Throwable
     */
    public function updateCustomer(int $customerId, array $data): Customer
    {
        return $this->transaction(function () use ($customerId, $data) {
            // Find customer
            $customer = $this->repository->findOrFail($customerId);

            // Validate unique constraints
            $this->validateUniqueCustomer($data, $customerId);

            // Update customer
            $customer = $this->repository->update($customerId, $data);

            // Log activity
            $this->logActivity('customer.updated', [
                'customer_id' => $customer->id,
                'changes' => $data,
            ]);

            // Fire event
            // $this->fireEvent(new CustomerUpdated($customer));

            return $customer;
        });
    }

    /**
     * Delete a customer.
     *
     * @throws \Throwable
     */
    public function deleteCustomer(int $customerId, bool $soft = true): bool
    {
        return $this->transaction(function () use ($customerId, $soft) {
            // Find customer
            /** @var Customer $customer */
            $customer = $this->repository->findOrFail($customerId);

            // Check if customer has active bookings or outstanding invoices
            $this->validateCustomerDeletion($customer);

            // Delete customer
            $deleted = $soft
                ? $this->repository->softDelete($customerId)
                : $this->repository->delete($customerId);

            if ($deleted) {
                // Log activity
                $this->logActivity('customer.deleted', [
                    'customer_id' => $customerId,
                    'soft_delete' => $soft,
                ]);

                // Fire event
                // $this->fireEvent(new CustomerDeleted($customer));
            }

            return $deleted;
        });
    }

    /**
     * Restore a soft-deleted customer.
     *
     * @throws \Throwable
     */
    public function restoreCustomer(int $customerId): bool
    {
        return $this->transaction(function () use ($customerId) {
            $restored = $this->repository->restore($customerId);

            if ($restored) {
                $this->logActivity('customer.restored', [
                    'customer_id' => $customerId,
                ]);
            }

            return $restored;
        });
    }

    /**
     * Search for customers.
     */
    public function searchCustomers(string $searchTerm, int $perPage = 15)
    {
        return $this->repository->search($searchTerm, $perPage);
    }

    /**
     * Get customer by ID.
     */
    public function getCustomer(int $customerId): Customer
    {
        return $this->repository->findOrFail($customerId);
    }

    /**
     * Get customer statistics.
     */
    public function getStatistics(): array
    {
        return $this->cache('customer.statistics', 3600, function () {
            return $this->repository->getStatistics();
        });
    }

    /**
     * Validate unique customer constraints.
     *
     * @throws \Exception
     */
    protected function validateUniqueCustomer(array $data, ?int $excludeId = null): void
    {
        // Check email uniqueness
        if (isset($data['email'])) {
            $existing = $this->repository->findByEmail($data['email']);
            if ($existing && (! $excludeId || $existing->id !== $excludeId)) {
                throw new \Exception('Email already exists');
            }
        }

        // Check phone uniqueness (optional based on business rules)
        if (isset($data['phone'])) {
            $existing = $this->repository->findByPhone($data['phone']);
            if ($existing && (! $excludeId || $existing->id !== $excludeId)) {
                throw new \Exception('Phone number already exists');
            }
        }
    }

    /**
     * Validate customer can be deleted.
     *
     * @throws \Exception
     */
    protected function validateCustomerDeletion(Customer $customer): void
    {
        // Check for active appointments
        // if ($customer->appointments()->active()->exists()) {
        //     throw new \Exception('Cannot delete customer with active appointments');
        // }

        // Check for outstanding invoices
        // if ($customer->invoices()->outstanding()->exists()) {
        //     throw new \Exception('Cannot delete customer with outstanding invoices');
        // }

        // Check for vehicles (may want to handle cascade)
        // if ($customer->vehicles()->exists()) {
        //     throw new \Exception('Cannot delete customer with registered vehicles');
        // }
    }

    /**
     * Merge two customer records.
     * Useful when duplicate customers are discovered.
     *
     * @throws \Throwable
     */
    public function mergeCustomers(int $primaryCustomerId, int $duplicateCustomerId): Customer
    {
        return $this->transaction(function () use ($primaryCustomerId, $duplicateCustomerId) {
            $primaryCustomer = $this->repository->findOrFail($primaryCustomerId);
            $duplicateCustomer = $this->repository->findOrFail($duplicateCustomerId);

            // Transfer vehicles
            // $duplicateCustomer->vehicles()->update(['customer_id' => $primaryCustomerId]);

            // Transfer appointments
            // $duplicateCustomer->appointments()->update(['customer_id' => $primaryCustomerId]);

            // Transfer job cards
            // $duplicateCustomer->jobCards()->update(['customer_id' => $primaryCustomerId]);

            // Transfer invoices
            // $duplicateCustomer->invoices()->update(['customer_id' => $primaryCustomerId]);

            // Soft delete duplicate
            $this->repository->softDelete($duplicateCustomerId);

            // Log merge
            $this->logActivity('customer.merged', [
                'primary_customer_id' => $primaryCustomerId,
                'duplicate_customer_id' => $duplicateCustomerId,
            ]);

            return $primaryCustomer;
        });
    }
}
