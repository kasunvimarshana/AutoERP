<?php

declare(strict_types=1);

namespace Modules\Workflow\Domain\Entities;

final class WorkflowTransition
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $workflowDefinitionId,
        public readonly int $fromStateId,
        public readonly int $toStateId,
        public readonly int $tenantId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $requiresComment,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
