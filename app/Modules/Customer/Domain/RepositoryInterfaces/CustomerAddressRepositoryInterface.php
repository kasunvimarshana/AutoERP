<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Customer\Domain\Entities\CustomerAddress;

interface CustomerAddressRepositoryInterface extends RepositoryInterface
{
    public function save(CustomerAddress $address): CustomerAddress;

    public function clearDefaultByCustomerAndType(int $tenantId, int $customerId, string $type, ?int $excludeId = null): void;

    public function find(int|string $id, array $columns = ['*']): ?CustomerAddress;
}
