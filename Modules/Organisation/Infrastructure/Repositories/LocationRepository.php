<?php

declare(strict_types=1);

namespace Modules\Organisation\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\Organisation\Domain\Contracts\LocationRepositoryContract;
use Modules\Organisation\Domain\Entities\Location;

/**
 * Location repository implementation.
 *
 * Extends the tenant-aware AbstractRepository.
 * All queries are automatically scoped to the current tenant.
 */
class LocationRepository extends AbstractRepository implements LocationRepositoryContract
{
    public function __construct()
    {
        $this->modelClass = Location::class;
    }

    /**
     * {@inheritdoc}
     */
    public function findByBranch(int|string $branchId): Collection
    {
        return $this->query()
            ->where('branch_id', $branchId)
            ->get();
    }
}
