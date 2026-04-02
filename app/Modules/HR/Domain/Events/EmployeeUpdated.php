<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\HR\Domain\Entities\Employee;

class EmployeeUpdated extends BaseEvent
{
    public function __construct(public readonly Employee $employee)
    {
        parent::__construct($employee->getTenantId(), $employee->getId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'              => $this->employee->getId(),
            'employee_number' => $this->employee->getEmployeeNumber()->value(),
            'first_name'      => $this->employee->getFirstName()->value(),
            'last_name'       => $this->employee->getLastName()->value(),
        ]);
    }
}
