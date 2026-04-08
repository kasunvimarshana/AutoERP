<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Customer\Application\Contracts\CustomerServiceInterface;
use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Domain\Events\CustomerCreated;
use Modules\Customer\Domain\Events\CustomerUpdated;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;

class CustomerService implements CustomerServiceInterface
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {}

    public function getCustomer(string $tenantId, string $id): Customer
    {
        $customer = $this->customerRepository->findById($tenantId, $id);
        if ($customer === null) {
            throw new NotFoundException('Customer', $id);
        }
        return $customer;
    }

    public function getAllCustomers(string $tenantId): array
    {
        return $this->customerRepository->findAll($tenantId);
    }

    public function createCustomer(string $tenantId, array $data): Customer
    {
        return DB::transaction(function () use ($tenantId, $data): Customer {
            $now = now();
            $customer = new Customer(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                name: $data['name'],
                code: $data['code'],
                email: $data['email'] ?? null,
                phone: $data['phone'] ?? null,
                address: $data['address'] ?? null,
                taxNumber: $data['tax_number'] ?? null,
                currency: $data['currency'] ?? 'USD',
                creditLimit: (float) ($data['credit_limit'] ?? 0.0),
                balance: (float) ($data['balance'] ?? 0.0),
                isActive: (bool) ($data['is_active'] ?? true),
                createdAt: $now,
                updatedAt: $now,
            );
            $this->customerRepository->save($customer);
            Event::dispatch(new CustomerCreated($customer));
            return $customer;
        });
    }

    public function updateCustomer(string $tenantId, string $id, array $data): Customer
    {
        return DB::transaction(function () use ($tenantId, $id, $data): Customer {
            $existing = $this->getCustomer($tenantId, $id);
            $customer = new Customer(
                id: $existing->id,
                tenantId: $existing->tenantId,
                name: $data['name'] ?? $existing->name,
                code: $data['code'] ?? $existing->code,
                email: array_key_exists('email', $data) ? $data['email'] : $existing->email,
                phone: array_key_exists('phone', $data) ? $data['phone'] : $existing->phone,
                address: array_key_exists('address', $data) ? $data['address'] : $existing->address,
                taxNumber: array_key_exists('tax_number', $data) ? $data['tax_number'] : $existing->taxNumber,
                currency: $data['currency'] ?? $existing->currency,
                creditLimit: isset($data['credit_limit']) ? (float) $data['credit_limit'] : $existing->creditLimit,
                balance: isset($data['balance']) ? (float) $data['balance'] : $existing->balance,
                isActive: isset($data['is_active']) ? (bool) $data['is_active'] : $existing->isActive,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );
            $this->customerRepository->save($customer);
            Event::dispatch(new CustomerUpdated($customer));
            return $customer;
        });
    }

    public function deleteCustomer(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getCustomer($tenantId, $id);
            $this->customerRepository->delete($tenantId, $id);
        });
    }
}
