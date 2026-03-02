<?php

declare(strict_types=1);

namespace Modules\Organisation\Domain\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Domain\Contracts\RepositoryContract;

/**
 * Branch repository contract.
 */
interface BranchRepositoryContract extends RepositoryContract
{
    /**
     * Return all branches for a given organisation (tenant-scoped).
     */
    public function findByOrganisation(int|string $organisationId): Collection;
}
