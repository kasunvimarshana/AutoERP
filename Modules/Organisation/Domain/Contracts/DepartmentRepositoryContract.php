<?php

declare(strict_types=1);

namespace Modules\Organisation\Domain\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Domain\Contracts\RepositoryContract;

/**
 * Department repository contract.
 */
interface DepartmentRepositoryContract extends RepositoryContract
{
    /**
     * Return all departments for a given location (tenant-scoped).
     */
    public function findByLocation(int|string $locationId): Collection;
}
