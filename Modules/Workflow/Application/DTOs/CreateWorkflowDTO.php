<?php

declare(strict_types=1);

namespace Modules\Workflow\Application\DTOs;

/**
 * Data Transfer Object for creating a WorkflowDefinition.
 */
final class CreateWorkflowDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $entityType,
        public readonly ?string $description,
        public readonly bool $isActive,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name:        $data['name'],
            entityType:  $data['entity_type'],
            description: $data['description'] ?? null,
            isActive:    (bool) ($data['is_active'] ?? true),
        );
    }
}
