<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Customer\Domain\Entities\CustomerContact;

interface CustomerContactRepositoryInterface extends RepositoryInterface
{
    public function save(CustomerContact $contact): CustomerContact;

    public function clearPrimaryByCustomer(int $tenantId, int $customerId, ?int $excludeId = null): void;

    public function find(int|string $id, array $columns = ['*']): ?CustomerContact;
}
