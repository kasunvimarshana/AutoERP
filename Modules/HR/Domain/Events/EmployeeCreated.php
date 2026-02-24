<?php

namespace Modules\HR\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class EmployeeCreated extends DomainEvent
{
    public function __construct(public readonly string $employeeId)
    {
        parent::__construct();
    }
}
