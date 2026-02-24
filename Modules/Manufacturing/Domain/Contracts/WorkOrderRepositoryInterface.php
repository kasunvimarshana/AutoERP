<?php

namespace Modules\Manufacturing\Domain\Contracts;

use Illuminate\Support\Collection;
use Modules\Shared\Domain\Contracts\RepositoryInterface;

interface WorkOrderRepositoryInterface extends RepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): object;

    public function findByStatus(string $tenantId, string $status): Collection;
}
