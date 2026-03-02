<?php

declare(strict_types=1);

namespace Modules\Workflow\Application\DTOs;

/**
 * Data Transfer Object for creating a Workflow Instance.
 */
final class CreateWorkflowInstanceDTO
{
    public function __construct(
        public readonly int $workflowDefinitionId,
        public readonly string $entityType,
        public readonly int $entityId,
        public readonly ?int $initialStateId,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            workflowDefinitionId: (int) $data['workflow_definition_id'],
            entityType:           $data['entity_type'],
            entityId:             (int) $data['entity_id'],
            initialStateId:       isset($data['initial_state_id']) ? (int) $data['initial_state_id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'workflow_definition_id' => $this->workflowDefinitionId,
            'entity_type'            => $this->entityType,
            'entity_id'              => $this->entityId,
            'initial_state_id'       => $this->initialStateId,
        ];
    }
}
