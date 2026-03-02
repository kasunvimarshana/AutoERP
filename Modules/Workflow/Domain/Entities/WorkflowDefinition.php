<?php

declare(strict_types=1);

namespace Modules\Workflow\Domain\Entities;

final class WorkflowDefinition
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $entityType,
        public readonly string $status,
        public readonly bool $isActive,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
