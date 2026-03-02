<?php

declare(strict_types=1);

namespace Modules\Workflow\Domain\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Domain\Contracts\RepositoryContract;

/**
 * Workflow repository contract.
 */
interface WorkflowRepositoryContract extends RepositoryContract
{
    /**
     * Find workflow definitions by entity type (tenant-scoped).
     */
    public function findByEntityType(string $entityType): Collection;
}
