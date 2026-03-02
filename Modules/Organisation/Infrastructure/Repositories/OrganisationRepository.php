<?php

declare(strict_types=1);

namespace Modules\Organisation\Infrastructure\Repositories;

use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\Organisation\Domain\Contracts\OrganisationRepositoryContract;
use Modules\Organisation\Domain\Entities\Organisation;

/**
 * Organisation repository implementation.
 *
 * Extends the tenant-aware AbstractRepository.
 * All queries are automatically scoped to the current tenant.
 */
class OrganisationRepository extends AbstractRepository implements OrganisationRepositoryContract
{
    public function __construct()
    {
        $this->modelClass = Organisation::class;
    }
}
