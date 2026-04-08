<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Customer\Domain\Entities\Customer;

interface CustomerRepositoryInterface extends RepositoryInterface
{
    public function findByCode(int $tenantId, string $code): ?Customer;

    public function findByTenant(int $tenantId): Collection;

    public function findByUserId(int $tenantId, int $userId): ?Customer;

    public function save(Customer $customer): Customer;
}
