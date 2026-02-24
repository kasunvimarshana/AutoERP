<?php

namespace Modules\ProjectManagement\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class MilestoneCreated extends DomainEvent
{
    public function __construct(
        public readonly string $milestoneId,
        public readonly string $projectId,
        public readonly string $tenantId,
        public readonly string $name,
    ) {
        parent::__construct();
    }
}
