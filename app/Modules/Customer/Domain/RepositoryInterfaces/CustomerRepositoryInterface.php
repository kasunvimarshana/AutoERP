<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Customer\Domain\Entities\Customer;

interface CustomerRepositoryInterface extends RepositoryInterface
{
    public function save(Customer $customer): Customer;

    public function findByTenantAndUserId(int $tenantId, int $userId): ?Customer;

    public function findByTenantAndCustomerCode(int $tenantId, string $customerCode): ?Customer;

    public function find(int|string $id, array $columns = ['*']): ?Customer;
}
