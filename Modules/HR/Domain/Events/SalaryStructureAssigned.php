<?php

namespace Modules\HR\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class SalaryStructureAssigned extends DomainEvent
{
    public function __construct(
        public readonly string $assignmentId,
        public readonly string $tenantId,
        public readonly string $employeeId,
        public readonly string $structureId,
    ) {
        parent::__construct();
    }
}
