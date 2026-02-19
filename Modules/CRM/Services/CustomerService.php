<?php

declare(strict_types=1);

namespace Modules\CRM\Services;

use Modules\Core\Helpers\TransactionHelper;
use Modules\Core\Services\CodeGeneratorService;
use Modules\CRM\Enums\CustomerStatus;
use Modules\CRM\Models\Customer;
use Modules\CRM\Repositories\CustomerRepository;

/**
 * Customer Service
 *
 * Handles business logic for customer management including
 * code generation, status management, and customer operations.
 */
class CustomerService
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private CodeGeneratorService $codeGenerator
    ) {}

    /**
     * Create a new customer.
     */
    public function createCustomer(array $data): Customer
    {
        return TransactionHelper::execute(function () use ($data) {
            // Generate customer code if not provided
            if (empty($data['customer_code'])) {
                $data['customer_code'] = $this->generateCustomerCode();
            }

            // Set default status if not provided
            $data['status'] = $data['status'] ?? CustomerStatus::ACTIVE;

            // Create customer
            return $this->customerRepository->create($data);
        });
    }

    /**
     * Update customer.
     */
    public function updateCustomer(string $id, array $data): Customer
    {
        return TransactionHelper::execute(function () use ($id, $data) {
            return $this->customerRepository->update($id, $data);
        });
    }

    /**
     * Delete customer.
     */
    public function deleteCustomer(string $id): bool
    {
        return $this->customerRepository->delete($id);
    }

    /**
     * Generate unique customer code.
     */
    private function generateCustomerCode(): string
    {
        $prefix = config('crm.customer.code_prefix', 'CUST-');

        return $this->codeGenerator->generateSequential(
            $prefix,
            $this->getNextSequence($prefix)
        );
    }

    /**
     * Get next sequence number for customer code.
     */
    private function getNextSequence(string $prefix): int
    {
        $lastCustomer = Customer::where('customer_code', 'like', $prefix.'%')
            ->orderBy('customer_code', 'desc')
            ->first();

        if ($lastCustomer) {
            $lastNumber = (int) str_replace($prefix, '', $lastCustomer->customer_code);

            return $lastNumber + 1;
        }

        return 1;
    }
}
