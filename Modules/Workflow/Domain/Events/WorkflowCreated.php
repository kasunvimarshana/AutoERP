<?php

namespace Modules\Workflow\Domain\Events;

class WorkflowCreated
{
    public function __construct(
        public readonly string $workflowId,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly string $documentType,
    ) {}
}
