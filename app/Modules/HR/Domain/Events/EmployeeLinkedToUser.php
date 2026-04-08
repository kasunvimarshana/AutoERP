<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\HR\Domain\Entities\Employee;

class EmployeeLinkedToUser extends BaseEvent
{
    public function __construct(public readonly Employee $employee)
    {
        parent::__construct($employee->getTenantId(), $employee->getId());
    }
}
