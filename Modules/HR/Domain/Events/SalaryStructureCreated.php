<?php

namespace Modules\HR\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class SalaryStructureCreated extends DomainEvent
{
    public function __construct(
        public readonly string $structureId,
        public readonly string $tenantId,
        public readonly string $code,
    ) {
        parent::__construct();
    }
}
