<?php

declare(strict_types=1);

namespace Modules\Organisation\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\Organisation\Domain\Contracts\DepartmentRepositoryContract;
use Modules\Organisation\Domain\Entities\Department;

/**
 * Department repository implementation.
 *
 * Extends the tenant-aware AbstractRepository.
 * All queries are automatically scoped to the current tenant.
 */
class DepartmentRepository extends AbstractRepository implements DepartmentRepositoryContract
{
    public function __construct()
    {
        $this->modelClass = Department::class;
    }

    /**
     * {@inheritdoc}
     */
    public function findByLocation(int|string $locationId): Collection
    {
        return $this->query()
            ->where('location_id', $locationId)
            ->get();
    }
}
