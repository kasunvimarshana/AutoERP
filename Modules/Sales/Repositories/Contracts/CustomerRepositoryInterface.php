<?php

namespace Modules\Sales\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\Sales\Entities\Customer;

/**
 * Customer Repository Interface
 *
 * Defines the contract for customer data access operations.
 */
interface CustomerRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find customer by email.
     */
    public function findByEmail(string $email): ?Customer;

    /**
     * Find customer by customer number.
     */
    public function findByCustomerNumber(string $customerNumber): ?Customer;

    /**
     * Get active customers.
     */
    public function getActiveCustomers(): Collection;

    /**
     * Search customers by name or email.
     */
    public function search(string $query): Collection;
}
