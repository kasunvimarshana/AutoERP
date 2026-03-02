<?php

declare(strict_types=1);

namespace Modules\Workflow\Domain\Entities;

final class WorkflowState
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $workflowDefinitionId,
        public readonly int $tenantId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $isInitial,
        public readonly bool $isFinal,
        public readonly int $sortOrder,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
