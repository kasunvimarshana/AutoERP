<?php

declare(strict_types=1);

namespace Modules\Workflow\Application\Commands;

final readonly class StartWorkflowInstanceCommand
{
    public function __construct(
        public int $tenantId,
        public int $workflowDefinitionId,
        public string $entityType,
        public int $entityId,
        public ?int $startedByUserId,
    ) {}

    public function rules(): array
    {
        return [
            'tenantId' => ['required', 'integer', 'min:1'],
            'workflowDefinitionId' => ['required', 'integer', 'min:1'],
            'entityType' => ['required', 'string', 'max:100'],
            'entityId' => ['required', 'integer', 'min:1'],
        ];
    }

    public function toArray(): array
    {
        return [
            'tenantId' => $this->tenantId,
            'workflowDefinitionId' => $this->workflowDefinitionId,
            'entityType' => $this->entityType,
            'entityId' => $this->entityId,
            'startedByUserId' => $this->startedByUserId,
        ];
    }
}
