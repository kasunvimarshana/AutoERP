<?php

declare(strict_types=1);

namespace App\Modules\CRM\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\CRM\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    /**
     * CustomerRepository constructor.
     */
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    /**
     * Find customer by code
     */
    public function findByCode(string $code): ?Customer
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * Find customer by email
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Get active customers
     */
    public function getActiveCustomers(): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->where('status', 'active')
            ->get();
    }

    /**
     * Get customers by type
     */
    public function getByType(string $type): Collection
    {
        return $this->model->where('type', $type)->get();
    }

    /**
     * Get customers by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Get customers by group
     */
    public function getByGroup(string $group): Collection
    {
        return $this->model->where('customer_group', $group)->get();
    }

    /**
     * Get customers assigned to user
     */
    public function getByAssignedUser(int $userId): Collection
    {
        return $this->model->where('assigned_to', $userId)->get();
    }

    /**
     * Search customers
     */
    public function search(string $query, array $filters = []): Collection
    {
        $queryBuilder = $this->model->query();

        // Search by name, code, email, phone, company
        if (!empty($query)) {
            $queryBuilder->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
                  ->orWhere('mobile', 'like', "%{$query}%")
                  ->orWhere('company_name', 'like', "%{$query}%")
                  ->orWhere('tax_number', 'like', "%{$query}%");
            });
        }

        // Apply filters
        if (isset($filters['type'])) {
            $queryBuilder->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $queryBuilder->where('status', $filters['status']);
        }

        if (isset($filters['customer_group'])) {
            $queryBuilder->where('customer_group', $filters['customer_group']);
        }

        if (isset($filters['priority'])) {
            $queryBuilder->where('priority', $filters['priority']);
        }

        if (isset($filters['assigned_to'])) {
            $queryBuilder->where('assigned_to', $filters['assigned_to']);
        }

        if (isset($filters['is_active'])) {
            $queryBuilder->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_verified'])) {
            $queryBuilder->where('is_verified', $filters['is_verified']);
        }

        if (isset($filters['organization_id'])) {
            $queryBuilder->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['branch_id'])) {
            $queryBuilder->where('branch_id', $filters['branch_id']);
        }

        return $queryBuilder->get();
    }

    /**
     * Get customers with exceeded credit limit
     */
    public function getWithExceededCreditLimit(): Collection
    {
        // This would need to join with invoices/orders to calculate actual usage
        // For now, returning customers with credit limit set
        return $this->model
            ->where('credit_limit', '>', 0)
            ->get();
    }

    /**
     * Get VIP customers
     */
    public function getVipCustomers(): Collection
    {
        return $this->model->where('priority', 'vip')->get();
    }
}
