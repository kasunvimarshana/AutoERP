<?php

declare(strict_types=1);

namespace Modules\Metadata\Domain\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Domain\Contracts\RepositoryContract;

/**
 * Custom field repository contract.
 */
interface CustomFieldRepositoryContract extends RepositoryContract
{
    /**
     * Return all active custom field definitions for a given entity type.
     */
    public function findByEntityType(string $entityType): Collection;
}
