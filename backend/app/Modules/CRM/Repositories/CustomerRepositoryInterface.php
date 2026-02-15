<?php

declare(strict_types=1);

namespace App\Modules\CRM\Repositories;

use App\Core\Repositories\BaseRepositoryInterface;
use App\Modules\CRM\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

interface CustomerRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find customer by code
     */
    public function findByCode(string $code): ?Customer;

    /**
     * Find customer by email
     */
    public function findByEmail(string $email): ?Customer;

    /**
     * Get active customers
     */
    public function getActiveCustomers(): Collection;

    /**
     * Get customers by type
     */
    public function getByType(string $type): Collection;

    /**
     * Get customers by status
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get customers by group
     */
    public function getByGroup(string $group): Collection;

    /**
     * Get customers assigned to user
     */
    public function getByAssignedUser(int $userId): Collection;

    /**
     * Search customers
     */
    public function search(string $query, array $filters = []): Collection;

    /**
     * Get customers with exceeded credit limit
     */
    public function getWithExceededCreditLimit(): Collection;

    /**
     * Get VIP customers
     */
    public function getVipCustomers(): Collection;
}
