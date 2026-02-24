<?php

namespace Modules\Logistics\Domain\Contracts;

use Modules\Shared\Domain\Contracts\RepositoryInterface;

interface CarrierRepositoryInterface extends RepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): object;

    public function findByCode(string $tenantId, string $code): ?object;
}
