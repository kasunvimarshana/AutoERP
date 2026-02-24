<?php

namespace Modules\HR\Domain\Contracts;

use Modules\Shared\Domain\Contracts\RepositoryInterface;

interface SalaryStructureRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $tenantId, string $code): ?object;

    public function findWithLines(string $id): ?object;

    public function paginate(array $filters = [], int $perPage = 15): object;
}
