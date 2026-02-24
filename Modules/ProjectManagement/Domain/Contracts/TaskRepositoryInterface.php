<?php

namespace Modules\ProjectManagement\Domain\Contracts;

use Illuminate\Support\Collection;
use Modules\Shared\Domain\Contracts\RepositoryInterface;

interface TaskRepositoryInterface extends RepositoryInterface
{
    public function findByProject(string $projectId): Collection;

    public function paginate(array $filters = [], int $perPage = 15): object;
}
