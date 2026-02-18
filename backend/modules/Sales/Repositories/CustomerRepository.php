<?php

declare(strict_types=1);

namespace Modules\Sales\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Sales\Models\Customer;

class CustomerRepository extends BaseRepository
{
    protected function model(): string
    {
        return Customer::class;
    }

    /**
     * Get customers with filters and pagination.
     */
    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // Filter by status
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Filter by tier
        if (! empty($filters['customer_tier'])) {
            $query->where('customer_tier', $filters['customer_tier']);
        }

        // Filter by country
        if (! empty($filters['country'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('billing_country', $filters['country'])
                    ->orWhere('shipping_country', $filters['country']);
            });
        }

        // Search by name, code, or email
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by good standing
        if (isset($filters['good_standing']) && $filters['good_standing']) {
            $query->inGoodStanding();
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'customer_name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Find customer by code.
     */
    public function findByCode(string $code): ?Customer
    {
        return $this->model->where('customer_code', $code)->first();
    }

    /**
     * Find customer by email.
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Get customers with outstanding balance exceeding credit limit.
     */
    public function getOverCreditLimit(): array
    {
        return $this->model
            ->where('is_active', true)
            ->whereRaw('outstanding_balance > credit_limit')
            ->get()
            ->toArray();
    }

    /**
     * Get top customers by total order value.
     */
    public function getTopCustomers(int $limit = 10): array
    {
        return $this->model
            ->withSum('salesOrders', 'total_amount')
            ->orderByDesc('sales_orders_sum_total_amount')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Generate next customer code.
     */
    public function generateNextCode(string $prefix = 'CUST'): string
    {
        $lastCustomer = $this->model
            ->where('customer_code', 'like', "{$prefix}%")
            ->orderByDesc('customer_code')
            ->first();

        if (! $lastCustomer) {
            return "{$prefix}-0001";
        }

        $lastNumber = (int) substr($lastCustomer->customer_code, strlen($prefix) + 1);
        $nextNumber = $lastNumber + 1;

        return sprintf('%s-%04d', $prefix, $nextNumber);
    }

    /**
     * Update customer balance.
     */
    public function updateBalance(int $customerId, float $amount): bool
    {
        return $this->model
            ->where('id', $customerId)
            ->increment('outstanding_balance', $amount);
    }
}
