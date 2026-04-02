<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\HR\Domain\Entities\Department;

class DepartmentCreated extends BaseEvent
{
    public function __construct(public readonly Department $department)
    {
        parent::__construct($department->getTenantId(), $department->getId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'   => $this->department->getId(),
            'name' => $this->department->getName()->value(),
            'code' => $this->department->getCode()?->value(),
        ]);
    }
}
