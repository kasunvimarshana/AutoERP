<?php

declare(strict_types=1);

namespace Modules\Organisation\Domain\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Domain\Contracts\RepositoryContract;

/**
 * Location repository contract.
 */
interface LocationRepositoryContract extends RepositoryContract
{
    /**
     * Return all locations for a given branch (tenant-scoped).
     */
    public function findByBranch(int|string $branchId): Collection;
}
