<?php

declare(strict_types=1);

namespace Modules\Workflow\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\Workflow\Domain\Contracts\WorkflowRepositoryContract;
use Modules\Workflow\Domain\Entities\WorkflowDefinition;

/**
 * Workflow repository implementation.
 *
 * Tenant-aware data access for WorkflowDefinition.
 * No business logic — data access only.
 */
class WorkflowRepository extends AbstractRepository implements WorkflowRepositoryContract
{
    protected string $modelClass = WorkflowDefinition::class;

    /**
     * {@inheritdoc}
     */
    public function findByEntityType(string $entityType): Collection
    {
        return $this->query()
            ->where('entity_type', $entityType)
            ->get();
    }
}
