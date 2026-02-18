<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\BaseService;
use Modules\Sales\Events\CreditLimitExceeded;
use Modules\Sales\Events\CustomerCreated;
use Modules\Sales\Events\CustomerUpdated;
use Modules\Sales\Models\Customer;
use Modules\Sales\Repositories\CustomerRepository;

class CustomerService extends BaseService
{
    public function __construct(
        protected CustomerRepository $repository,
        \Modules\Core\Services\TenantContext $tenantContext
    ) {
        parent::__construct($tenantContext);
    }

    /**
     * Get paginated customers with filters.
     */
    public function getAll(array $filters = [], int $perPage = 15): array
    {
        $this->validateTenant();

        return $this->repository->getFiltered($filters, $perPage)->toArray();
    }

    /**
     * Find customer by ID.
     */
    public function findById(int $id): ?Customer
    {
        $this->validateTenant();

        return $this->repository->find($id);
    }

    /**
     * Create new customer with automatic code generation.
     */
    public function create(array $data): Customer
    {
        $this->validateTenant();
        $this->validateCustomerData($data);

        return $this->transaction(function () use ($data) {
            // Auto-generate customer code if not provided
            if (empty($data['customer_code'])) {
                $data['customer_code'] = $this->repository->generateNextCode();
            }

            $data['tenant_id'] = $this->getTenantId();
            $customer = $this->repository->create($data);

            $this->dispatchEvent(new CustomerCreated($customer));
            $this->logActivity('created', $customer);

            return $customer;
        });
    }

    /**
     * Update existing customer.
     */
    public function update(int $id, array $data): Customer
    {
        $this->validateTenant();
        $this->validateCustomerData($data, $id);

        return $this->transaction(function () use ($id, $data) {
            $customer = $this->repository->update($id, $data);

            $this->dispatchEvent(new CustomerUpdated($customer));
            $this->logActivity('updated', $customer);

            return $customer;
        });
    }

    /**
     * Delete customer (soft delete).
     */
    public function delete(int $id): bool
    {
        $this->validateTenant();

        $customer = $this->repository->find($id);
        if (! $customer) {
            throw new \RuntimeException('Customer not found');
        }

        // Check if customer has outstanding balance
        if ($customer->outstanding_balance > 0) {
            throw new \RuntimeException('Cannot delete customer with outstanding balance');
        }

        return $this->transaction(function () use ($id, $customer) {
            $this->logActivity('deleted', $customer);

            return $this->repository->delete($id);
        });
    }

    /**
     * Activate customer account.
     */
    public function activate(int $id): Customer
    {
        $this->validateTenant();

        return $this->transaction(function () use ($id) {
            $customer = $this->repository->update($id, ['is_active' => true]);
            $this->logActivity('activated', $customer);

            return $customer;
        });
    }

    /**
     * Deactivate customer account.
     */
    public function deactivate(int $id): Customer
    {
        $this->validateTenant();

        return $this->transaction(function () use ($id) {
            $customer = $this->repository->update($id, ['is_active' => false]);
            $this->logActivity('deactivated', $customer);

            return $customer;
        });
    }

    /**
     * Update customer tier (standard, premium, vip).
     */
    public function updateTier(int $id, string $tier): Customer
    {
        $this->validateTenant();

        if (! in_array($tier, ['standard', 'premium', 'vip'])) {
            throw new \InvalidArgumentException('Invalid customer tier');
        }

        return $this->transaction(function () use ($id, $tier) {
            $customer = $this->repository->update($id, ['customer_tier' => $tier]);
            $this->logActivity('tier_updated', $customer, ['new_tier' => $tier]);

            return $customer;
        });
    }

    /**
     * Check if customer can place order based on credit.
     */
    public function checkCreditAvailability(int $id, float $orderAmount): array
    {
        $customer = $this->repository->find($id);

        if (! $customer) {
            return [
                'approved' => false,
                'reason' => 'Customer not found',
            ];
        }

        if (! $customer->is_active) {
            return [
                'approved' => false,
                'reason' => 'Customer account is inactive',
            ];
        }

        $availableCredit = $customer->available_credit;

        if ($orderAmount > $availableCredit) {
            $this->dispatchEvent(new CreditLimitExceeded($customer, $orderAmount));

            return [
                'approved' => false,
                'reason' => 'Order amount exceeds available credit',
                'available_credit' => $availableCredit,
                'requested_amount' => $orderAmount,
                'shortfall' => $orderAmount - $availableCredit,
            ];
        }

        return [
            'approved' => true,
            'available_credit' => $availableCredit,
            'remaining_after_order' => $availableCredit - $orderAmount,
        ];
    }

    /**
     * Get customer statistics.
     */
    public function getStatistics(int $id): array
    {
        $customer = $this->repository->find($id);

        if (! $customer) {
            throw new \RuntimeException('Customer not found');
        }

        $totalOrders = $customer->salesOrders()->count();
        $totalRevenue = $customer->salesOrders()->sum('total_amount');
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'average_order_value' => $averageOrderValue,
            'outstanding_balance' => $customer->outstanding_balance,
            'available_credit' => $customer->available_credit,
            'credit_utilization' => $customer->credit_limit > 0
                ? ($customer->outstanding_balance / $customer->credit_limit) * 100
                : 0,
        ];
    }

    /**
     * Validate customer data.
     */
    protected function validateCustomerData(array $data, ?int $id = null): void
    {
        $rules = [
            'customer_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:customers,email'.($id ? ",$id" : ''),
            'phone' => 'nullable|string|max:50',
            'customer_tier' => 'nullable|in:standard,premium,vip',
            'payment_terms' => 'nullable|in:cod,net_7,net_15,net_30,net_60,net_90',
            'credit_limit' => 'nullable|numeric|min:0',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
