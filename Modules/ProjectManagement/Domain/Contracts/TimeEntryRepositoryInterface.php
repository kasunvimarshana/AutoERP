<?php

namespace Modules\ProjectManagement\Domain\Contracts;

use Illuminate\Support\Collection;
use Modules\Shared\Domain\Contracts\RepositoryInterface;

interface TimeEntryRepositoryInterface extends RepositoryInterface
{
    public function findByProject(string $projectId): Collection;

    public function sumHoursByProject(string $projectId): string;
}
