<?php

namespace Modules\Accounting\Domain\Contracts;

use Modules\Shared\Domain\Contracts\RepositoryInterface;

interface BankAccountRepositoryInterface extends RepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): object;
    public function findActiveByTenant(string $tenantId): array;
}
