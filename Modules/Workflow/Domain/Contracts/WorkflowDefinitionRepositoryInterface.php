<?php

declare(strict_types=1);

namespace Modules\Workflow\Domain\Contracts;

use Modules\Workflow\Domain\Entities\WorkflowDefinition;
use Modules\Workflow\Domain\Entities\WorkflowState;
use Modules\Workflow\Domain\Entities\WorkflowTransition;

interface WorkflowDefinitionRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?WorkflowDefinition;

    public function findAll(int $tenantId, int $page, int $perPage): array;

    public function save(WorkflowDefinition $entity, array $states = [], array $transitions = []): WorkflowDefinition;

    public function delete(int $id, int $tenantId): void;

    /** @return WorkflowState[] */
    public function findStates(int $definitionId, int $tenantId): array;

    /** @return WorkflowTransition[] */
    public function findTransitions(int $definitionId, int $tenantId): array;
}
