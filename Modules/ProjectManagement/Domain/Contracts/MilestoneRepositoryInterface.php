<?php

namespace Modules\ProjectManagement\Domain\Contracts;

use Illuminate\Support\Collection;
use Modules\Shared\Domain\Contracts\RepositoryInterface;

interface MilestoneRepositoryInterface extends RepositoryInterface
{
    public function findByProject(string $projectId): Collection;
}
