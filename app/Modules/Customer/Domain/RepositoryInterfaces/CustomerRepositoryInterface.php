<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\RepositoryInterfaces;

use Modules\Customer\Domain\Entities\Customer;

interface CustomerRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?Customer;

    /** @return Customer[] */
    public function findAll(string $tenantId): array;

    public function findByCode(string $tenantId, string $code): ?Customer;

    /** @return Customer[] */
    public function findActive(string $tenantId): array;

    public function save(Customer $customer): void;

    public function delete(string $tenantId, string $id): void;
}
