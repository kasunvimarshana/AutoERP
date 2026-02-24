<?php

namespace Modules\HR\Domain\Contracts;

use Modules\Shared\Domain\Contracts\RepositoryInterface;

interface PerformanceGoalRepositoryInterface extends RepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): object;
}
