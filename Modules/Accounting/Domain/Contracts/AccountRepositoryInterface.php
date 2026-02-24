<?php

namespace Modules\Accounting\Domain\Contracts;

use Modules\Shared\Domain\Contracts\RepositoryInterface;

interface AccountRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $tenantId, string $code): ?object;
    public function paginate(array $filters, int $perPage = 15): object;
}
