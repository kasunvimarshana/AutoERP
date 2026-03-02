<?php

declare(strict_types=1);

namespace Modules\Organisation\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\Organisation\Domain\Contracts\BranchRepositoryContract;
use Modules\Organisation\Domain\Entities\Branch;

/**
 * Branch repository implementation.
 *
 * Extends the tenant-aware AbstractRepository.
 * All queries are automatically scoped to the current tenant.
 */
class BranchRepository extends AbstractRepository implements BranchRepositoryContract
{
    public function __construct()
    {
        $this->modelClass = Branch::class;
    }

    /**
     * {@inheritdoc}
     */
    public function findByOrganisation(int|string $organisationId): Collection
    {
        return $this->query()
            ->where('organisation_id', $organisationId)
            ->get();
    }
}
