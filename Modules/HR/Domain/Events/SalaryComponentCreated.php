<?php

namespace Modules\HR\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class SalaryComponentCreated extends DomainEvent
{
    public function __construct(
        public readonly string $componentId,
        public readonly string $tenantId,
        public readonly string $code,
        public readonly string $type,
    ) {
        parent::__construct();
    }
}
