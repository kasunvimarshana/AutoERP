<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Contracts;

use Modules\Customer\Domain\Entities\Customer;

interface CustomerServiceInterface
{
    public function getCustomer(string $tenantId, string $id): Customer;

    /** @return Customer[] */
    public function getAllCustomers(string $tenantId): array;

    public function createCustomer(string $tenantId, array $data): Customer;

    public function updateCustomer(string $tenantId, string $id, array $data): Customer;

    public function deleteCustomer(string $tenantId, string $id): void;
}
