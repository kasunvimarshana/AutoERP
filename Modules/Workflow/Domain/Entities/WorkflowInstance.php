<?php

declare(strict_types=1);

namespace Modules\Workflow\Domain\Entities;

final class WorkflowInstance
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly int $workflowDefinitionId,
        public readonly string $entityType,
        public readonly int $entityId,
        public readonly int $currentStateId,
        public readonly string $status,
        public readonly ?string $startedAt,
        public readonly ?string $completedAt,
        public readonly ?int $startedByUserId,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
