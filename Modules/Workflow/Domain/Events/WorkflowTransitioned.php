<?php

namespace Modules\Workflow\Domain\Events;

class WorkflowTransitioned
{
    public function __construct(
        public readonly string $workflowId,
        public readonly string $tenantId,
        public readonly string $documentType,
        public readonly string $documentId,
        public readonly string $fromState,
        public readonly string $toState,
        public readonly string $actorId,
    ) {}
}
