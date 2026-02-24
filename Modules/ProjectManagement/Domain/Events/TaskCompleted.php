<?php

namespace Modules\ProjectManagement\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class TaskCompleted extends DomainEvent
{
    public function __construct(
        public readonly string $taskId,
        public readonly string $projectId,
        public readonly string $tenantId,
    ) {
        parent::__construct();
    }
}
