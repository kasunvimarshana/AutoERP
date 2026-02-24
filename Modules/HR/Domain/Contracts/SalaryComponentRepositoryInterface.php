<?php

namespace Modules\HR\Domain\Contracts;

use Modules\Shared\Domain\Contracts\RepositoryInterface;

interface SalaryComponentRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $tenantId, string $code): ?object;

    public function paginate(array $filters = [], int $perPage = 15): object;
}
